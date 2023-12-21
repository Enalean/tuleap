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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectMemberAdderWithoutStatusCheckAndNotificationsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;
    use GlobalResponseMock;

    private AddProjectMember&MockObject $add_project_member;
    private ProjectMemberAdderWithoutStatusCheckAndNotifications $project_member_adder;
    private \Project $an_active_project;
    private \PFUser $project_admin;

    protected function setUp(): void
    {
        $this->add_project_member = $this->createMock(AddProjectMember::class);
        $this->project_admin      = UserTestBuilder::buildWithDefaults();

        $this->an_active_project = ProjectTestBuilder::aProject()
            ->withId(202)
            ->withPublicName('A project name')
            ->withUnixName('a-project-name')
            ->build();

        $this->project_member_adder = new ProjectMemberAdderWithoutStatusCheckAndNotifications($this->add_project_member);
    }

    public function testItAddsActiveUsers(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);
        $this->add_project_member->expects(self::once())->method('addProjectMember')->with($user, $this->an_active_project, $this->project_admin);

        $GLOBALS['Response']->expects(self::never())->method('addFeedback');

        $this->project_member_adder->addProjectMemberWithFeedback($user, $this->an_active_project, $this->project_admin);
    }

    public function testItDisplaysAnErrorWhenRestrictedUserIsAddedToWoRestrictedProject(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);
        $this->add_project_member->method('addProjectMember')->willThrowException(new CannotAddRestrictedUserToProjectNotAllowingRestricted($user, $this->an_active_project));

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(\Feedback::ERROR);

        $this->project_member_adder->addProjectMemberWithFeedback($user, $this->an_active_project, $this->project_admin);
    }

    public function testItDisplaysAnErrorWhenUserIsAlreadyMember(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);
        $this->add_project_member->method('addProjectMember')->willThrowException(new AlreadyProjectMemberException());

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(\Feedback::ERROR);

        $this->project_member_adder->addProjectMemberWithFeedback($user, $this->an_active_project, $this->project_admin);
    }
}
