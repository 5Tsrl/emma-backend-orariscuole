<?php

declare(strict_types=1);

namespace App\Controller;

use App\Notification\timetableReadyNotification;
use Cake\Utility\Hash;
use DateTime;
use Error;

/**
 * Timetables Controller
 *
 * @property \Moma\Model\Table\TimetablesTable $Timetables
 * @method \Moma\Model\Entity\Timetable[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TimetablesController extends AppController
{
  /**
   * Index method
   *
   * @return \Cake\Http\Response|null|void Renders view
   */
  public function index()
  {
    $this->allowRolesOnly(["admin", "superiori"]);

    $this->paginate = [
      'contain' => ['Offices', 'Users'],
    ];
    $timetables = $this->paginate($this->Timetables);

    $this->set(compact('timetables'));
  }

  /**
   * View method
   *
   * @param null $office_id Timetable id.
   * @return \Cake\Http\Response|null|void Renders view
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function view($office_id = null, $timetables_id = null)
  {
    $this->allowRolesOnly(["admin", "superiori"]);

    if (empty($office_id)) {
      throw new Error("E' necessario inserire un codice relativo ad una sede");
    }

    $timetable = $this->Timetables->find()
      ->contain(['Offices' => ['Companies']])
      ->where(['office_id' => $office_id]);

    if (!empty($timetables_id)) {
      $timetable->where(['Timetables.id' => $timetables_id]);
    }

    $timetable->first();
    $this->set(compact('timetable'));
    $this->viewBuilder()->setOption('serialize', ['timetable']);
  }

  /**
   * Add method
   *
   * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
   */
  public function add()
  {
    $this->allowRolesOnly(["admin", "superiori"]);

    $timetable = $this->Timetables->newEmptyEntity();
    if ($this->request->is('post')) {
      //Prima estraggo solo i dati della timetable
      $req_timetable = $this->request->getData('timetable');

      //Forzo/Converto alcuni campi
      $req_timetable['valid_from'] = new DateTime($req_timetable['valid_from']);
      $identity = $this->Authentication->getIdentity();
      $req_timetable['user_id']  = $company_id = $identity->get('id');

      $timetable = $this->Timetables->patchEntity($timetable, $req_timetable, ['associated' => []]);
      if ($this->Timetables->save($timetable)) {
        //Adesso che ho salvato il timetable, devo aggiornare gli orari (TimeSlots)
        $req_time_in = $this->request->getData('tab_in');
        $this->Timetables->Timeslots->salva($req_time_in, $timetable->id, 0);

        $req_time_out = $this->request->getData('tab_out');
        $this->Timetables->Timeslots->salva($req_time_out, $timetable->id, 1);

        if (!$this->request->is('json')) {
          $this->Flash->success(__('The timetable has been saved.'));

          return $this->redirect(['action' => 'index']);
        }
        $this->set('timetable_id', $timetable->id);
        $this->viewBuilder()->setOption('serialize', ['timetable_id']);

        return;
      }
      $this->Flash->error(__('The timetable could not be saved. Please, try again.'));
    }
    $offices = $this->Timetables->Offices->find('list', ['limit' => 200]);
    $users = $this->Timetables->Users->find('list', ['limit' => 200]);
    $this->set(compact('timetable', 'offices', 'users'));
  }

  /**
   * Edit method
   *
   * @param string|null $id Timetable id.
   * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function edit($id = null)
  {
    $this->allowRolesOnly(["admin", "superiori"]);

    $timetable = $this->Timetables->get($id, [
      'contain' => [],
    ]);
    if ($this->request->is(['patch', 'post', 'put'])) {
      //Prima estraggo solo i dati della timetable
      $req_timetable = $this->request->getData('timetable');
      //Forzo alcuni valori
      //$req_timetable['valid_from'] = new DateTime($req_timetable['valid_from']);
      $identity = $this->Authentication->getIdentity();
      $req_timetable['user_id']  = $company_id = $identity->get('id');
      $timetable = $this->Timetables->patchEntity($timetable, $req_timetable, ['associated' => []]);

      if ($this->Timetables->save($timetable)) {
        //Adesso che ho salvato il timetable, devo aggiornare gli orari (TimeSlots)
        $req_time_in = $this->request->getData('tab_in');
        $this->Timetables->Timeslots->salva($req_time_in, $timetable->id, 0);

        $req_time_out = $this->request->getData('tab_out');
        $this->Timetables->Timeslots->salva($req_time_out, $timetable->id, 1);

        if (!$this->request->is('json')) {
          $this->Flash->success(__('The timetable has been saved.'));

          return $this->redirect(['action' => 'index']);
        }
        $this->set('timetable_id', $timetable->id);
        $this->viewBuilder()->setOption('serialize', ['timetable_id']);

        return;
      }
      //TODO: Gestire Errore in JSON
      $this->Flash->error(__('The timetable could not be saved. Please, try again.'));
    }
    $offices = $this->Timetables->Offices->find('list', ['limit' => 200]);
    $users = $this->Timetables->Users->find('list', ['limit' => 200]);
    $this->set(compact('timetable', 'offices', 'users'));
  }

  /**
   * Delete method
   *
   * @param string|null $id Timetable id.
   * @return \Cake\Http\Response|null|void Redirects to index.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function delete($id = null)
  {
    $this->allowRolesOnly(["admin", "superiori"]);
    $this->request->allowMethod(['post', 'delete']);

    $timetable = $this->Timetables->get($id);
    //TODO: verifica che cancelli anche i time slot
    //TODO: Verifica i privilegi utente
    if ($this->Timetables->delete($timetable)) {
      if (!$this->request->is('json')) {
        $this->Flash->success(__('The timetable has been deleted.'));
        return $this->redirect(['action' => 'index']);
      } else {
        $msg = 'Orario eliminato con successo';
      }
    } else {
      if (!$this->request->is('json')) {
        $this->Flash->error(__('Impossibile cancellare questo orario'));
        return $this->redirect(['action' => 'index']);
      } else {
        $msg = 'Impossibile cancellare questo orario';
      }
    }

    $this->set(compact('msg'));
    $this->viewBuilder()->setOption('serialize', ['msg']);
  }

  public function notify($id)
  {
    $timetable = $this->Timetables->find()
      ->where(['Timetables.id' => $id])
      ->contain(['Offices' => ['Companies']])
      ->first();

    //Send an instant notification to the admin user
    $n = new timetableReadyNotification($timetable);
    $n->toMail();
    //$n->toDB();
    $msg = 'Notifica inviata con successo';

    //Set the notify flag to true so to prepare for digest notification
    $timetable->notify = true;
    if ($this->Timetables->save($timetable)) {
      $msg .= PHP_EOL . ' Timetable messa in coda per notifica giornaliera';
    } else {
      $msg .= PHP_EOL . ' Impossibile mettere in coda per la notifica giornaliera';
    }

    $this->set(compact('msg'));
    $this->viewBuilder()->setOption('serialize', ['msg']);
  }

  //Statistiche sul caricamento orari
  public function stats()
  {
    $sedi = $this->Timetables->Offices->find();
    $annoscolastico = $this->getInizioAnnoScolastico();

    $sedi->select([
      'sedi' => $sedi->func()->count('Offices.id'),
      'no_ifp' => $sedi->func()->count('Offices.office_code'),
      'Offices.province'
    ])
      ->contain(['Companies'])
      ->where(['Companies.type' => 4])
      ->order(['Offices.province'])
      ->group(['Offices.province'])
      ->all();

    $scuole = $this->Timetables->Offices->Companies->find()
      ->where(['Companies.type' => 4])
      ->count();

    $subquery = $this->Timetables->find()
      ->select(['office_id'])
      ->distinct(['office_id'])
      ->where(['valid_from >='=>$annoscolastico]);

    $orari = $subquery->count();

    $sedi_si = $this->Timetables->Offices->find();
    $sedi_si->select([
      'sedi' => $sedi_si->func()->count('Offices.id'),
      'no_ifp' => $sedi_si->func()->count('Offices.office_code'),
      'Offices.province'
    ])
      ->contain(['Companies'])
      ->where([
        'Companies.type' => 4,
        'Offices.id IN' => $subquery,
      ])
      ->order(['Offices.province'])
      ->group(['Offices.province'])
      ->toList();


    $sedi_no = $this->Timetables->Offices->find()
      ->select(['Offices.id', 'Offices.name', 'Companies.Name', 'Offices.City', 'Companies.email', 'Offices.province'])
      ->contain(['Companies'])
      ->where([
        'Companies.type' => 4,
        'Offices.id NOT IN' => $subquery,
      ])
      ->order(['Offices.City', 'Companies.Name'])
      ->toList();


    $this->set(compact('sedi', 'scuole', 'orari', 'sedi_no', 'sedi_si'));
    $this->viewBuilder()->setOption('serialize', ['sedi', 'scuole', 'orari', 'sedi_no', 'sedi_si']);
  }

  //Statistiche sul caricamento orari
  //Export these columns Provincia, Città, Istituto, Sede, Formazione Professionale, Privato/Pubblico
  //Companies.Province, Companies.City, Companies.name, Companies.address,
  //Formazione Professionale => Companies.company_code IS NULL (write SI/NO is it is Formazione Professionale),
  //Privato => Offices.office_code_external is NULL (write Pubblico or Privato in the cell )
  public function statsXls()
  {
    $annoscolastico = $this->getInizioAnnoScolastico();
    $subquery = $this->Timetables->find()
      ->select(['office_id'])
      ->distinct(['office_id'])
     ->where(['valid_from >=' => $annoscolastico]);

    $companies = $this->Timetables->Offices->find();
    //it's easier if the ORM returns an array instead of an object
    $companies->disableHydration();

    $companies =  $companies->contain(['Companies'])
      ->select([
        'Offices.province', 'Offices.city',
        'Companies.company_code',
        'Companies.name',
        'Offices.name',
        'Offices.address', 'Companies.company_code', 'Offices.office_code_external'
      ])
      ->where([
        'Companies.type' => 4,
        'Offices.id NOT IN' => $subquery,
      ])
      ->order(['Offices.province', 'Offices.city', 'Companies.name']);

    $this->set(compact('companies', $companies));
    //$this->viewBuilder()->setOption('serialize', ['res']);
  }

  public function last()
  {
    $last = $this->Timetables->find()
      ->contain(['Offices' => ['Companies']])   //JOIN
      ->select(['valid_from', 'type', 'Companies.name', 'Offices.name'])    //Pass only the needed data
      ->order(['Timetables.id DESC'])
      ->first();

    $this->set('timetable', $last);
    $this->viewBuilder()->setOption('serialize', ['timetable']);
  }

  private function getInizioAnnoScolastico(){
    $dt = new DateTime();
    $mesecorrente = $dt->format('m');
    if ($mesecorrente >= 9) { //Sett, ott, nov, dic ==> anno scolastico = anno corrente
      $annoscolastico = $dt->format('Y') . "-09-01";
    } else { // Anno scolastico iniziato l'anno scorso
      $annoscolastico = $dt->format('Y') - 1 . "-09-01";
    }
    return $annoscolastico;
  }
}
