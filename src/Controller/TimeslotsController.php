<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Exception;

/**
 * Timeslots Controller
 *
 * @property \Moma\Model\Table\TimeslotsTable $Timeslots
 * @method \Moma\Model\Entity\Timeslot[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TimeslotsController extends AppController
{
    /**
     * Index method
     * Devo tiare fuori una cosa fatta così, per ingresso e uscita
     *     {
     *     giorno: "Lun",
     *     h0750: 200,
     *     h0800: 40,
     *     h0810: 210,
     *     h0820: 100,
     * },
     * {
     *     giorno: "Mar",
     *      h0750: 200,
     *      h0800: 200,
     *      h0810: 210,
     *      h0820: 0,
     *  },
     * struttura su db
     * {
     * id:    1
     * qty:   0
     * slot:  "h0720"
     * day:   1
     * }
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index($timetable_id = null)
    {
        $this->allowRolesOnly(["admin", "superiori"]);

        //Faccio due query, per differenziare in e out
        $timeslots = $this->Timeslots->getForBTable($timetable_id, false);

        $this->set('timeslots_in', $timeslots);

        $timeslots = $this->Timeslots->getForBTable($timetable_id, true);
        $this->set('timeslots_out', $timeslots);

        $this->viewBuilder()->setOption('serialize', ['timeslots_in', 'timeslots_out']);
    }

    //Genera l'excel degli orari
    public function export()
    {
        $this->allowRolesOnly(["admin", "moma", "superiori"]);

        $connection = ConnectionManager::get('default');

        $sql = "
    SELECT 
        STR_TO_DATE(Timeslots.slot, 'h%H%i') as slot, Timeslots.qty, Timeslots.is_out, Timeslots.day,
        Timetables.id, Timetables.valid_from, Timetables.approved, Timetables.note, Timetables.type,
        Offices.id, Offices.province, Offices.city, Offices.address, Offices.company_code,
        Companies.id, Companies.name nome_scuola, Offices.name nome_sede
    FROM
      timeslots Timeslots 
      LEFT JOIN timetables Timetables ON Timetables.id = (Timeslots.timetable_id) 
      LEFT JOIN offices Offices ON Offices.id = (Timetables.office_id) 
      LEFT JOIN companies Companies ON Companies.id = (Offices.company_id) 
    WHERE 
      Timeslots.qty > 0 
    ";

        //Querystring provincia
        if ($this->request->getQuery('provincia')) {
            $sql .= " AND Offices.province = :p ";
        }
        //Querystring q (ricerca testo libero)
        if ($this->request->getQuery('q')) {
            $sql .= " AND (Offices.city like :q OR Companies.name like :q ) \n";
        }

        $identity = $this->Authentication->getIdentity();
        //L'utente con l'azienda valorizzata può vedere solo la sua azienda
        $company_id = $identity->get('company_id');
        if (!empty($company_id)) {
            $sql .= " AND Companies.id = :c \n";
        }

        //Querystring from (data validità)
        if ($this->request->getQuery('from')) {
            $sql .= " AND Timetables.valid_from >= :f \n";
        } else {
            $sql .= " AND Timetables.id = (SELECT t2.id from timetables t2 where Timetables.office_id = t2.office_id order by t2.valid_from desc limit 1) \n";
        }

        $sql .= "
      ORDER BY 
      Offices.province, 
      Offices.city, 
      Timetables.valid_from DESC
    ";

        $statement = $connection->prepare($sql);
        if ($this->request->getQuery('provincia')) {
            $statement->bindValue('p', $this->request->getQuery('provincia'), 'string');
        }
        if ($this->request->getQuery('q')) {
            $statement->bindValue('q', "%" . $this->request->getQuery('q') . "%", 'string');
        }
        if (!empty($company_id)) {
            $statement->bindValue('c', $company_id, 'integer');
        }
        if ($this->request->getQuery('from')) {
            $statement->bindValue('f', $this->request->getQuery('from'), 'datetime');
        }
        $statement->execute();
        $rows = $statement->fetchAll('assoc');
        $this->set('timeslots', $rows);
    }

    //Genera l'excel degli orari
    public function exportOneLine()
    {
        $this->allowRolesOnly(["admin", "moma", "superiori"]);
        if ($this->request->getParam('_ext') !== 'xls') {
            return;
        };

        $connection = ConnectionManager::get('default');

        //Dev'essere spezzata per gestire la funzione sum
        $sql = "
    SELECT 
        Offices.address AS address, 
        Timeslots.slot AS slot, 
        min(Timetables.office_id) AS office_id,
        min(Companies.name) as name,
        min(Offices.province) as province,  
        min(Offices.cap) as cap,  
        min(Offices.city) as city,  
        min(Offices.office_code) as code,  
        (
          SUM(Timeslots.qty)
        ) AS tot_pax, 
        (
          FROM_UNIXTIME(
            UNIX_TIMESTAMP(
              STR_TO_DATE(`slot`, 'h%H%i')
            ) DIV (60 * 15)* 60 * 15
          )
        ) AS uslot, 
        Timeslots.is_out AS is_out, 
        Timeslots.day AS day, 
        Timeslots.timetable_id AS timetable_id, 
        min(Timetables.office_id) AS office_id 
      FROM 
        timeslots Timeslots 
        LEFT JOIN timetables Timetables ON Timetables.id = (Timeslots.timetable_id) 
        LEFT JOIN offices Offices ON Offices.id = (Timetables.office_id) 
        LEFT JOIN companies Companies ON Companies.id = (Offices.company_id) 
      WHERE 
        Timeslots.qty > 0 
        AND Timeslots.day = :d
      GROUP BY 
        Offices.address, 
        Timeslots.day, 
        Timeslots.is_out, 
        uslot 
    ";
        $statement = $connection->prepare($sql);

        //Querystring day
        if ($this->request->getQuery('day')) {
            $statement->bindValue('d', $this->request->getQuery('day'), 'integer');
        } else {
            throw new Exception("E' obbligatorio specificare un giorno");
        }
        $statement->execute();
        $rows = $statement->fetchAll('assoc');

        //dd($rows);
        $slotFissi = [];
        //Devo generare 4 slot fissi ogni ora  dalle 7 alle 23 (17 ore)
        $start = new FrozenTime('7:00');
        $maxSlots = 17 * 4;
        for ($i = 0; $i < $maxSlots; $i++) {
            $slotFissi[] = $start->addMinute($i * 15)->format('H:i');
        }
        $slotIndex = array_flip($slotFissi);
        //dd($slotFissi);
        $orari = [];
        foreach ($rows as $r) {
            if (isset($r['address'])) {
                $ft = new FrozenTime($r['uslot']);
                $shortSlot = $ft->format('H:i');
                if (isset($r['address'])) {
                    $adx = trim($r['address']);
                    if (!isset($orari[$adx][$r['is_out']][$slotIndex[$shortSlot]])) {
                        $orari[$adx][$r['is_out']][$slotIndex[$shortSlot]] = 0;
                    }
                    $orari[$adx][$r['is_out']][$slotIndex[$shortSlot]] += $r['tot_pax'];
                    $orari[$adx]['name'] = $r['name'];
                    $orari[$adx]['code'] = $r['code'];
                    $orari[$adx]['province'] = $r['province'];
                    $orari[$adx]['city'] = $r['city'];
                    $orari[$adx]['cap'] = $r['cap'];
                }
            }
        }
        $this->set('orari', $orari);
    }


    /**
     * View method
     *
     * @param string|null $id Timeslot id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->allowRolesOnly(["admin", "superiori"]);

        $timeslot = $this->Timeslots->get($id, [
      'contain' => ['Timetables'],
    ]);

        $this->set(compact('timeslot'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->allowRolesOnly(["admin", "superiori"]);

        $timeslot = $this->Timeslots->newEmptyEntity();
        if ($this->request->is('post')) {
            $timeslot = $this->Timeslots->patchEntity($timeslot, $this->request->getData());
            if ($this->Timeslots->save($timeslot)) {
                $this->Flash->success(__('The timeslot has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The timeslot could not be saved. Please, try again.'));
        }
        $timetables = $this->Timeslots->Timetables->find('list', ['limit' => 200]);
        $this->set(compact('timeslot', 'timetables'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Timeslot id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->allowRolesOnly(["admin", "superiori"]);

        $timeslot = $this->Timeslots->get($id, [
      'contain' => [],
    ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $timeslot = $this->Timeslots->patchEntity($timeslot, $this->request->getData());
            if ($this->Timeslots->save($timeslot)) {
                $this->Flash->success(__('The timeslot has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The timeslot could not be saved. Please, try again.'));
        }
        $timetables = $this->Timeslots->Timetables->find('list', ['limit' => 200]);
        $this->set(compact('timeslot', 'timetables'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Timeslot id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->allowRolesOnly(["admin", "superiori"]);
        $this->request->allowMethod(['post', 'delete']);

        $timeslot = $this->Timeslots->get($id);
        if ($this->Timeslots->delete($timeslot)) {
            $this->Flash->success(__('The timeslot has been deleted.'));
        } else {
            $this->Flash->error(__('The timeslot could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
