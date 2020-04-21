<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\svn\Event;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class UpdateProjectAccessFilesSchedulerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\SystemEventManager
     */
    private $system_event_manager;

    /**
     * @var UpdateProjectAccessFilesScheduler
     */
    private $scheduler;

    protected function setUp(): void
    {
        $this->system_event_manager = \Mockery::mock(\SystemEventManager::class);

        $this->scheduler = new UpdateProjectAccessFilesScheduler($this->system_event_manager);
    }

    public function testAnUpdateCanBeScheduled(): void
    {
        $this->system_event_manager->shouldReceive('areThereMultipleEventsQueuedMatchingFirstParameter')->andReturn(false);

        $this->system_event_manager->shouldReceive('createEvent')->once();

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);

        $this->scheduler->scheduleUpdateOfProjectAccessFiles($project);
    }

    public function testAnUpdateIsNotScheduledWhenThereIsAlreadyOneWaitingToBeExecuted(): void
    {
        $this->system_event_manager->shouldReceive('areThereMultipleEventsQueuedMatchingFirstParameter')->andReturn(true);

        $this->system_event_manager->shouldReceive('createEvent')->never();

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(103);

        $this->scheduler->scheduleUpdateOfProjectAccessFiles($project);
    }
}
