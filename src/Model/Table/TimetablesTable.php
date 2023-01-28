<?php
declare(strict_types=1);

namespace Orariscuole\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Timetables Model
 *
 * @property \Moma\Model\Table\OfficesTable&\Cake\ORM\Association\BelongsTo $Offices
 * @property \Moma\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \Moma\Model\Table\TimeslotTable&\Cake\ORM\Association\HasMany $Timeslot
 * @method \Moma\Model\Entity\Timetable newEmptyEntity()
 * @method \Moma\Model\Entity\Timetable newEntity(array $data, array $options = [])
 * @method \Moma\Model\Entity\Timetable[] newEntities(array $data, array $options = [])
 * @method \Moma\Model\Entity\Timetable get($primaryKey, $options = [])
 * @method \Moma\Model\Entity\Timetable findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Moma\Model\Entity\Timetable patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Moma\Model\Entity\Timetable[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Moma\Model\Entity\Timetable|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Moma\Model\Entity\Timetable saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Moma\Model\Entity\Timetable[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Moma\Model\Entity\Timetable[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Moma\Model\Entity\Timetable[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Moma\Model\Entity\Timetable[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TimetablesTable extends Table
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

        $this->setTable('timetables');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Offices', [
        'foreignKey' => 'office_id',
        'className' => 'Offices',
        ]);
        $this->belongsTo('Users', [
        'foreignKey' => 'user_id',
        'className' => 'Users',
        ]);
        $this->hasMany('Timeslots', [
        'foreignKey' => 'timetable_id',
        'className' => 'Timeslots',
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
        ->scalar('note')
        ->allowEmptyString('note');

        $validator
        ->boolean('approved')
        ->allowEmptyString('approved');

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
        //$rules->add($rules->existsIn(['office_id'], 'Offices'), ['errorField' => 'office_id']);
        return $rules;
    }
}
