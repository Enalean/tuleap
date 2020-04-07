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
abstract class Cardwall_OnTop_Config_Command_UpdateMappingFieldsTestBase extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker_id = 666;
        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturns($this->tracker_id);

        $this->task_tracker = \Mockery::spy(\Tracker::class);
        $this->task_tracker->shouldReceive('getId')->andReturns(42);

        $this->story_tracker = \Mockery::spy(\Tracker::class);
        $this->story_tracker->shouldReceive('getId')->andReturns(69);

        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(42)->andReturns($this->task_tracker);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(69)->andReturns($this->story_tracker);

        $this->status_field   = $this->buildField(123, $this->task_tracker);
        $this->assignto_field = $this->buildField(321, $this->story_tracker);
        $this->stage_field    = $this->buildField(322, $this->story_tracker);

        $this->element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->element_factory->shouldReceive('getFieldById')->with(123)->andReturns($this->status_field);
        $this->element_factory->shouldReceive('getFieldById')->with(321)->andReturns($this->assignto_field);
        $this->element_factory->shouldReceive('getFieldById')->with(322)->andReturns($this->stage_field);

        $existing_mappings = array(
            42 => new Cardwall_OnTop_Config_TrackerMappingStatus($this->task_tracker, array(), array(), $this->status_field),
            69 => new Cardwall_OnTop_Config_TrackerMappingFreestyle($this->story_tracker, array(), array(), $this->stage_field),
        );

        $this->dao       = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->value_dao = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $this->command = new Cardwall_OnTop_Config_Command_UpdateMappingFields(
            $this->tracker,
            $this->dao,
            $this->value_dao,
            $this->tracker_factory,
            $this->element_factory,
            $existing_mappings
        );
    }

    private function buildField(int $id, Tracker $tracker): Tracker_FormElement_Field
    {
        $field = Mockery::spy(Tracker_FormElement_Field::class);
        $field->shouldReceive('getId')->andReturn($id);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        return $field;
    }
}
