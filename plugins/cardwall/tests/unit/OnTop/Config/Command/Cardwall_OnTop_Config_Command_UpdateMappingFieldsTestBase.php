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

namespace Tuleap\Cardwall\OnTop\Config\Command;

use Cardwall_OnTop_ColumnMappingFieldDao;
use Cardwall_OnTop_ColumnMappingFieldValueDao;
use Cardwall_OnTop_Config_Command_UpdateMappingFields;
use Cardwall_OnTop_Config_TrackerMappingFreestyle;
use Cardwall_OnTop_Config_TrackerMappingStatus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

abstract class Cardwall_OnTop_Config_Command_UpdateMappingFieldsTestBase extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    protected int $tracker_id;
    protected Tracker $tracker;
    protected Tracker $task_tracker;
    protected Tracker $story_tracker;
    protected TrackerFactory&MockObject $tracker_factory;
    protected TrackerField $status_field;
    protected TrackerField $assignto_field;
    protected TrackerField $stage_field;
    protected Tracker_FormElementFactory&MockObject $element_factory;
    protected Cardwall_OnTop_ColumnMappingFieldValueDao&MockObject $value_dao;
    protected Cardwall_OnTop_Config_Command_UpdateMappingFields $command;
    protected Cardwall_OnTop_ColumnMappingFieldDao&MockObject $dao;

    #[Before]
    protected function buildCommand(): void
    {
        $this->tracker_id    = 666;
        $this->tracker       = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();
        $this->task_tracker  = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->story_tracker = TrackerTestBuilder::aTracker()->withId(69)->build();

        $this->tracker_factory = $this->createMock(TrackerFactory::class);
        $this->tracker_factory->method('getTrackerById')->willReturnCallback(fn(int $tracker_id) => match ($tracker_id) {
            42      => $this->task_tracker,
            69      => $this->story_tracker,
            default => self::fail("Should not have been called with $tracker_id"),
        });

        $this->status_field   = $this->buildField(123, $this->task_tracker);
        $this->assignto_field = $this->buildField(321, $this->story_tracker);
        $this->stage_field    = $this->buildField(322, $this->story_tracker);

        $this->element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->element_factory->method('getFieldById')->willReturnCallback(fn(int $field_id) => match ($field_id) {
            123     => $this->status_field,
            321     => $this->assignto_field,
            322     => $this->stage_field,
            default => self::fail("Should not have been called with $field_id"),
        });

        $existing_mappings = [
            42 => new Cardwall_OnTop_Config_TrackerMappingStatus($this->task_tracker, [], [], $this->status_field),
            69 => new Cardwall_OnTop_Config_TrackerMappingFreestyle($this->story_tracker, [], [], $this->stage_field),
        ];

        $this->dao       = $this->createMock(Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->value_dao = $this->createMock(Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $this->command   = new Cardwall_OnTop_Config_Command_UpdateMappingFields(
            $this->tracker,
            $this->dao,
            $this->value_dao,
            $this->tracker_factory,
            $this->element_factory,
            $existing_mappings
        );
    }

    private function buildField(int $id, Tracker $tracker): TrackerField
    {
        return IntegerFieldBuilder::anIntField($id)->inTracker($tracker)->build();
    }
}
