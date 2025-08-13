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
use Cardwall_OnTop_Config_Command_DeleteMappingFields;
use Cardwall_OnTop_Config_TrackerMappingFreestyle;
use Cardwall_OnTop_Config_TrackerMappingNoField;
use HTTPRequest;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use TrackerFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_Command_DeleteMappingFieldsTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private int $tracker_id;
    private Cardwall_OnTop_ColumnMappingFieldDao&MockObject $dao;
    private Cardwall_OnTop_ColumnMappingFieldValueDao&MockObject $value_dao;
    private Cardwall_OnTop_Config_Command_DeleteMappingFields $command;

    protected function setUp(): void
    {
        $this->tracker_id = 666;
        $tracker          = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();
        $bug_tracker      = TrackerTestBuilder::aTracker()->withId(13)->build();
        $task_tracker     = TrackerTestBuilder::aTracker()->withId(42)->build();
        $story_tracker    = TrackerTestBuilder::aTracker()->withId(69)->build();

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturnCallback(fn(int $tracker_id) => match ($tracker_id) {
            13      => $bug_tracker,
            42      => $task_tracker,
            69      => $story_tracker,
            default => throw new LogicException("Should not have been called with $tracker_id"),
        });

        $existing_mappings = [
            13 => new Cardwall_OnTop_Config_TrackerMappingNoField($bug_tracker, []),
            42 => new Cardwall_OnTop_Config_TrackerMappingNoField($task_tracker, []),
            69 => new Cardwall_OnTop_Config_TrackerMappingFreestyle($story_tracker, [], [], SelectboxFieldBuilder::aSelectboxField(186)->build()),
        ];
        $this->dao         = $this->createMock(Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->value_dao   = $this->createMock(Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $this->command     = new Cardwall_OnTop_Config_Command_DeleteMappingFields($tracker, $this->dao, $this->value_dao, $tracker_factory, $existing_mappings);
    }

    public function testItDeletesOnlyCustomMappings(): void
    {
        $request = new HTTPRequest();
        $request->set('custom_mapping', ['13' => '1', '42' => 0, '69' => 0]);
        $this->dao->expects($this->once())->method('delete')->with($this->tracker_id, 69)->willReturn(true);
        $this->value_dao->expects($this->once())->method('delete')->with($this->tracker_id, 69);
        $this->command->execute($request);
    }
}
