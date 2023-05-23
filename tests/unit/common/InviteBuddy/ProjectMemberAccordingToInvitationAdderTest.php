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

namespace Tuleap\InviteBuddy;

use Psr\Log\NullLogger;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class ProjectMemberAccordingToInvitationAdderTest extends TestCase
{
    private const PROJECT_ID   = 111;
    private const FROM_USER_ID = 102;
    private const NEW_USER_ID  = 201;

    public function testDoesNothingIfInvitationIsNotForProject(): void
    {
        $user_manager               = RetrieveUserByIdStub::withNoUser();
        $project_retriever          = ProjectByIDFactoryStub::buildWithoutProject();
        $delegation_dao             = $this->createMock(MembershipDelegationDao::class);
        $project_member_adder       = $this->createMock(ProjectMemberAdder::class);
        $invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
        $email_notifier             = $this->createMock(InvitationEmailNotifier::class);
        $project_history_dao        = $this->createMock(\ProjectHistoryDao::class);

        $adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_retriever,
            $project_member_adder,
            $invitation_instrumentation,
            new NullLogger(),
            $email_notifier,
            $project_history_dao,
        );

        $project_member_adder
            ->expects(self::never())
            ->method('addProjectMember');
        $invitation_instrumentation
            ->expects(self::never())
            ->method('incrementProjectInvitation');

        $adder->addUserToProjectAccordingToInvitation(
            UserTestBuilder::buildWithDefaults(),
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->from(self::FROM_USER_ID)
                    ->to('doe@example.com')
                    ->build(),
                new ConcealedString('secret')
            )
        );
    }

    /**
     * @dataProvider notActiveNorRestrictedStatus
     */
    public function testDoesNothingIfUserIsNotActiveNorRestricted(string $status): void
    {
        $user_manager               = RetrieveUserByIdStub::withNoUser();
        $project_retriever          = ProjectByIDFactoryStub::buildWithoutProject();
        $delegation_dao             = $this->createMock(MembershipDelegationDao::class);
        $project_member_adder       = $this->createMock(ProjectMemberAdder::class);
        $invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
        $logger                     = new TestLogger();
        $email_notifier             = $this->createMock(InvitationEmailNotifier::class);
        $project_history_dao        = $this->createMock(\ProjectHistoryDao::class);

        $adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_retriever,
            $project_member_adder,
            $invitation_instrumentation,
            $logger,
            $email_notifier,
            $project_history_dao,
        );

        $project_member_adder
            ->expects(self::never())
            ->method('addProjectMember');
        $invitation_instrumentation
            ->expects(self::never())
            ->method('incrementProjectInvitation');

        $adder->addUserToProjectAccordingToInvitation(
            UserTestBuilder::aUser()->withId(self::NEW_USER_ID)->withStatus($status)->build(),
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->from(self::FROM_USER_ID)
                    ->to('doe@example.com')
                    ->toProjectId(self::PROJECT_ID)
                    ->build(),
                new ConcealedString('secret')
            )
        );

        self::assertTrue(
            $logger->hasInfo('User #201 has been invited to project #111, but need to be active first. Waiting for site admin approval')
        );
    }

    /**
     * @return string[]
     */
    public static function notActiveNorRestrictedStatus(): array
    {
        return [
            [\PFUser::STATUS_SUSPENDED],
            [\PFUser::STATUS_DELETED],
            [\PFUser::STATUS_VALIDATED],
            [\PFUser::STATUS_VALIDATED_RESTRICTED],
            [\PFUser::STATUS_PENDING],
        ];
    }

    public function testDoesNothingIfUserWhoInvitedCannotBeFound(): void
    {
        $user_manager               = RetrieveUserByIdStub::withNoUser();
        $project_retriever          = ProjectByIDFactoryStub::buildWithoutProject();
        $delegation_dao             = $this->createMock(MembershipDelegationDao::class);
        $project_member_adder       = $this->createMock(ProjectMemberAdder::class);
        $invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
        $logger                     = new TestLogger();
        $email_notifier             = $this->createMock(InvitationEmailNotifier::class);
        $project_history_dao        = $this->createMock(\ProjectHistoryDao::class);

        $adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_retriever,
            $project_member_adder,
            $invitation_instrumentation,
            $logger,
            $email_notifier,
            $project_history_dao,
        );

        $project_member_adder
            ->expects(self::never())
            ->method('addProjectMember');
        $invitation_instrumentation
            ->expects(self::never())
            ->method('incrementProjectInvitation');

        $adder->addUserToProjectAccordingToInvitation(
            UserTestBuilder::anActiveUser()->withId(self::NEW_USER_ID)->build(),
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->from(self::FROM_USER_ID)
                    ->to('doe@example.com')
                    ->toProjectId(self::PROJECT_ID)
                    ->build(),
                new ConcealedString('secret')
            )
        );

        self::assertTrue(
            $logger->hasError('User #201 has been invited by user #102 to project #111, but we cannot find user #102')
        );
    }

    /**
     * @dataProvider notActiveNorRestrictedStatus
     */
    public function testDoesNothingIfUserWhoInvitedIsNotActiveNorRestricted(string $status): void
    {
        $from_user = UserTestBuilder::aUser()
            ->withId(self::FROM_USER_ID)
            ->withStatus($status)
            ->build();

        $user_manager               = RetrieveUserByIdStub::withUser($from_user);
        $project_retriever          = ProjectByIDFactoryStub::buildWithoutProject();
        $delegation_dao             = $this->createMock(MembershipDelegationDao::class);
        $project_member_adder       = $this->createMock(ProjectMemberAdder::class);
        $invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
        $logger                     = new TestLogger();
        $email_notifier             = $this->createMock(InvitationEmailNotifier::class);
        $project_history_dao        = $this->createMock(\ProjectHistoryDao::class);

        $adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_retriever,
            $project_member_adder,
            $invitation_instrumentation,
            $logger,
            $email_notifier,
            $project_history_dao,
        );

        $project_member_adder
            ->expects(self::never())
            ->method('addProjectMember');
        $invitation_instrumentation
            ->expects(self::never())
            ->method('incrementProjectInvitation');

        $adder->addUserToProjectAccordingToInvitation(
            UserTestBuilder::anActiveUser()->withId(self::NEW_USER_ID)->build(),
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->from(self::FROM_USER_ID)
                    ->to('doe@example.com')
                    ->toProjectId(self::PROJECT_ID)
                    ->build(),
                new ConcealedString('secret')
            )
        );

        self::assertTrue(
            $logger->hasError('User #201 has been invited by user #102 to project #111, but user #102 is not active nor restricted')
        );
    }

    public function testDoesNothingIfProjectCannotBeFound(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $from_user = UserTestBuilder::anActiveUser()
            ->withId(self::FROM_USER_ID)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();

        $just_created_user = UserTestBuilder::anActiveUser()
            ->withId(self::NEW_USER_ID)
            ->build();

        $user_manager               = RetrieveUserByIdStub::withUser($from_user);
        $project_retriever          = ProjectByIDFactoryStub::buildWithoutProject();
        $delegation_dao             = $this->createMock(MembershipDelegationDao::class);
        $project_member_adder       = $this->createMock(ProjectMemberAdder::class);
        $invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
        $logger                     = new TestLogger();
        $email_notifier             = $this->createMock(InvitationEmailNotifier::class);
        $project_history_dao        = $this->createMock(\ProjectHistoryDao::class);

        $adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_retriever,
            $project_member_adder,
            $invitation_instrumentation,
            $logger,
            $email_notifier,
            $project_history_dao,
        );

        $delegation_dao
            ->method('doesUserHasMembershipDelegation')
            ->willReturn(false);

        $project_member_adder
            ->expects(self::never())
            ->method('addProjectMember');
        $invitation_instrumentation
            ->expects(self::never())
            ->method('incrementProjectInvitation');

        $adder->addUserToProjectAccordingToInvitation(
            $just_created_user,
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->from(self::FROM_USER_ID)
                    ->to('doe@example.com')
                    ->toProjectId(self::PROJECT_ID)
                    ->build(),
                new ConcealedString('secret')
            )
        );

        self::assertTrue(
            $logger->hasError('User #201 has been invited to project #111, but it appears that this project is not valid.')
        );
    }

    public function testProjectAdminCanAddAProjectMember(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $from_user = UserTestBuilder::anActiveUser()
            ->withId(self::FROM_USER_ID)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();

        $just_created_user = UserTestBuilder::anActiveUser()
            ->withId(self::NEW_USER_ID)
            ->build();

        $user_manager               = RetrieveUserByIdStub::withUser($from_user);
        $project_retriever          = ProjectByIDFactoryStub::buildWith($project);
        $delegation_dao             = $this->createMock(MembershipDelegationDao::class);
        $project_member_adder       = $this->createMock(ProjectMemberAdder::class);
        $invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
        $logger                     = new NullLogger();
        $email_notifier             = $this->createMock(InvitationEmailNotifier::class);
        $project_history_dao        = $this->createMock(\ProjectHistoryDao::class);

        $adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_retriever,
            $project_member_adder,
            $invitation_instrumentation,
            $logger,
            $email_notifier,
            $project_history_dao,
        );

        $delegation_dao
            ->method('doesUserHasMembershipDelegation')
            ->willReturn(false);

        $project_member_adder
            ->expects(self::once())
            ->method('addProjectMember')
            ->with($just_created_user, $project, $from_user);

        $invitation_instrumentation
            ->expects(self::once())
            ->method('incrementProjectInvitation');
        $project_history_dao
            ->expects(self::once())
            ->method('addHistory');

        $adder->addUserToProjectAccordingToInvitation(
            $just_created_user,
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->from(self::FROM_USER_ID)
                    ->to('doe@example.com')
                    ->toProjectId(self::PROJECT_ID)
                    ->build(),
                new ConcealedString('secret')
            )
        );
    }

    public function testItDoesNotTryToAddUserIfAlreadyProjectMemberThanksToAPreviouslyProcessedInvitation(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $from_user = UserTestBuilder::anActiveUser()
            ->withId(self::FROM_USER_ID)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();

        $just_created_user = UserTestBuilder::anActiveUser()
            ->withId(self::NEW_USER_ID)
            ->withMemberOf($project)
            ->build();

        $user_manager               = RetrieveUserByIdStub::withUser($from_user);
        $project_retriever          = ProjectByIDFactoryStub::buildWith($project);
        $delegation_dao             = $this->createMock(MembershipDelegationDao::class);
        $project_member_adder       = $this->createMock(ProjectMemberAdder::class);
        $invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
        $logger                     = new NullLogger();
        $email_notifier             = $this->createMock(InvitationEmailNotifier::class);
        $project_history_dao        = $this->createMock(\ProjectHistoryDao::class);

        $adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_retriever,
            $project_member_adder,
            $invitation_instrumentation,
            $logger,
            $email_notifier,
            $project_history_dao,
        );

        $delegation_dao
            ->method('doesUserHasMembershipDelegation')
            ->willReturn(false);

        $project_member_adder
            ->expects(self::never())
            ->method('addProjectMember');

        $invitation_instrumentation
            ->expects(self::once())
            ->method('incrementProjectInvitation');
        $project_history_dao
            ->expects(self::once())
            ->method('addHistory');

        $adder->addUserToProjectAccordingToInvitation(
            $just_created_user,
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->from(self::FROM_USER_ID)
                    ->to('doe@example.com')
                    ->toProjectId(self::PROJECT_ID)
                    ->build(),
                new ConcealedString('secret')
            )
        );
    }

    public function testUserWithPermissionDelegationCanAddAProjectMember(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $from_user = UserTestBuilder::anActiveUser()
            ->withId(self::FROM_USER_ID)
            ->withoutSiteAdministrator()
            ->withMemberOf($project)
            ->build();

        $just_created_user = UserTestBuilder::anActiveUser()
            ->withId(self::NEW_USER_ID)
            ->build();

        $user_manager               = RetrieveUserByIdStub::withUser($from_user);
        $project_retriever          = ProjectByIDFactoryStub::buildWith($project);
        $delegation_dao             = $this->createMock(MembershipDelegationDao::class);
        $project_member_adder       = $this->createMock(ProjectMemberAdder::class);
        $invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
        $logger                     = new NullLogger();
        $email_notifier             = $this->createMock(InvitationEmailNotifier::class);
        $project_history_dao        = $this->createMock(\ProjectHistoryDao::class);

        $adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_retriever,
            $project_member_adder,
            $invitation_instrumentation,
            $logger,
            $email_notifier,
            $project_history_dao,
        );

        $delegation_dao
            ->method('doesUserHasMembershipDelegation')
            ->willReturn(true);

        $project_member_adder
            ->expects(self::once())
            ->method('addProjectMember')
            ->with($just_created_user, $project, $from_user);

        $invitation_instrumentation
            ->expects(self::once())
            ->method('incrementProjectInvitation');
        $project_history_dao
            ->expects(self::once())
            ->method('addHistory');

        $adder->addUserToProjectAccordingToInvitation(
            $just_created_user,
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->from(self::FROM_USER_ID)
                    ->to('doe@example.com')
                    ->toProjectId(self::PROJECT_ID)
                    ->build(),
                new ConcealedString('secret')
            )
        );
    }

    public function testRestrictedUserInProjectWithoutRestricted(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $from_user = UserTestBuilder::anActiveUser()
            ->withId(self::FROM_USER_ID)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();

        $just_created_user = UserTestBuilder::aRestrictedUser()
            ->withId(self::NEW_USER_ID)
            ->build();

        $user_manager               = RetrieveUserByIdStub::withUser($from_user);
        $project_retriever          = ProjectByIDFactoryStub::buildWith($project);
        $delegation_dao             = $this->createMock(MembershipDelegationDao::class);
        $project_member_adder       = $this->createMock(ProjectMemberAdder::class);
        $invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
        $logger                     = new TestLogger();
        $email_notifier             = $this->createMock(InvitationEmailNotifier::class);
        $project_history_dao        = $this->createMock(\ProjectHistoryDao::class);

        $adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_retriever,
            $project_member_adder,
            $invitation_instrumentation,
            $logger,
            $email_notifier,
            $project_history_dao,
        );

        $project_member_adder
            ->expects(self::once())
            ->method('addProjectMember')
            ->with($just_created_user, $project, $from_user)
            ->willThrowException(new CannotAddRestrictedUserToProjectNotAllowingRestricted($just_created_user, $project));

        $invitation_instrumentation
            ->expects(self::never())
            ->method('incrementProjectInvitation');

        $email_notifier
            ->expects(self::once())
            ->method('informThatCannotAddRestrictedUserToProjectNotAllowingRestricted')
            ->with($from_user, $just_created_user, $project);

        $adder->addUserToProjectAccordingToInvitation(
            $just_created_user,
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->from(self::FROM_USER_ID)
                    ->to('doe@example.com')
                    ->toProjectId(self::PROJECT_ID)
                    ->build(),
                new ConcealedString('secret')
            )
        );

        self::assertTrue(
            $logger->hasError("Unable to add restricted user #201 to project #111.")
        );
    }
}
