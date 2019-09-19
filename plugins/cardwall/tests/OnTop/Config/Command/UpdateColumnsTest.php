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

class Cardwall_OnTop_Config_Command_UpdateColumnsTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns($this->tracker_id);

        $this->dao     = mock('Cardwall_OnTop_ColumnDao');
        $this->command = new Cardwall_OnTop_Config_Command_UpdateColumns($tracker, $this->dao);
    }

    public function itUpdatesAllColumns()
    {
        $request = aRequest()->with('column', array(
            12 => array('label' => 'Todo', 'bgcolor' => '#000000'),
            13 => array('label' => ''),
            14 => array('label' => 'Done', 'bgcolor' => '#16ed9d')))->build();
        stub($this->dao)->save($this->tracker_id, 12, 'Todo', 0, 0, 0)->at(0);
        stub($this->dao)->save($this->tracker_id, 14, 'Done', 22, 237, 157)->at(1);
        stub($this->dao)->save()->count(2);
        $this->command->execute($request);
    }
}
