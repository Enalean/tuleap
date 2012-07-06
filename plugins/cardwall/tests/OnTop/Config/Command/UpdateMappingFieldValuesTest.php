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

require_once dirname(__FILE__) .'/../../../../../tracker/include/constants.php';
require_once dirname(__FILE__) .'/../../../../include/constants.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/Command/UpdateMappingFieldValues.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/ColumnMappingFieldValueDao.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/Tracker.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/TrackerFactory.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/FormElement/Tracker_FormElementFactory.class.php';
require_once dirname(__FILE__) .'/../../../../../../tests/simpletest/common/include/builders/aRequest.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aMockField.php';

class Cardwall_OnTop_Config_Command_UpdateMappingFieldValuesTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns($this->tracker_id);

        $task_tracker = mock('Tracker');
        stub($task_tracker)->getId()->returns(42);

        $story_tracker = mock('Tracker');
        stub($story_tracker)->getId()->returns(69);

        $tracker_factory = mock('TrackerFactory');
        stub($tracker_factory)->getTrackerById(42)->returns($task_tracker);
        stub($tracker_factory)->getTrackerById(69)->returns($story_tracker);

        $status_field   = aMockField()->withId(123)->withTracker($task_tracker)->build();
        $assignto_field = aMockField()->withId(321)->withTracker($story_tracker)->build();
        $stage_field    = aMockField()->withId(322)->withTracker($story_tracker)->build();

        $element_factory = mock('Tracker_FormElementFactory');
        stub($element_factory)->getFieldById('123')->returns($status_field);
        stub($element_factory)->getFieldById('321')->returns($assignto_field);
        stub($element_factory)->getFieldById('322')->returns($stage_field);

        $this->dao     = mock('Cardwall_OnTop_ColumnMappingFieldValueDao');
        $this->command = new Cardwall_OnTop_Config_Command_UpdateMappingFieldValues($tracker, $this->dao, $tracker_factory, $element_factory);
    }

    public function itUpdatesMappingFields() {
        $request = aRequest()->with('mapping_field',
            array(
                '42' => '123',
                '69' => '321',
            )
        )->build();
        $this->command->execute($request);
    }

    public function itDoesntUpdatesMappingFieldsIfItIsNotNeeded() {
        $request = aRequest()->with('mapping_field',
            array(
                '42' => '123',
                '69' => '322',
            )
        )->build();
        $this->command->execute($request);
    }
}
?>
