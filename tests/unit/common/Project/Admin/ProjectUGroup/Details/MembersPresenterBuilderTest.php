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
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\ProjectUGroup\Details;

use Event;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Admin\ProjectUGroup\ProjectUGroupMemberUpdatable;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class MembersPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private MembersPresenterBuilder $builder;
    private \EventManager&MockObject $event_manager;
    private \UserHelper&MockObject $user_helper;
    private SynchronizedProjectMembershipDetector&MockObject $detector;

    protected function setUp(): void
    {
        $this->event_manager = $this->createMock(\EventManager::class);
        $this->user_helper   = $this->createMock(\UserHelper::class);
        $this->detector      = $this->createMock(SynchronizedProjectMembershipDetector::class);
        $this->builder       = new MembersPresenterBuilder($this->event_manager, $this->user_helper, $this->detector);

        $this->event_manager->method('processEvent');
    }

    public function testItDoesNotAllowUpdatingBoundUGroups(): void
    {
        $ugroup = $this->getEmptyBoundStaticUGroup();
        $this->detector
            ->method('isSynchronizedWithProjectMembers')
            ->willReturn(false);

        $result = $this->builder->build($ugroup);

        self::assertFalse($result->can_be_updated);
    }

    public function testItDoesNotAllowUpdatingUGroupsAccordingToEvent(): void
    {
        $ugroup = $this->getEmptyStaticUGroup();
        $this->event_manager
            ->expects(self::exactly(2))
            ->method('processEvent')
            ->withConsecutive(
                [Event::UGROUP_UPDATE_USERS_ALLOWED, self::anything()],
                [self::isInstanceOf(ProjectUGroupMemberUpdatable::class)]
            )
            ->willReturnOnConsecutiveCalls(
                self::returnCallback(function (string $event_name, array $params): void {
                    $params['allowed'] = false;
                }),
                self::anything()
            );
        $this->detector
            ->method('isSynchronizedWithProjectMembers')
            ->willReturn(false);

        $result = $this->builder->build($ugroup);

        self::assertFalse($result->can_be_updated);
    }

    public function testItSetsTheUGroupAsDynamic(): void
    {
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('isBound')->willReturn(false);
        $ugroup->method('getId')->willReturn(98);
        $ugroup->method('isStatic')->willReturn(false);
        $ugroup->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $ugroup->method('getMembersIncludingSuspendedAndDeleted')->willReturn([]);
        $this->detector
            ->method('isSynchronizedWithProjectMembers')
            ->willReturn(false);

        $result = $this->builder->build($ugroup);

        self::assertTrue($result->is_dynamic_group);
    }

    public function testItSetsTheUGroupAsStatic(): void
    {
        $ugroup = $this->getEmptyStaticUGroup();
        $this->detector
            ->method('isSynchronizedWithProjectMembers')
            ->willReturn(false);

        $result = $this->builder->build($ugroup);

        self::assertFalse($result->is_dynamic_group);
    }

    public function testItShowsTheSynchronizedMembershipMessageWhenUGroupCanBeUpdated(): void
    {
        $ugroup = $this->getEmptyStaticUGroup();
        $this->detector
            ->expects(self::once())
            ->method('isSynchronizedWithProjectMembers')
            ->with($ugroup->getProject())
            ->willReturn(true);

        $result = $this->builder->build($ugroup);

        self::assertTrue($result->is_synchronized_message_shown);
    }

    public function testItDoesNotShowTheSynchronizedMembershipMessageWhenUGroupCannotBeUpdated(): void
    {
        $ugroup = $this->getEmptyBoundStaticUGroup();
        $this->detector
            ->expects(self::once())
            ->method('isSynchronizedWithProjectMembers')
            ->willReturn(true);

        $result = $this->builder->build($ugroup);

        self::assertFalse($result->is_synchronized_message_shown);
    }

    public function testItDoesNotShowTheSynchronizedMembershipMessageWhenItIsNotEnabled(): void
    {
        $ugroup = $this->getEmptyStaticUGroup();
        $this->detector
            ->expects(self::once())
            ->method('isSynchronizedWithProjectMembers')
            ->willReturn(false);

        $result = $this->builder->build($ugroup);

        self::assertFalse($result->is_synchronized_message_shown);
    }

    public function testItFormatsUGroupMembers(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $ugroup  = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('isBound')->willReturn(false);
        $ugroup->method('getId')->willReturn(98);
        $ugroup->method('isStatic')->willReturn(true);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('getProjectId')->willReturn(202);
        $first_member = UserTestBuilder::aUser()
            ->withUserName('jmost')
            ->withId(105)
            ->withRealName('Junko Most')
            ->withAvatarUrl('')
            ->withStatus('A')
            ->withoutSiteAdministrator()
            ->withMemberOf($project)
            ->build();
        $ugroup->method('getMembersIncludingSuspendedAndDeleted')->willReturn([$first_member]);
        $this->user_helper
            ->method('getDisplayName')
            ->with('jmost', 'Junko Most')
            ->willReturn('Junko Most (jmost)');
        $this->detector
            ->method('isSynchronizedWithProjectMembers')
            ->willReturn(false);

        $result = $this->builder->build($ugroup);

        $first_formatted_member = $result->members[0];
        self::assertSame('Junko Most (jmost)', $first_formatted_member->username_display);
        self::assertSame('/users/jmost/', $first_formatted_member->profile_page_url);
        self::assertTrue($first_formatted_member->has_avatar);
        self::assertSame('jmost', $first_formatted_member->user_name);
        self::assertSame(105, $first_formatted_member->user_id);
        self::assertTrue($first_formatted_member->is_member_updatable);
        self::assertEmpty($first_formatted_member->member_updatable_messages);
        self::assertFalse($first_formatted_member->is_news_admin);
        self::assertSame(0, $first_formatted_member->user_is_project_admin);
    }

    public function testItSetsMembersAsNotUpdatableAccordingToEvent(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $ugroup  = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('isBound')->willReturn(false);
        $ugroup->method('getId')->willReturn(98);
        $ugroup->method('isStatic')->willReturn(true);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('getProjectId')->willReturn(202);
        $first_member = UserTestBuilder::aUser()
            ->withUserName('jmost')
            ->withId(105)
            ->withRealName('Junko Most')
            ->withAvatarUrl('')
            ->withStatus('A')
            ->withoutSiteAdministrator()
            ->withMemberOf($project)
            ->build();
        $ugroup->method('getMembersIncludingSuspendedAndDeleted')->willReturn([$first_member]);

        $this->event_manager
            ->expects(self::exactly(2))
            ->method('processEvent')
            ->withConsecutive(
                [Event::UGROUP_UPDATE_USERS_ALLOWED, self::anything()],
                [self::isInstanceOf(ProjectUGroupMemberUpdatable::class)]
            )
            ->willReturn(
                self::anything(),
                self::returnCallback(function (ProjectUGroupMemberUpdatable $event) use ($first_member): void {
                    $event->markUserHasNotUpdatable($first_member, 'User cannot be updated');
                })
            );
        $this->user_helper->method('getDisplayName');
        $this->detector
            ->method('isSynchronizedWithProjectMembers')
            ->willReturn(false);

        $result = $this->builder->build($ugroup);

        $first_formatted_member = $result->members[0];
        self::assertFalse($first_formatted_member->is_member_updatable);
        self::assertContains('User cannot be updated', $first_formatted_member->member_updatable_messages);
    }

    public function testItSetsMemberIsNewsAdmin(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $ugroup  = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('isBound')->willReturn(false);
        $ugroup->method('getId')->willReturn(\ProjectUGroup::NEWS_WRITER);
        $ugroup->method('getProjectId')->willReturn(180);
        $ugroup->method('isStatic')->willReturn(false);
        $ugroup->method('getProject')->willReturn($project);
        $first_member = $this->createMock(\PFUser::class);
        $first_member->method('getUserName')->willReturn('jmost');
        $first_member->method('getId')->willReturn(105);
        $first_member->method('getRealName')->willReturn('Junko Most');
        $first_member->method('hasAvatar')->willReturn(true);
        $first_member->method('isAdmin')->willReturn(false);
        $first_member->method('getStatus')->willReturn('A');
        $first_member->method('getAvatarUrl')->willReturn('');
        $first_member->method('isMember')->with(180, 'N2')->willReturn(true);
        $ugroup->method('getMembersIncludingSuspendedAndDeleted')->willReturn([$first_member]);
        $this->user_helper->method('getDisplayName');
        $this->detector
            ->method('isSynchronizedWithProjectMembers')
            ->willReturn(false);

        $result = $this->builder->build($ugroup);

        $first_formatted_member = $result->members[0];
        self::assertTrue($first_formatted_member->is_news_admin);
    }

    private function getEmptyBoundStaticUGroup(): \ProjectUGroup
    {
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('isBound')->willReturn(true);
        $ugroup->method('getId')->willReturn(98);
        $ugroup->method('isStatic')->willReturn(true);
        $ugroup->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $ugroup->method('getMembersIncludingSuspendedAndDeleted')->willReturn([]);

        return $ugroup;
    }

    private function getEmptyStaticUGroup(): \ProjectUGroup
    {
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('isBound')->willReturn(false);
        $ugroup->method('getId')->willReturn(98);
        $ugroup->method('isStatic')->willReturn(true);
        $ugroup->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $ugroup->method('getMembersIncludingSuspendedAndDeleted')->willReturn([]);

        return $ugroup;
    }
}
