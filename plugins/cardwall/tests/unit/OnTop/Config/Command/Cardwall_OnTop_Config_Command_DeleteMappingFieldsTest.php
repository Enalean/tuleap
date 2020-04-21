<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Cardwall_OnTop_Config_Command_DeleteMappingFieldsTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturns($this->tracker_id);

        $bug_tracker = \Mockery::spy(\Tracker::class);
        $bug_tracker->shouldReceive('getId')->andReturns(13);

        $task_tracker = \Mockery::spy(\Tracker::class);
        $task_tracker->shouldReceive('getId')->andReturns(42);

        $story_tracker = \Mockery::spy(\Tracker::class);
        $story_tracker->shouldReceive('getId')->andReturns(69);

        $tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackerById')->with(13)->andReturns($bug_tracker);
        $tracker_factory->shouldReceive('getTrackerById')->with(42)->andReturns($task_tracker);
        $tracker_factory->shouldReceive('getTrackerById')->with(69)->andReturns($story_tracker);

        $existing_mappings = array(
            13 => new Cardwall_OnTop_Config_TrackerMappingNoField($bug_tracker, array()),
            42 => new Cardwall_OnTop_Config_TrackerMappingNoField($task_tracker, array()),
            69 => new Cardwall_OnTop_Config_TrackerMappingFreestyle($story_tracker, array(), array(), Mockery::mock(Tracker_FormElement_Field_Selectbox::class)),
        );
        $this->dao       = \Mockery::mock(\Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->value_dao = \Mockery::mock(\Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $this->command   = new Cardwall_OnTop_Config_Command_DeleteMappingFields($tracker, $this->dao, $this->value_dao, $tracker_factory, $existing_mappings);
    }

    public function testItDeletesOnlyCustomMappings(): void
    {
        $request = new HTTPRequest();
        $request->set('custom_mapping', array('13' => '1', '42' => 0, '69' => 0));
        $this->dao->shouldReceive('delete')->with($this->tracker_id, 69)->once()->andReturns(true);
        $this->value_dao->shouldReceive('delete')->with($this->tracker_id, 69)->once();
        $this->command->execute($request);
    }
}
