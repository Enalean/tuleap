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

require_once dirname(__FILE__) .'/../../../bootstrap.php';
require_once dirname(__FILE__) .'/../../../../../../tests/simpletest/common/include/builders/aRequest.php';

class Cardwall_OnTop_Config_Command_CreateMappingFieldTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns($this->tracker_id);

        $task_tracker = mock('Tracker');
        stub($task_tracker)->getId()->returns(42);

        $tracker_factory = mock('TrackerFactory');
        stub($tracker_factory)->getTrackerById('42')->returns($task_tracker);

        $this->dao     = mock('Cardwall_OnTop_ColumnMappingFieldDao');
        $this->command = new Cardwall_OnTop_Config_Command_CreateMappingField($tracker, $this->dao, $tracker_factory);
    }

    public function itCreatesANewMappingField()
    {
        $request = aRequest()->with('add_mapping_on', '42')->build();
        stub($this->dao)->create($this->tracker_id, 42, null)->once();
        $this->command->execute($request);
    }
}
