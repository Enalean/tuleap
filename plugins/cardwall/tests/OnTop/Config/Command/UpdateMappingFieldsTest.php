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

abstract class Cardwall_OnTop_Config_Command_UpdateMappingFieldsTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->tracker_id = 666;
        $this->tracker = mock('Tracker');
        stub($this->tracker)->getId()->returns($this->tracker_id);

        $this->task_tracker = mock('Tracker');
        stub($this->task_tracker)->getId()->returns(42);

        $this->story_tracker = mock('Tracker');
        stub($this->story_tracker)->getId()->returns(69);

        $this->tracker_factory = mock('TrackerFactory');
        stub($this->tracker_factory)->getTrackerById(42)->returns($this->task_tracker);
        stub($this->tracker_factory)->getTrackerById(69)->returns($this->story_tracker);

        $this->status_field   = aMockField()->withId(123)->withTracker($this->task_tracker)->build();
        $this->assignto_field = aMockField()->withId(321)->withTracker($this->story_tracker)->build();
        $this->stage_field    = aMockField()->withId(322)->withTracker($this->story_tracker)->build();

        $this->element_factory = mock('Tracker_FormElementFactory');
        stub($this->element_factory)->getFieldById(123)->returns($this->status_field);
        stub($this->element_factory)->getFieldById(321)->returns($this->assignto_field);
        stub($this->element_factory)->getFieldById(322)->returns($this->stage_field);

        $existing_mappings = array(
            42 => new Cardwall_OnTop_Config_TrackerMappingStatus($this->task_tracker, array(), array(), $this->status_field),
            69 => new Cardwall_OnTop_Config_TrackerMappingFreestyle($this->story_tracker, array(), array(), $this->stage_field),
        );

        $this->dao       = mock('Cardwall_OnTop_ColumnMappingFieldDao');
        $this->value_dao = mock('Cardwall_OnTop_ColumnMappingFieldValueDao');
        $this->command = new Cardwall_OnTop_Config_Command_UpdateMappingFields(
            $this->tracker,
            $this->dao,
            $this->value_dao,
            $this->tracker_factory,
            $this->element_factory,
            $existing_mappings
        );
    }
}

class Cardwall_OnTop_Config_Command_UpdateMappingFields_UpdateFieldTest extends Cardwall_OnTop_Config_Command_UpdateMappingFieldsTest
{

    public function itUpdatesMappingFields()
    {
        $request = aRequest()->with(
            'mapping_field',
            array(
                '42' => array(
                    'field' => '123',
                ),
                '69' => array(
                    'field' => '321',
                ),
            )
        )->build();
        stub($this->dao)->searchMappingFields($this->tracker_id)->returns(
            TestHelper::arrayToDar(
                array(
                    'cardwall_tracker_id' => 666,
                    'tracker_id'          => 42,
                    'field_id'            => 100
                ),
                array(
                    'cardwall_tracker_id' => 666,
                    'tracker_id'          => 69,
                    'field_id'            => null
                )
            )
        );
        stub($this->dao)->save($this->tracker_id, 42, 123)->at(0)->returns(true);
        stub($this->dao)->save($this->tracker_id, 69, 321)->at(1)->returns(true);
        stub($this->dao)->save()->count(2);
        $this->command->execute($request);
    }

    public function itDoesntUpdatesMappingFieldsIfItIsNotNeeded()
    {
        $request = aRequest()->with(
            'mapping_field',
            array(
                '42' => array(
                    'field' => '123',
                ),
                '69' => array(
                    'field' => '322',
                ),
            )
        )->build();
        stub($this->dao)->searchMappingFields($this->tracker_id)->returns(
            TestHelper::arrayToDar(
                array(
                    'cardwall_tracker_id' => 666,
                    'tracker_id'          => 42,
                    'field_id'            => 123
                ),
                array(
                    'cardwall_tracker_id' => 666,
                    'tracker_id'          => 69,
                    'field_id'            => 321
                )
            )
        );
        stub($this->dao)->save($this->tracker_id, 69, 322)->once();
        $this->command->execute($request);
    }
}

class Cardwall_OnTop_Config_Command_UpdateMappingFields_UpdateValuesTest extends Cardwall_OnTop_Config_Command_UpdateMappingFieldsTest
{

