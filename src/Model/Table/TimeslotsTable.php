<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Timeslots Model
 *
 * @property \Moma\Model\Table\TimetablesTable&\Cake\ORM\Association\BelongsTo $Timetables
 * @method \Moma\Model\Entity\Timeslot newEmptyEntity()
 * @method \Moma\Model\Entity\Timeslot newEntity(array $data, array $options = [])
 * @method \Moma\Model\Entity\Timeslot[] newEntities(array $data, array $options = [])
 * @method \Moma\Model\Entity\Timeslot get($primaryKey, $options = [])
 * @method \Moma\Model\Entity\Timeslot findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Moma\Model\Entity\Timeslot patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Moma\Model\Entity\Timeslot[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Moma\Model\Entity\Timeslot|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Moma\Model\Entity\Timeslot saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Moma\Model\Entity\Timeslot[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Moma\Model\Entity\Timeslot[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Moma\Model\Entity\Timeslot[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Moma\Model\Entity\Timeslot[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class TimeslotsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('timeslots');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Timetables', [
        'foreignKey' => 'timetable_id',
        'className' => 'Timetables',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
        ->integer('id')
        ->allowEmptyString('id', null, 'create');

        $validator
        ->integer('qty')
        ->allowEmptyString('qty');

        $validator
        ->scalar('slot')
        ->maxLength('slot', 5)
        ->allowEmptyString('slot');

        $validator
        ->integer('day')
        ->allowEmptyString('day');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['timetable_id'], 'Timetables'), ['errorField' => 'timetable_id']);

        return $rules;
    }

    //Salva tutti i timeslot associati al timetable

    public function salva($timeslots, $timetable_id, $is_out): bool
    {
        $result = [];
        $giorni = ['Lun' => 1, 'Mar' => 2, 'Mer' => 3, 'Gio' => 4, 'Ven' => 5, 'Sab' => 6, 'Dom' => 7];
        foreach ($timeslots as $t) {
            $giorno = $giorni[$t['giorno']];
            //Tolgo le chiavi inutili
            unset($t['giorno']);
            unset($t['undefined']);

            $slots = array_keys($t);
            if (count($slots) > 0) {
                //Elimino gli slot che non sono in $slots (sono stati cancellati o rinominati)
                $this->deleteAll([
                'timetable_id' => $timetable_id,
                'is_out' => $is_out,
                'day' => $giorno,
                'slot NOT IN' => $slots,
                ]);

                //Creo gli slot nuovi
                foreach ($slots as $s) {
                    $row = [
                        'timetable_id' => $timetable_id,
                        'is_out' => $is_out,
                        'day' => $giorno,
                        'slot' => $s,
                      ];

                    //Cerco se esiste già un record per questi indici
                    $q = $this->find()->where($row);
                    $existingTimeSlot = $q->first();
                    //Se esiste inserisco l'id nel record (così fa update)
                    if ($existingTimeSlot) {
                        $row['id'] = $existingTimeSlot->id;
                    }
                    //Aggiungo la quantità aggiornata
                    $row['qty'] = $t[$s];

                    //Metto nell'insieme dei risultati
                    $result[] = $row;
                }
            }
        }
        $originalTimeSlots = $this->find()->where(['timetable_id' => $timetable_id, 'is_out' => $is_out]);
        $entities = $this->patchEntities($originalTimeSlots, $result);
        if ($this->saveMany($entities)) {
            return true;
        }

        return false;
    }

    //Restituisce tutti i timeslot di un dato timetable, filtrati per ingresso/uscita
    public function getForBTable($timetable_id, $is_out)
    {
        $timeslots = $this->find()
        ->select(['id', 'qty', 'slot', 'day']);

        if (!empty($timetable_id)) {
            $timeslots->where(['timetable_id' => $timetable_id]);
        }
        $timeslots->where(['is_out' => $is_out]);

        $giorni = [
        1 => [
        'giorno' => 'Lun',
        ],
        2 => [
        'giorno' => 'Mar',
        ],
        3 => [
        'giorno' => 'Mer',
        ],
        4 => [
        'giorno' => 'Gio',
        ],
        5 => [
        'giorno' => 'Ven',
        ],
        6 => [
        'giorno' => 'Sab',
        ],
        7 => [
        'giorno' => 'Dom',
        ],
        ];

        foreach ($timeslots as $t) {
            $giorni[$t['day']][$t['slot']] = $t['qty'];
        }

        unset($giorni[7]);

        return $giorni;
    }
}
