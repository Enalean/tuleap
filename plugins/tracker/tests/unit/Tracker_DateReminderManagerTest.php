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

use Tracker_DateReminder;
use Tracker_DateReminderManager;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class Tracker_DateReminderManagerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private const TRACKER_ID = 158;

    /**
     * @var DateField&\PHPUnit\Framework\MockObject\MockObject
     */
    private $field;
    /**
     * @var false|int
     */
    private $today_at_midnight;
    private Tracker_DateReminderManager $reminder_manager;
    /**
     * @var Tracker_DateReminder&\PHPUnit\Framework\MockObject\MockObject
     */
    private $reminder;

    #[\Override]
    public function setUp(): void
    {
        $this->field    = $this->createMock(DateField::class);
        $this->reminder = $this->createMock(Tracker_DateReminder::class);
        $this->reminder->method('getField')->willReturn($this->field);

        $this->today_at_midnight = mktime(0, 0, 0, date('n'), date('j'), date('Y'));

        $tracker = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();

        $this->reminder_manager = new Tracker_DateReminderManager($tracker);
    }

    public function testItFetchArtifactsTwoDaysAgo(): void
    {
        $this->reminder->method('getDistance')->willReturn('2');
        $this->reminder->method('getNotificationType')->willReturn('1');

        $expected_time = strtotime('-2 days', $this->today_at_midnight);
        $this->field->expects($this->once())->method('getArtifactsByCriterias')->with($expected_time, self::TRACKER_ID);

        $this->reminder_manager->getArtifactsByReminder($this->reminder);
    }

    public function testItFetchArtifactsFourDaysInTheFuture(): void
    {
        $this->reminder->method('getDistance')->willReturn('4');
        $this->reminder->method('getNotificationType')->willReturn('0');

        $expected_time = strtotime('4 days', $this->today_at_midnight);
        $this->field->expects($this->once())->method('getArtifactsByCriterias')->with($expected_time, self::TRACKER_ID);

        $this->reminder_manager->getArtifactsByReminder($this->reminder);
    }
}
