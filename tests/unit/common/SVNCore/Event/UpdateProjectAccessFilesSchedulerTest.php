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

namespace Tuleap\SVNCore\Event;

use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class UpdateProjectAccessFilesSchedulerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \SystemEventManager&MockObject
     */
    private $system_event_manager;

    /**
     * @var UpdateProjectAccessFilesScheduler
     */
    private $scheduler;

    protected function setUp(): void
    {
        $this->system_event_manager = $this->createMock(\SystemEventManager::class);

        $this->scheduler = new UpdateProjectAccessFilesScheduler($this->system_event_manager);
    }

    public function testAnUpdateCanBeScheduled(): void
    {
        $this->system_event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter')->willReturnMap([
            [SystemEvent::TYPE_SVN_UPDATE_PROJECT_ACCESS_FILES, "102", false],
            [SystemEvent::TYPE_UGROUP_MODIFY, "102", false],
        ]);

        $this->system_event_manager->expects(self::once())->method('createEvent');

        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $this->scheduler->scheduleUpdateOfProjectAccessFiles($project);
    }

    public function testAnUpdateIsNotScheduledWhenThereIsAlreadyOneWaitingToBeExecuted(): void
    {
        $this->system_event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter')->willReturnMap([
            [SystemEvent::TYPE_SVN_UPDATE_PROJECT_ACCESS_FILES, "103", true],
            [SystemEvent::TYPE_UGROUP_MODIFY, "103", false],
        ]);

        $this->system_event_manager->expects(self::never())->method('createEvent');

        $project = ProjectTestBuilder::aProject()->withId(103)->build();

        $this->scheduler->scheduleUpdateOfProjectAccessFiles($project);
    }

    public function testNoUpdateScheduledWhenThereUGroupModifyAlreadyScheduledBecauseItWillAlsoQueueUpdateLaterOn(): void
    {
        $this->system_event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter')->willReturnMap([
            [SystemEvent::TYPE_SVN_UPDATE_PROJECT_ACCESS_FILES, "103", false],
            [SystemEvent::TYPE_UGROUP_MODIFY, "103", true],
        ]);

        $this->system_event_manager->expects(self::never())->method('createEvent');

        $project = ProjectTestBuilder::aProject()->withId(103)->build();

        $this->scheduler->scheduleUpdateOfProjectAccessFiles($project);
    }
}
