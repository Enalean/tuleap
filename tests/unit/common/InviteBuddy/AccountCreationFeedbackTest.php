<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\User\Account\Register\RegisterFormContext;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AccountCreationFeedbackTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LoggerInterface|MockObject $logger;
    private InvitationDao|MockObject $dao;
    private MockObject|AccountCreationFeedbackEmailNotifier $email_notifier;
    /**
     * @var InvitationInstrumentation&MockObject
     */
    private $invitation_instrumentation;


    protected function setUp(): void
    {
        $this->logger                     = $this->createMock(LoggerInterface::class);
        $this->dao                        = $this->createMock(InvitationDao::class);
        $this->email_notifier             = $this->createMock(AccountCreationFeedbackEmailNotifier::class);
        $this->invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
    }

    public function testItUpdatesInvitationsWithJustCreatedUser(): void
    {
        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->expects($this->once())
            ->method('saveJustCreatedUserThanksToInvitation')
            ->with('doe@example.com', 104, null);

        $this->dao
            ->expects($this->once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn([]);

        $this->invitation_instrumentation->expects(self::never())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects(self::never())->method('incrementCompletedInvitation');

        $user_manager              = RetrieveUserByIdStub::withNoUser();
        $project_member_adder      = AddUserToProjectAccordingToInvitationStub::buildSelf();
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            $project_member_adder,
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());

        self::assertEquals(0, $project_member_adder->getNbCalls());
    }

    public function testItUpdatesInvitationsWithJustCreatedUserByInvitation(): void
    {
        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->expects($this->once())
            ->method('saveJustCreatedUserThanksToInvitation')
            ->with('doe@example.com', 104, 1);

        $this->dao
            ->expects($this->once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn([]);

        $this->invitation_instrumentation->expects($this->once())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects(self::never())->method('incrementCompletedInvitation');

        $user_manager              = RetrieveUserByIdStub::withNoUser();
        $project_member_adder      = AddUserToProjectAccordingToInvitationStub::buildSelf();
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            $project_member_adder,
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated(
            $new_user,
            RegisterFormContext::forAnonymous(
                true,
                InvitationToEmail::fromInvitation(
                    InvitationTestBuilder::aSentInvitation(1)
                        ->from(102)
                        ->to('doe@example.com')
                        ->build(),
                    new ConcealedString('secret')
                )
            )
        );

        self::assertEquals(0, $project_member_adder->getNbCalls());
    }

    public function testItAddUsersToAllProjectsTheyHaveBeenInvitedInto(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(111)->build();
        $another_project = ProjectTestBuilder::aProject()->withId(112)->build();

        $new_user = UserTestBuilder::anActiveUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $project_admin = UserTestBuilder::anActiveUser()
            ->withId(102)
            ->withAdministratorOf($project)
            ->withAdministratorOf($another_project)
            ->build();

        $used_invitation           = InvitationTestBuilder::aUsedInvitation(1)
            ->from(102)
            ->to('doe@example.com')
            ->toProjectId(111)
            ->build();
        $another_invitation        = InvitationTestBuilder::aCompletedInvitation(2)
            ->from(102)
            ->to('doe@example.com')
            ->toProjectId(112)
            ->build();
        $not_in_project_invitation = InvitationTestBuilder::aCompletedInvitation(3)
            ->from(102)
            ->to('doe@example.com')
            ->build();

        $this->dao->method('saveJustCreatedUserThanksToInvitation');
        $this->dao->method('searchByCreatedUserId')->willReturn([
            $not_in_project_invitation,
            $used_invitation,
            $another_invitation,
        ]);

        $this->invitation_instrumentation->expects($this->once())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects(self::exactly(3))->method('incrementCompletedInvitation');

        $this->email_notifier
            ->expects(self::exactly(1))
            ->method('send')
            ->with($project_admin, $new_user)
            ->willReturn(true);

        $project_history_dao = $this->createMock(\ProjectHistoryDao::class);
        $project_history_dao->method('addHistory');

        $user_manager              = RetrieveUserByIdStub::withUsers($new_user, $project_admin);
        $project_member_adder      = AddUserToProjectAccordingToInvitationStub::buildSelf();
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            $project_member_adder,
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated(
            $new_user,
            RegisterFormContext::forAnonymous(
                true,
                InvitationToEmail::fromInvitation(
                    $used_invitation,
                    new ConcealedString('secret')
                )
            )
        );

        self::assertEquals(2, $project_member_adder->getNbCalls());
    }

    public function testItAddsTwiceIfUSerIsInvitedTwiceInTheSameProjectFromTheSameUserSoThatWeCanRegisterInHistoryTheCompletedInvitations(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(111)->build();

        $new_user = UserTestBuilder::anActiveUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $project_admin = UserTestBuilder::anActiveUser()
            ->withId(102)
            ->withAdministratorOf($project)
            ->build();

        $used_invitation    = InvitationTestBuilder::aUsedInvitation(1)
            ->from(102)
            ->to('doe@example.com')
            ->toProjectId(111)
            ->build();
        $another_invitation = InvitationTestBuilder::aCompletedInvitation(2)
            ->from(102)
            ->to('doe@example.com')
            ->toProjectId(111)
            ->build();

        $this->dao->method('saveJustCreatedUserThanksToInvitation');
        $this->dao->method('searchByCreatedUserId')->willReturn([
            $used_invitation,
            $another_invitation,
        ]);

        $this->invitation_instrumentation->expects($this->once())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects(self::exactly(2))->method('incrementCompletedInvitation');

        $this->email_notifier
            ->expects(self::exactly(1))
            ->method('send')
            ->with($project_admin, $new_user)
            ->willReturn(true);

        $project_history_dao = $this->createMock(\ProjectHistoryDao::class);
        $project_history_dao->method('addHistory');

        $user_manager              = RetrieveUserByIdStub::withUsers($new_user, $project_admin);
        $project_member_adder      = AddUserToProjectAccordingToInvitationStub::buildSelf();
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            $project_member_adder,
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated(
            $new_user,
            RegisterFormContext::forAnonymous(
                true,
                InvitationToEmail::fromInvitation(
                    $used_invitation,
                    new ConcealedString('secret')
                )
            )
        );

        self::assertEquals(2, $project_member_adder->getNbCalls());
    }

    public function testItAddUsersToAllProjectsTheyHaveBeenInvitedIntoFromDifferentUsers(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(111)->build();
        $another_project = ProjectTestBuilder::aProject()->withId(112)->build();

        $new_user = UserTestBuilder::anActiveUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $project_admin = UserTestBuilder::anActiveUser()
            ->withId(102)
            ->withAdministratorOf($project)
            ->build();

        $another_project_admin = UserTestBuilder::anActiveUser()
            ->withId(103)
            ->withAdministratorOf($another_project)
            ->build();

        $used_invitation           = InvitationTestBuilder::aUsedInvitation(1)
            ->from(102)
            ->to('doe@example.com')
            ->toProjectId(111)
            ->build();
        $another_invitation        = InvitationTestBuilder::aCompletedInvitation(2)
            ->from(103)
            ->to('doe@example.com')
            ->toProjectId(112)
            ->build();
        $not_in_project_invitation = InvitationTestBuilder::aCompletedInvitation(3)
            ->from(102)
            ->to('doe@example.com')
            ->build();

        $this->dao->method('saveJustCreatedUserThanksToInvitation');
        $this->dao->method('searchByCreatedUserId')->willReturn([
            $not_in_project_invitation,
            $used_invitation,
            $another_invitation,
        ]);

        $this->invitation_instrumentation->expects($this->once())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects(self::exactly(3))->method('incrementCompletedInvitation');

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(static fn (\PFUser $from_user, \PFUser $just_created_user) => match (true) {
                $just_created_user === $new_user && ($from_user === $project_admin || $from_user === $another_project_admin) => true
            });

        $project_history_dao = $this->createMock(\ProjectHistoryDao::class);
        $project_history_dao->method('addHistory');

        $user_manager              = RetrieveUserByIdStub::withUsers($new_user, $project_admin, $another_project_admin);
        $project_member_adder      = AddUserToProjectAccordingToInvitationStub::buildSelf();
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            $project_member_adder,
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated(
            $new_user,
            RegisterFormContext::forAnonymous(
                true,
                InvitationToEmail::fromInvitation(
                    $used_invitation,
                    new ConcealedString('secret')
                )
            )
        );

        self::assertEquals(2, $project_member_adder->getNbCalls());
    }

    public function testItNotifiesNobodyIfUserWasNotInvited(): void
    {
        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects($this->once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn([]);

        $this->email_notifier
            ->expects(self::never())
            ->method('send');

        $this->invitation_instrumentation->expects(self::never())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects(self::never())->method('incrementCompletedInvitation');

        $user_manager              = RetrieveUserByIdStub::withNoUser();
        $project_member_adder      = AddUserToProjectAccordingToInvitationStub::buildSelf();
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            $project_member_adder,
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());

        self::assertEquals(0, $project_member_adder->getNbCalls());
    }

    public function testItNotifiesEveryPeopleWhoInvitedTheUser(): void
    {
        $from_user = UserTestBuilder::aUser()
            ->withId(103)
            ->withStatus('A')
            ->build();

        $from_another_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withStatus('A')
            ->build();

        $an_invitation      = InvitationTestBuilder::aUsedInvitation(1)
            ->from(103)
            ->to('doe@example.com')
            ->build();
        $another_invitation = InvitationTestBuilder::aCompletedInvitation(2)
            ->from(104)
            ->to('doe@example.com')
            ->build();

        $new_user = UserTestBuilder::aUser()
            ->withId(105)
            ->withEmail('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects($this->once())
            ->method('searchByCreatedUserId')
            ->with(105)
            ->willReturn(
                [
                    $an_invitation, $another_invitation,
                ]
            );

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(
                fn (\PFUser $send_from_user, \PFUser $send_just_created_user): bool => match (true) {
                    $from_user === $send_from_user && $send_just_created_user === $new_user,
                    $from_another_user === $send_from_user && $send_just_created_user === $new_user => true
                }
            );

        $this->invitation_instrumentation->expects(self::never())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects(self::exactly(2))->method('incrementCompletedInvitation');

        $user_manager              = RetrieveUserByIdStub::withUsers($new_user, $from_user, $from_another_user);
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            AddUserToProjectAccordingToInvitationStub::buildSelf(),
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }

    public function testItIgnoresUsersThatCannotBeFoundButLogsAnError(): void
    {
        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $an_invitation = InvitationTestBuilder::aUsedInvitation(1)
            ->from(103)
            ->to('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects($this->once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn([$an_invitation]);

        $this->email_notifier
            ->expects(self::never())
            ->method('send');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Invitation was referencing an unknown user #103');

        $this->invitation_instrumentation->expects(self::never())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects($this->once())->method('incrementCompletedInvitation');

        $user_manager              = RetrieveUserByIdStub::withNoUser();
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            AddUserToProjectAccordingToInvitationStub::buildSelf(),
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }

    public function testItIgnoresUsersThatAreNotAliveButLogsAWarning(): void
    {
        $from_user = UserTestBuilder::aUser()
            ->withId(103)
            ->withStatus('D')
            ->build();

        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $an_invitation = InvitationTestBuilder::aUsedInvitation(1)
            ->from(103)
            ->to('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects($this->once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn([$an_invitation]);

        $this->email_notifier
            ->expects(self::never())
            ->method('send');

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Cannot send invitation feedback to inactive user #103');

        $this->invitation_instrumentation->expects(self::never())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects($this->once())->method('incrementCompletedInvitation');

        $user_manager              = RetrieveUserByIdStub::withUsers($from_user);
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            AddUserToProjectAccordingToInvitationStub::buildSelf(),
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }

    public function testItLogsAnErrorIfEmailCannotBeSent(): void
    {
        $from_user = UserTestBuilder::aUser()
            ->withId(103)
            ->withStatus('A')
            ->build();

        $new_user = UserTestBuilder::aUser()
            ->withId(104)
            ->withEmail('doe@example.com')
            ->build();

        $an_invitation = InvitationTestBuilder::aUsedInvitation(1)
            ->from(103)
            ->to('doe@example.com')
            ->build();

        $this->dao
            ->method('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->expects($this->once())
            ->method('searchByCreatedUserId')
            ->with(104)
            ->willReturn([$an_invitation]);

        $this->email_notifier
            ->expects($this->once())
            ->method('send')
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Unable to send invitation feedback to user #103 after registration of user #104');

        $this->invitation_instrumentation->expects(self::never())->method('incrementUsedInvitation');
        $this->invitation_instrumentation->expects($this->once())->method('incrementCompletedInvitation');

        $user_manager              = RetrieveUserByIdStub::withUsers($from_user);
        $account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $user_manager,
            $this->email_notifier,
            AddUserToProjectAccordingToInvitationStub::buildSelf(),
            $this->invitation_instrumentation,
            $this->logger,
        );
        $account_creation_feedback->accountHasJustBeenCreated($new_user, RegisterFormContext::forAdmin());
    }
}
