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

class Cardwall_OnTop_Config_Command_DeleteColumnsTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = stub('Tracker')->getId()->returns($this->tracker_id);

        $this->field_dao = mock('Cardwall_OnTop_ColumnMappingFieldDao');
        $this->value_dao = mock('Cardwall_OnTop_ColumnMappingFieldValueDao');

        $this->dao     = mock('Cardwall_OnTop_ColumnDao');
        $this->command = new Cardwall_OnTop_Config_Command_DeleteColumns($tracker, $this->dao, $this->field_dao, $this->value_dao);
    }

    public function itDeletesOneColumn()
    {
        $request = aRequest()->with('column', array(
            12 => array('label' => 'Todo'),
            14 => array('label' => '')))->build();
        stub($this->field_dao)->deleteCardwall()->never();
        stub($this->value_dao)->deleteForColumn($this->tracker_id, 14)->once();
        stub($this->dao)->delete($this->tracker_id, 14)->once();
        stub($this->dao)->delete()->count(1);
        $this->command->execute($request);
    }

    public function itDeletes2Columns()
    {
        $request = aRequest()->with('column', array(
            12 => array('label' => 'Todo'),
            13 => array('label' => ''),
            14 => array('label' => '')))->build();
        stub($this->field_dao)->deleteCardwall()->never();
        stub($this->value_dao)->deleteForColumn($this->tracker_id, 13)->at(0);
        stub($this->value_dao)->deleteForColumn($this->tracker_id, 14)->at(1);
        stub($this->value_dao)->deleteForColumn()->count(2);
        stub($this->dao)->delete($this->tracker_id, 13)->at(0);
        stub($this->dao)->delete($this->tracker_id, 14)->at(1);
        stub($this->dao)->delete()->count(2);
        $this->command->execute($request);
    }

    public function itDeleteFieldMappingWhenRemoveTheLastColumn()
    {
        $request = aRequest()->with('column', array(
            14 => array('label' => '')))->build();
        stub($this->field_dao)->deleteCardwall($this->tracker_id)->once();
        stub($this->value_dao)->deleteForColumn($this->tracker_id, 14)->once();
        stub($this->dao)->delete($this->tracker_id, 14)->once();
        $this->command->execute($request);
    }

    public function itDeletesAllColumns()
    {
        $request = aRequest()->with(
            'column',
            array(
                12 => array('label' => ''),
                13 => array('label' => ''),
            )
        )->build();
        stub($this->field_dao)->deleteCardwall($this->tracker_id)->once();
        stub($this->value_dao)->deleteForColumn($this->tracker_id, 12)->at(0);
        stub($this->value_dao)->deleteForColumn($this->tracker_id, 13)->at(1);
        stub($this->value_dao)->deleteForColumn()->count(2);
        stub($this->dao)->delete($this->tracker_id, 12)->at(0);
        stub($this->dao)->delete($this->tracker_id, 13)->at(1);
        stub($this->dao)->delete()->count(2);
        $this->command->execute($request);
    }
}
