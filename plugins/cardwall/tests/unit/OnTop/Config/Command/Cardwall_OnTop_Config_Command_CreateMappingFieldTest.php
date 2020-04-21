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
final class Cardwall_OnTop_Config_Command_CreateMappingFieldTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturns($this->tracker_id);

        $task_tracker = \Mockery::spy(\Tracker::class);
        $task_tracker->shouldReceive('getId')->andReturns(42);

        $tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackerById')->with('42')->andReturns($task_tracker);

        $this->dao     = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->command = new Cardwall_OnTop_Config_Command_CreateMappingField($tracker, $this->dao, $tracker_factory);
    }

    public function testItCreatesANewMappingField(): void
    {
        $request = new HTTPRequest();
        $request->set('add_mapping_on', '42');
        $this->dao->shouldReceive('create')->with($this->tracker_id, 42, null)->once();
        $this->command->execute($request);
    }
}
