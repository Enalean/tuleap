<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\Transition\Condition;

use PermissionsManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Workflow_WorkflowUser;
use Transition;
use Tuleap\Tracker\Tracker;
use Workflow_Transition_Condition_Permissions;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PermissionsManager&MockObject $permission_manager;
    private Workflow_Transition_Condition_Permissions $condition;
    private PFUser&MockObject $user;
    private Tracker_Workflow_WorkflowUser $workflow_user;
    private Transition&MockObject $transition;
    private Tracker&MockObject $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createMock(PFUser::class);

        $this->workflow_user = new Tracker_Workflow_WorkflowUser();
        $this->user->method('getId')->willReturn(101);

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getGroupId')->willReturn(103);

        $this->permission_manager = $this->createMock(PermissionsManager::class);
        $this->permission_manager->method('getAuthorizedUgroups')
            ->with(
                303,
                Workflow_Transition_Condition_Permissions::PERMISSION_TRANSITION
            )
            ->willReturn([['ugroup_id' => 404]]);

        PermissionsManager::setInstance($this->permission_manager);

        $this->transition = $this->createMock(Transition::class);
        $this->transition->method('getId')->willReturn(303);

        $this->condition = new Workflow_Transition_Condition_Permissions($this->transition);
    }

    protected function tearDown(): void
    {
        PermissionsManager::clearInstance();

        parent::tearDown();
    }

    public function testItReturnsTrueIfUserCanSeeTransition(): void
    {
        $this->user->method('isMemberOfUGroup')->willReturn(true);
        $this->tracker->method('userIsAdmin')->with($this->user)->willReturn(false);

        $this->assertTrue($this->condition->isUserAllowedToSeeTransition($this->user, $this->tracker));
    }

    public function testItReturnsFalseIfUserCannotSeeTransition(): void
    {
        $this->user->method('isMemberOfUGroup')->willReturn(false);
            $this->tracker->method('userIsAdmin')->with($this->user)->willReturn(false);

        $this->assertFalse($this->condition->isUserAllowedToSeeTransition($this->user, $this->tracker));
    }

    public function testItReturnsTrueIfUserCanAdministrateTracker(): void
    {
        $this->tracker->expects($this->once())->method('userIsAdmin')->with($this->user)->willReturn(true);

        $this->assertTrue($this->condition->isUserAllowedToSeeTransition($this->user, $this->tracker));
    }

    public function testItReturnsTrueIfWorkFlowTrackerUserCanAdministrateTracker(): void
    {
        $this->tracker->expects($this->never())->method('userIsAdmin')->with($this->workflow_user);

        $this->assertTrue($this->condition->isUserAllowedToSeeTransition($this->workflow_user, $this->tracker));
    }
}
