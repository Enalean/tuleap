<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Membership\DynamicUGroups;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;

class ProjectMemberAdderWithoutStatusCheckAndNotificationsTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock, ForgeConfigSandbox, GlobalResponseMock;

    /**
     * @var M\MockInterface|AddProjectMember
     */
    private $add_project_member;
    /**
     * @var ProjectMemberAdderWithStatusCheckAndNotifications
     */
    private $project_member_adder;
    /**
     * @var \Project
     */
    private $an_active_project;

    protected function setUp(): void
    {
        $this->add_project_member = M::mock(AddProjectMember::class);

        $this->an_active_project = M::mock(\Project::class, ['getID' => 202, 'getPublicName' => 'A project name', 'getUnixName' => 'a-project-name']);

        $this->project_member_adder = new ProjectMemberAdderWithoutStatusCheckAndNotifications($this->add_project_member);
    }

    public function testItAddsActiveUsers(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);
        $this->add_project_member->shouldReceive('addProjectMember')->with($user, $this->an_active_project)->once();

        $GLOBALS['Response']->shouldNotReceive('addFeedback');

        $this->project_member_adder->addProjectMember($user, $this->an_active_project);
    }

    public function testItDisplaysAnErrorWhenRestrictedUserIsAddedToWoRestrictedProject(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);
        $this->add_project_member->shouldReceive('addProjectMember')->andThrow(new CannotAddRestrictedUserToProjectNotAllowingRestricted($user, $this->an_active_project));

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any())->once();

        $this->project_member_adder->addProjectMember($user, $this->an_active_project);
    }

    public function testItDisplaysAnErrorWhenUserIsAlreadyMember(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);
        $this->add_project_member->shouldReceive('addProjectMember')->andThrow(new AlreadyProjectMemberException());

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any())->once();

        $this->project_member_adder->addProjectMember($user, $this->an_active_project);
    }
}
