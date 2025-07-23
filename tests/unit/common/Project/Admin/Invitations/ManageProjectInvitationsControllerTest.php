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

namespace Tuleap\Project\Admin\Invitations;

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\InviteBuddy\InvitationByIdRetriever;
use Tuleap\InviteBuddy\InvitationByIdRetrieverStub;
use Tuleap\InviteBuddy\InvitationSender;
use Tuleap\InviteBuddy\InvitationSenderGateKeeperException;
use Tuleap\InviteBuddy\InvitationTestBuilder;
use Tuleap\InviteBuddy\PendingInvitationsWithdrawer;
use Tuleap\InviteBuddy\PendingInvitationsWithdrawerStub;
use Tuleap\InviteBuddy\SentInvitationResult;
use Tuleap\InviteBuddy\UnableToSendInvitationsException;
use Tuleap\Layout\Feedback\ISerializeFeedback;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\FeedbackSerializerStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\User\RetrieveUserById;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ManageProjectInvitationsControllerTest extends TestCase
{
    private const PROJECT_ID = 111;
    private \Project $project;
    private \PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(111)->build();
        $this->user    = UserTestBuilder::anActiveUser()->build();
    }

    public function testErrorWhenRequestDoesNotTryToWithdrawOrResendAnInvitation(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            RetrieveUserByIdStub::withNoUser(),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withoutMatchingInvitation(),
            $invitations_withdrawer,
            $this->createMock(InvitationSender::class),
            $this->createMock(\ProjectHistoryDao::class),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([]);

        $this->expectException(ForbiddenException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
    }

    public function testErrorWhenTryingToRemoveAnUnknownInvitation(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            RetrieveUserByIdStub::withNoUser(),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withoutMatchingInvitation(),
            $invitations_withdrawer,
            $this->createMock(InvitationSender::class),
            $this->createMock(\ProjectHistoryDao::class),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'withdraw-invitation' => 42,
            ]);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
    }

    public function testErrorWhenTryingToRemoveAnInvitationThatDoesNotBelongToProject(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            RetrieveUserByIdStub::withNoUser(),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(112)
                    ->build()
            ),
            $invitations_withdrawer,
            $this->createMock(InvitationSender::class),
            $this->createMock(\ProjectHistoryDao::class),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'withdraw-invitation' => 42,
            ]);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
    }

    public function testErrorWhenTryingToRemoveAnInvitationThatHasNotBeSentToAnEmail(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            RetrieveUserByIdStub::withNoUser(),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to(102)
                    ->toProjectId(111)
                    ->build()
            ),
            $invitations_withdrawer,
            $this->createMock(InvitationSender::class),
            $this->createMock(\ProjectHistoryDao::class),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'withdraw-invitation' => 42,
            ]);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
    }

    public function testWithdrawInvitation(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();
        $history_dao            = $this->createMock(\ProjectHistoryDao::class);

        $history_dao
            ->expects($this->once())
            ->method('addHistory');

        $controller = $this->buildController(
            RetrieveUserByIdStub::withNoUser(),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(111)
                    ->build()
            ),
            $invitations_withdrawer,
            $this->createMock(InvitationSender::class),
            $history_dao,
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'withdraw-invitation' => 42,
            ]);

        $response = $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertTrue($invitations_withdrawer->hasBeenCalled());
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/project/111/admin/members', $response->getHeaderLine('Location'));
        self::assertEquals(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    public function testErrorWhenTryingToResendAnUnknownInvitation(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            RetrieveUserByIdStub::withNoUser(),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withoutMatchingInvitation(),
            $invitations_withdrawer,
            $this->createMock(InvitationSender::class),
            $this->createMock(\ProjectHistoryDao::class),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'resend-invitation' => 42,
            ]);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
    }

    public function testErrorWhenTryingToResendAnInvitationThatDoesNotBelongToProject(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            RetrieveUserByIdStub::withNoUser(),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(112)
                    ->build()
            ),
            $invitations_withdrawer,
            $this->createMock(InvitationSender::class),
            $this->createMock(\ProjectHistoryDao::class),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'resend-invitation' => 42,
            ]);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
    }

    public function testErrorWhenTryingToResendAnInvitationThatHasNotBeSentToAnEmail(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            RetrieveUserByIdStub::withNoUser(),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to(102)
                    ->toProjectId(111)
                    ->build()
            ),
            $invitations_withdrawer,
            $this->createMock(InvitationSender::class),
            $this->createMock(\ProjectHistoryDao::class),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'resend-invitation' => 42,
            ]);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
    }

    public function testResendInvitationThrowsErrorIfCurrentUserIsNotAllowedToResendInvitation(): void
    {
        $from_user = UserTestBuilder::anActiveUser()->withId(101)->build();

        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();
        $invitation_sender      = $this->createMock(InvitationSender::class);
        $history_dao            = $this->createMock(\ProjectHistoryDao::class);

        $history_dao
            ->expects($this->never())
            ->method('addHistory');

        $invitation_sender
            ->expects($this->exactly(2))
            ->method('send')
            ->willThrowException(new UserIsNotAllowedToManageProjectMembersException());

        $controller = $this->buildController(
            RetrieveUserByIdStub::withUser($from_user),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(111)
                    ->withCustomMessage('Viens on est bien')
                    ->build()
            ),
            $invitations_withdrawer,
            $invitation_sender,
            $history_dao,
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'resend-invitation' => 42,
            ]);

        $this->expectException(ForbiddenException::class);
        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
    }

    public function testResendInvitationDisplaysErrorIfGateKeeperException(): void
    {
        $from_user = UserTestBuilder::anActiveUser()->withId(101)->build();

        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();
        $invitation_sender      = $this->createMock(InvitationSender::class);
        $history_dao            = $this->createMock(\ProjectHistoryDao::class);

        $history_dao
            ->expects($this->never())
            ->method('addHistory');

        $invitation_sender
            ->expects($this->exactly(2))
            ->method('send')
            ->willThrowException(new InvitationSenderGateKeeperException());

        $controller = $this->buildController(
            RetrieveUserByIdStub::withUser($from_user),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(111)
                    ->withCustomMessage('Viens on est bien')
                    ->build()
            ),
            $invitations_withdrawer,
            $invitation_sender,
            $history_dao,
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'resend-invitation' => 42,
            ]);

        $response = $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/project/111/admin/members', $response->getHeaderLine('Location'));
        self::assertEquals(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    public function testResendInvitationDisplaysErrorIfUnableToSendInvitationsException(): void
    {
        $from_user = UserTestBuilder::anActiveUser()->withId(101)->build();

        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();
        $invitation_sender      = $this->createMock(InvitationSender::class);
        $history_dao            = $this->createMock(\ProjectHistoryDao::class);

        $history_dao
            ->expects($this->never())
            ->method('addHistory');

        $invitation_sender
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new UnableToSendInvitationsException());

        $controller = $this->buildController(
            RetrieveUserByIdStub::withUser($from_user),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(111)
                    ->withCustomMessage('Viens on est bien')
                    ->build()
            ),
            $invitations_withdrawer,
            $invitation_sender,
            $history_dao,
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'resend-invitation' => 42,
            ]);

        $response = $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/project/111/admin/members', $response->getHeaderLine('Location'));
        self::assertEquals(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    public function testResendInvitationFallbackToCurrentUserIfFormerUserIsNoMoreProjectAdmin(): void
    {
        $from_user = UserTestBuilder::anActiveUser()->withId(101)->build();

        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();
        $invitation_sender      = $this->createMock(InvitationSender::class);
        $history_dao            = $this->createMock(\ProjectHistoryDao::class);

        $invitation_sender
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(
                function (
                    \PFUser $arg_from_user,
                    array $arg_emails,
                    ?\Project $arg_project,
                    ?string $arg_custom_message,
                    ?\PFUser $arg_resent_from_user,
                ) use ($from_user): SentInvitationResult {
                    if ((int) $arg_from_user->getId() === (int) $from_user->getId()) {
                        throw new UserIsNotAllowedToManageProjectMembersException();
                    }

                    if (
                        (int) $arg_from_user->getId() === (int) $this->user->getId()
                        && $arg_emails === ['bob@example.com']
                        && $arg_project === $this->project
                        && $arg_custom_message === null
                        && (int) $arg_resent_from_user->getId() === (int) $this->user->getId()
                    ) {
                        return new SentInvitationResult([], [], [], [], []);
                    }

                    throw new \Exception('Unexpected call te send()');
                }
            );

        $controller = $this->buildController(
            RetrieveUserByIdStub::withUser($from_user),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(111)
                    ->withCustomMessage('Viens on est bien')
                    ->build()
            ),
            $invitations_withdrawer,
            $invitation_sender,
            $history_dao,
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'resend-invitation' => 42,
            ]);

        $response = $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/project/111/admin/members', $response->getHeaderLine('Location'));
        self::assertEquals(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    public function testResendInvitationFallbackToCurrentUserIfFormerUserSentTooMuchInvitations(): void
    {
        $from_user = UserTestBuilder::anActiveUser()->withId(101)->build();

        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();
        $invitation_sender      = $this->createMock(InvitationSender::class);
        $history_dao            = $this->createMock(\ProjectHistoryDao::class);

        $invitation_sender
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(
                function (
                    \PFUser $arg_from_user,
                    array $arg_emails,
                    ?\Project $arg_project,
                    ?string $arg_custom_message,
                    ?\PFUser $arg_resent_from_user,
                ) use ($from_user): SentInvitationResult {
                    if ((int) $arg_from_user->getId() === (int) $from_user->getId()) {
                        throw new InvitationSenderGateKeeperException();
                    }

                    if (
                        (int) $arg_from_user->getId() === (int) $this->user->getId()
                        && $arg_emails === ['bob@example.com']
                        && $arg_project === $this->project
                        && $arg_custom_message === null
                        && (int) $arg_resent_from_user->getId() === (int) $this->user->getId()
                    ) {
                        return new SentInvitationResult([], [], [], [], []);
                    }

                    throw new \Exception('Unexpected call te send()');
                }
            );

        $controller = $this->buildController(
            RetrieveUserByIdStub::withUser($from_user),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(111)
                    ->withCustomMessage('Viens on est bien')
                    ->build()
            ),
            $invitations_withdrawer,
            $invitation_sender,
            $history_dao,
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'resend-invitation' => 42,
            ]);

        $response = $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/project/111/admin/members', $response->getHeaderLine('Location'));
        self::assertEquals(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    public function testResendInvitation(): void
    {
        $from_user = UserTestBuilder::anActiveUser()->withId(101)->build();

        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();
        $invitation_sender      = $this->createMock(InvitationSender::class);
        $history_dao            = $this->createMock(\ProjectHistoryDao::class);

        $invitation_sender
            ->expects($this->once())
            ->method('send')
            ->with(
                $from_user,
                ['bob@example.com'],
                $this->project,
                'Viens on est bien',
                $this->user,
            )->willReturn(new SentInvitationResult([], [], [], [], []));

        $controller = $this->buildController(
            RetrieveUserByIdStub::withUser($from_user),
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(111)
                    ->withCustomMessage('Viens on est bien')
                    ->build()
            ),
            $invitations_withdrawer,
            $invitation_sender,
            $history_dao,
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([
                'resend-invitation' => 42,
            ]);

        $response = $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertFalse($invitations_withdrawer->hasBeenCalled());
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/project/111/admin/members', $response->getHeaderLine('Location'));
        self::assertEquals(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    private function buildController(
        RetrieveUserById $user_manager,
        CSRFSynchronizerTokenInterface $token,
        ISerializeFeedback $feedback_serializer,
        InvitationByIdRetriever $invitation_by_id_retriever,
        PendingInvitationsWithdrawer $invitations_withdrawer,
        InvitationSender $invitation_sender,
        \ProjectHistoryDao $history_dao,
    ): ManageProjectInvitationsController {
        $csrf_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $csrf_provider
            ->expects($this->once())
            ->method('getCSRF')
            ->willReturn($token);

        return new ManageProjectInvitationsController(
            $user_manager,
            $csrf_provider,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $invitation_by_id_retriever,
            $invitations_withdrawer,
            $invitation_sender,
            $history_dao,
            new NoopSapiEmitter(),
        );
    }
}
