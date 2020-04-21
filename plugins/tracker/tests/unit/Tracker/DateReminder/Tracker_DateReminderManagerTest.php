<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_DateReminder;
use Tracker_DateReminderManager;
use Tracker_FormElement_Field_Date;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_DateReminderManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_DateReminder
     */
    private $reminder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_Date
     */
    private $field;
    /**
     * @var false|int
     */
    private $today_at_midnight;
    /**
     * @var int
     */
    private $tracker_id;
    /**
     * @var Tracker_DateReminderManager
     */
    private $reminder_manager;

    public function setUp(): void
    {
        $this->field    = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $this->reminder = Mockery::mock(Tracker_DateReminder::class);
        $this->reminder->shouldReceive('getField')->andReturn($this->field);

        $this->today_at_midnight = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
        $this->tracker_id        = 158;

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($this->tracker_id);

        $this->reminder_manager = new Tracker_DateReminderManager($tracker);
    }

    public function testItFetchArtifactsTwoDaysAgo()
    {
        $this->reminder->shouldReceive('getDistance')->andReturn('2');
        $this->reminder->shouldReceive('getNotificationType')->andReturn('1');

        $expected_time = strtotime('-2 days', $this->today_at_midnight);
        $this->field->shouldReceive('getArtifactsByCriterias')->with($expected_time, $this->tracker_id)->once();

        $this->reminder_manager->getArtifactsByreminder($this->reminder);
    }

    public function testItFetchArtifactsFourDaysInTheFuture()
    {
        $this->reminder->shouldReceive('getDistance')->andReturn('4');
        $this->reminder->shouldReceive('getNotificationType')->andReturn('0');

        $expected_time = strtotime('4 days', $this->today_at_midnight);
        $this->field->shouldReceive('getArtifactsByCriterias')->with($expected_time, $this->tracker_id)->once();

        $this->reminder_manager->getArtifactsByreminder($this->reminder);
    }
}
