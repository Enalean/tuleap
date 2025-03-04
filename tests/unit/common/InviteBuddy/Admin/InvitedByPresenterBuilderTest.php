<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy\Admin;

use Tuleap\InviteBuddy\InvitationDao;
use Tuleap\InviteBuddy\InvitationTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvitedByPresenterBuilderTest extends TestCase
{
    private const CURRENT_USER_ID    = 101;
    private const ALICE_ID           = 102;
    private const BOB_ID             = 103;
    private const INVITEE_ID         = 104;
    private const A_PROJECT_ID       = 1001;
    private const ANOTHER_PROJECT_ID = 1002;

    private \UserHelper&\PHPUnit\Framework\MockObject\MockObject $user_helper;

    protected function setUp(): void
    {
        $this->user_helper = $this->createMock(\UserHelper::class);
        \UserHelper::setInstance($this->user_helper);
    }

    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testGetInvitedByPresenter(): void
    {
        $current_user = UserTestBuilder::aUser()
            ->withId(self::CURRENT_USER_ID)
            ->withoutMemberOfProjects()
            ->withSiteAdministrator()
            ->build();

        $alice = UserTestBuilder::aUser()->withId(self::ALICE_ID)->build();
        $bob   = UserTestBuilder::aUser()->withId(self::BOB_ID)->build();

        $this->user_helper
            ->method('getDisplayNameFromUser')
            ->willReturnCallback(static fn (\PFUser $user) => match ($user) {
                $alice => 'Alice',
                $bob   => 'Bob',
            });

        $invitee = UserTestBuilder::aUser()->withId(self::INVITEE_ID)->build();

        $a_project       = ProjectTestBuilder::aProject()->withId(self::A_PROJECT_ID)->build();
        $another_project = ProjectTestBuilder::aProject()->withId(self::ANOTHER_PROJECT_ID)->build();

        $invitation_1 = InvitationTestBuilder::aCompletedInvitation(1)
            ->from(self::ALICE_ID)
            ->toProjectId(self::A_PROJECT_ID)
            ->withCreatedUserId(self::INVITEE_ID)
            ->build();
        $invitation_2 = InvitationTestBuilder::aCompletedInvitation(2)
            ->from(self::ALICE_ID)
            ->toProjectId(self::A_PROJECT_ID)
            ->withCreatedUserId(self::INVITEE_ID)
            ->build();
        $invitation_3 = InvitationTestBuilder::aCompletedInvitation(3)
            ->from(self::BOB_ID)
            ->toProjectId(self::A_PROJECT_ID)
            ->withCreatedUserId(self::INVITEE_ID)
            ->build();
        $invitation_4 = InvitationTestBuilder::aCompletedInvitation(4)
            ->from(self::ALICE_ID)
            ->toProjectId(self::ANOTHER_PROJECT_ID)
            ->withCreatedUserId(self::INVITEE_ID)
            ->build();

        $dao = $this->createMock(InvitationDao::class);
        $dao->method('searchByCreatedUserId')
            ->willReturn([
                $invitation_1,
                $invitation_2,
                $invitation_3,
                $invitation_4,
            ]);

        $builder = new InvitedByPresenterBuilder(
            $dao,
            RetrieveUserByIdStub::withUsers($alice, $bob, $invitee),
            ProjectByIDFactoryStub::buildWith($a_project, $another_project),
            ProvideUserAvatarUrlStub::build(),
        );

        $presenter = $builder->getInvitedByPresenter($invitee, $current_user);
        self::assertTrue($presenter->has_been_invited);
        self::assertCount(2, $presenter->invited_by_users);
        self::assertEquals('Alice', $presenter->invited_by_users[0]->display_name);
        self::assertCount(2, $presenter->invited_by_users[0]->projects);
        self::assertEquals(self::A_PROJECT_ID, $presenter->invited_by_users[0]->projects[0]->id);
        self::assertEquals(self::ANOTHER_PROJECT_ID, $presenter->invited_by_users[0]->projects[1]->id);
        self::assertEquals('Bob', $presenter->invited_by_users[1]->display_name);
        self::assertCount(1, $presenter->invited_by_users[1]->projects);
        self::assertEquals(self::A_PROJECT_ID, $presenter->invited_by_users[1]->projects[0]->id);
    }
}