    public function itUpdatesMappingFieldValues()
    {
        $request = aRequest()->with(
            'mapping_field',
            array(
                '69' => array(
                    'field' => '321',
                    'values' => array(
                        '11' => array(
                            '9001',
                            '9002'
                        ),
                    )
                ),
            )
        )->build();
        stub($this->dao)->searchMappingFields($this->tracker_id)->returns(
            TestHelper::arrayToDar(
                array(
                    'cardwall_tracker_id' => 666,
                    'tracker_id'          => 69,
                    'field_id'            => 321
                )
            )
        );
        stub($this->value_dao)->deleteAllFieldValues($this->tracker_id, 69, 321, 11)->once();
        stub($this->value_dao)->save($this->tracker_id, 69, 321, 9001, 11)->at(0);
        stub($this->value_dao)->save($this->tracker_id, 69, 321, 9002, 11)->at(1);
        stub($this->value_dao)->save()->count(2);
        $this->command->execute($request);
    }

    public function itDoesntUpdateMappingValuesIfTheFieldChange()
    {
        $request = aRequest()->with(
            'mapping_field',
            array(
                '69' => array(
                    'field' => '321',
                    'values' => array(
                        '11' => array(
                            '9001',
                            '9002'
                        ),
                    )
                ),
            )
        )->build();
        stub($this->dao)->searchMappingFields($this->tracker_id)->returns(
            TestHelper::arrayToDar(
                array(
                    'cardwall_tracker_id' => 666,
                    'tracker_id'          => 69,
                    'field_id'            => 666,
                )
            )
        );
        stub($this->dao)->save()->returns(true);
        stub($this->value_dao)->delete($this->tracker_id, 69)->once();
        stub($this->value_dao)->deleteAllFieldValues()->never();
        stub($this->value_dao)->save()->never();
        $this->command->execute($request);
    }
}

class Cardwall_OnTop_Config_Command_UpdateMappingFields_UpdateValuesNoUpdateTest extends Cardwall_OnTop_Config_Command_UpdateMappingFieldsTest
{
    public function setUp()
    {
        parent::setUp();

        stub($this->dao)->searchMappingFields($this->tracker_id)->returns(
            TestHelper::arrayToDar(
                array(
                    'cardwall_tracker_id' => 666,
                    'tracker_id'          => 69,
                    'field_id'            => 321
                )
            )
        );

        $existing_mappings = array(
            42 => new Cardwall_OnTop_Config_TrackerMappingStatus($this->task_tracker, array(), array(), $this->status_field),
            69 => new Cardwall_OnTop_Config_TrackerMappingFreestyle(
                $this->story_tracker,
                array(),
                array(
                    9001 => new Cardwall_OnTop_Config_ValueMapping(aFieldListStaticValue()->withId(9001)->build(), 11),
                    9002 => new Cardwall_OnTop_Config_ValueMapping(aFieldListStaticValue()->withId(9002)->build(), 11),
                    9007 => new Cardwall_OnTop_Config_ValueMapping(aFieldListStaticValue()->withId(9002)->build(), 12),
                ),
                $this->stage_field
            ),
        );

        $this->command = new Cardwall_OnTop_Config_Command_UpdateMappingFields(
            $this->tracker,
            $this->dao,
            $this->value_dao,
            $this->tracker_factory,
            $this->element_factory,
            $existing_mappings
        );
    }

    public function itDoesntUpdatesMappingFieldValuesWhenMappingDoesntChange()
    {
        $request = aRequest()->with(
            'mapping_field',
            array(
                '69' => array(
                    'field' => '321',
                    'values' => array(
                        '11' => array(
                            '9001',
                            '9002'
                        ),
                    )
                ),
            )
        )->build();
        stub($this->value_dao)->deleteAllFieldValues()->never();
        stub($this->value_dao)->save()->never();
        $this->command->execute($request);
    }

    public function itUpdatesMappingFieldValuesWhenThereIsANewValue()
    {
        $request = aRequest()->with(
            'mapping_field',
            array(
                '69' => array(
                    'field' => '321',
                    'values' => array(
                        '11' => array(
                            '9001',
                            '9002',
                            '9003'
                        ),
                    )
                ),
            )
        )->build();
        stub($this->value_dao)->deleteAllFieldValues()->once();
        stub($this->value_dao)->save()->count(3);
        $this->command->execute($request);
    }

    public function itUpdatesMappingFieldValuesWhenAValueIsRemoved()
    {
        $request = aRequest()->with(
            'mapping_field',
            array(
                '69' => array(
                    'field' => '321',
                    'values' => array(
                        '11' => array(
                            '9001',
                        ),
                    )
                ),
            )
        )->build();
        stub($this->value_dao)->deleteAllFieldValues()->once();
        stub($this->value_dao)->save()->once();
        $this->command->execute($request);
    }
}
