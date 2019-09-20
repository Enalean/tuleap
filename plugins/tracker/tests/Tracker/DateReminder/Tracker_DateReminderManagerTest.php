<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once __DIR__.'/../../bootstrap.php';

class Tracker_DateReminderManagerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->field = mock('Tracker_FormElement_Field_Date');
        $this->reminder = mock('Tracker_DateReminder');
        stub($this->reminder)->getField()->returns($this->field);

        $this->today_at_midnight = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
        $this->tracker_id    = 158;

        $this->reminder_manager = new Tracker_DateReminderManager(aTracker()->withId($this->tracker_id)->build());
    }

    public function itFetchArtifactsTwoDaysAgo()
    {
        stub($this->reminder)->getDistance()->returns('2');
        stub($this->reminder)->getNotificationType()->returns('1');

        $expected_time = strtotime('-2 days', $this->today_at_midnight);
        $this->field->expectOnce('getArtifactsByCriterias', array($expected_time, $this->tracker_id));

        $this->reminder_manager->getArtifactsByreminder($this->reminder);
    }

    public function itFetchArtifactsFourDaysInTheFuture()
    {
        stub($this->reminder)->getDistance()->returns('4');
        stub($this->reminder)->getNotificationType()->returns('0');

        $expected_time = strtotime('4 days', $this->today_at_midnight);
        $this->field->expectOnce('getArtifactsByCriterias', array($expected_time, $this->tracker_id));

        $this->reminder_manager->getArtifactsByreminder($this->reminder);
    }
}
