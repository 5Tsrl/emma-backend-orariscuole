<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Timetable Entity
 *
 * @property int $id
 * @property int|null $office_id
 * @property \Cake\I18n\FrozenTime|null $valid_from
 * @property string|null $note
 * @property bool|null $approved
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $user_id
 *
 * @property \Moma\Model\Entity\Office $office
 * @property \Moma\Model\Entity\User $user
 * @property \Moma\Model\Entity\Timeslot[] $timeslot
 */
class Timetable extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
    'office_id' => true,
    'valid_from' => true,
    'note' => true,
    'approved' => true,
    'created' => true,
    'modified' => true,
    'user_id' => true,
    'office' => true,
    'user' => true,
    'timeslot' => true,
    'type' => true,
    ];
}
