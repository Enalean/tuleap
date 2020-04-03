<?php
/**
 * Copyright (c) Enalean, 2015 - 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\Transition\Condition;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Transition;
use Workflow_Transition_Condition_Permissions;

require_once __DIR__ . '/../../../../bootstrap.php';

class PermissionsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $permission_manager;
    private $condition;
    private $user;
    private $transition;
    private $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(101);

        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getGroupId')->andReturn(103);

        $this->permission_manager = Mockery::mock(PermissionsManager::class);
        $this->permission_manager->shouldReceive('getAuthorizedUgroups')
            ->with(
                303,
                Workflow_Transition_Condition_Permissions::PERMISSION_TRANSITION
            )
            ->andReturn([['ugroup_id' => 404]]);

        PermissionsManager::setInstance($this->permission_manager);

        $this->transition = Mockery::mock(Transition::class);
        $this->transition->shouldReceive('getId')->andReturn(303);

        $this->condition  = new Workflow_Transition_Condition_Permissions($this->transition);
    }

    protected function tearDown(): void
    {
        PermissionsManager::clearInstance();

        parent::tearDown();
    }

    public function testItReturnsTrueIfUserCanSeeTransition()
    {
        $this->user->shouldReceive('isMemberOfUGroup')->andReturn(true);
        $this->tracker->shouldReceive('userIsAdmin')->with($this->user)->andReturn(false);

        $this->assertTrue($this->condition->isUserAllowedToSeeTransition($this->user, $this->tracker));
    }

    public function testItReturnsFalseIfUserCannotSeeTransition()
    {
        $this->user->shouldReceive('isMemberOfUGroup')->andReturn(false);
        $this->tracker->shouldReceive('userIsAdmin')->with($this->user)->andReturn(false);

        $this->assertFalse($this->condition->isUserAllowedToSeeTransition($this->user, $this->tracker));
    }

    public function testItReturnsTrueIfUserCanAdministrateTracker()
    {
        $this->tracker->shouldReceive('userIsAdmin')->with($this->user)->andReturn(true);

        $this->assertTrue($this->condition->isUserAllowedToSeeTransition($this->user, $this->tracker));
    }
}
