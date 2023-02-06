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
use Tuleap\InviteBuddy\InvitationTestBuilder;
use Tuleap\InviteBuddy\PendingInvitationsWithdrawer;
use Tuleap\InviteBuddy\PendingInvitationsWithdrawerStub;
use Tuleap\Layout\Feedback\ISerializeFeedback;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\FeedbackSerializerStub;

class ManageProjectInvitationsControllerTest extends TestCase
{
    private const PROJECT_ID = 111;
    private \Project $project;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(111)->build();
        $this->user    = UserTestBuilder::anActiveUser()->build();
    }

    public function testErrorWhenRequestDoesNotTryToWithdrawAnInvitation(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withoutMatchingInvitation(),
            $invitations_withdrawer,
            $this->createMock(\ProjectHistoryDao::class),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $this->project)
            ->withAttribute(\PFUser::class, $this->user)
            ->withParsedBody([]);

        $this->expectException(ForbiddenException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
    }

    public function testErrorWhenTryingToRemoveAnUnknownInvitation(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withoutMatchingInvitation(),
            $invitations_withdrawer,
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
    }

    public function testErrorWhenTryingToRemoveAnInvitationThatDoesNotBelongToProject(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(112)
                    ->build()
            ),
            $invitations_withdrawer,
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
    }

    public function testErrorWhenTryingToRemoveAnInvitationThatHasNotBeSentToAnEmail(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();

        $controller = $this->buildController(
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to(102)
                    ->toProjectId(111)
                    ->build()
            ),
            $invitations_withdrawer,
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
    }

    public function testHappyPath(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $feedback_serializer    = FeedbackSerializerStub::buildSelf();
        $invitations_withdrawer = PendingInvitationsWithdrawerStub::buildSelf();
        $history_dao            = $this->createMock(\ProjectHistoryDao::class);

        $history_dao
            ->expects(self::once())
            ->method('addHistory');

        $controller = $this->buildController(
            $token,
            $feedback_serializer,
            InvitationByIdRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(42)
                    ->to('bob@example.com')
                    ->toProjectId(111)
                    ->build()
            ),
            $invitations_withdrawer,
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
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/project/111/admin/members', $response->getHeaderLine('Location'));
        self::assertEquals(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    private function buildController(
        CSRFSynchronizerTokenInterface $token,
        ISerializeFeedback $feedback_serializer,
        InvitationByIdRetriever $invitation_by_id_retriever,
        PendingInvitationsWithdrawer $invitations_withdrawer,
        \ProjectHistoryDao $history_dao,
    ): ManageProjectInvitationsController {
        $csrf_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $csrf_provider
            ->expects(self::once())
            ->method('getCSRF')
            ->willReturn($token);

        return new ManageProjectInvitationsController(
            $csrf_provider,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $invitation_by_id_retriever,
            $invitations_withdrawer,
            $history_dao,
            new NoopSapiEmitter(),
        );
    }
}
