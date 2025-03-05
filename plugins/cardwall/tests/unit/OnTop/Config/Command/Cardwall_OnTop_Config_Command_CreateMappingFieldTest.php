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
use Cardwall_OnTop_Config_Command_CreateMappingField;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use TrackerFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_Command_CreateMappingFieldTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private int $tracker_id;
    private Cardwall_OnTop_ColumnMappingFieldDao&MockObject $dao;
    private Cardwall_OnTop_Config_Command_CreateMappingField $command;

    protected function setUp(): void
    {
        $this->tracker_id = 666;
        $tracker          = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();
        $task_tracker     = TrackerTestBuilder::aTracker()->withId(42)->build();

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->with(42)->willReturn($task_tracker);

        $this->dao     = $this->createMock(Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->command = new Cardwall_OnTop_Config_Command_CreateMappingField($tracker, $this->dao, $tracker_factory);
    }

    public function testItCreatesANewMappingField(): void
    {
        $request = new HTTPRequest();
        $request->set('add_mapping_on', '42');
        $this->dao->expects(self::once())->method('create')->with($this->tracker_id, 42, null);
        $this->command->execute($request);
    }
}
