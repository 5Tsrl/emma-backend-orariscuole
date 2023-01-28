<?php
declare(strict_types=1);

namespace Orariscuole\Model\Entity;

use Cake\ORM\Entity;

/**
 * Timeslot Entity
 *
 * @property int $id
 * @property int|null $timetable_id
 * @property int|null $qty
 * @property string|null $slot
 * @property int|null $day
 *
 * @property \Moma\Model\Entity\Timetable $timetable
 */
class Timeslot extends Entity
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
    'timetable_id' => true,
    'qty' => true,
    'slot' => true,
    'day' => true,
    'timetable' => true,
    'is_out' => true,
    ];
}
