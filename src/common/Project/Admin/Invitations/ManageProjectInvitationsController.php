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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\InviteBuddy\Invitation;
use Tuleap\InviteBuddy\InvitationByIdRetriever;
use Tuleap\InviteBuddy\InvitationHistoryEntry;
use Tuleap\InviteBuddy\InvitationNotFoundException;
use Tuleap\InviteBuddy\InvitationSender;
use Tuleap\InviteBuddy\InvitationSenderGateKeeperException;
use Tuleap\InviteBuddy\MustBeProjectAdminToInvitePeopleInProjectException;
use Tuleap\InviteBuddy\PendingInvitationsWithdrawer;
use Tuleap\InviteBuddy\UnableToSendInvitationsException;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class ManageProjectInvitationsController extends DispatchablePSR15Compatible
{
    public function __construct(
        private CSRFSynchronizerTokenProvider $token_provider,
        private RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private InvitationByIdRetriever $invitation_retriever,
        private PendingInvitationsWithdrawer $invitations_withdrawer,
        private InvitationSender $invitation_sender,
        private \ProjectHistoryDao $history_dao,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        assert($project instanceof \Project);

        $this->token_provider->getCSRF($project)->check();

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        $query_params = $request->getParsedBody();
        if (isset($query_params['withdraw-invitation'])) {
            return $this->withdrawInvitation((int) $query_params['withdraw-invitation'], $project, $user);
        }

        if (isset($query_params['resend-invitation'])) {
            return $this->resendInvitation((int) $query_params['resend-invitation'], $project, $user);
        }

        throw new ForbiddenException(_('Invalid request'));
    }

    private function withdrawInvitation(int $invitation_id, \Project $project, \PFUser $user): ResponseInterface
    {
        $invitation = $this->getInvitation($invitation_id, $project);

        $this->invitations_withdrawer->withdrawPendingInvitationsForProject($invitation->to_email, (int) $project->getID());
        $this->history_dao->addHistory(
            $project,
            $user,
            new \DateTimeImmutable('now'),
            InvitationHistoryEntry::InvitationWithdrawn->value,
            '',
            [],
        );

        return $this->createResponseForUser($user, $project, new NewFeedback(
            \Feedback::SUCCESS,
            _('Invitation has been withdrawn')
        ));
    }

    private function resendInvitation(int $invitation_id, \Project $project, \PFUser $user): ResponseInterface
    {
        $invitation = $this->getInvitation($invitation_id, $project);

        try {
            $this->invitation_sender->send($user, [$invitation->to_email], $project, null);
        } catch (MustBeProjectAdminToInvitePeopleInProjectException) {
            throw new ForbiddenException(
                _("You don't have permission to manage members of this project.")
            );
        } catch (UnableToSendInvitationsException | InvitationSenderGateKeeperException $exception) {
            return $this->createResponseForUser($user, $project, new NewFeedback(
                \Feedback::ERROR,
                $exception->getMessage(),
            ));
        }

        $this->history_dao->addHistory(
            $project,
            $user,
            new \DateTimeImmutable('now'),
            InvitationHistoryEntry::InvitationResent->value,
            '',
            [],
        );

        return $this->createResponseForUser($user, $project, new NewFeedback(
            \Feedback::SUCCESS,
            _('Invitation has been resent')
        ));
    }

    private function getInvitation(int $invitation_id, \Project $project): Invitation
    {
        try {
            $invitation = $this->invitation_retriever->searchById($invitation_id);
        } catch (InvitationNotFoundException $e) {
            throw new NotFoundException(_('The invitation you want to withdraw cannot be found. Maybe it has already been removed?'));
        }

        if ($invitation->to_project_id !== (int) $project->getID()) {
            throw new NotFoundException();
        }

        if (! $invitation->to_email) {
            throw new NotFoundException();
        }

        return $invitation;
    }

    private function createResponseForUser(\PFUser $user, \Project $project, NewFeedback $feedback): ResponseInterface
    {
        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            self::getMembersUrl($project),
            $feedback,
        );
    }

    public static function getCSRFToken(\Project $project): CSRFSynchronizerTokenInterface
    {
        return new \CSRFSynchronizerToken(self::getMembersUrl($project));
    }

    public static function getUrl(\Project $project): string
    {
        return '/project/' . urlencode((string) $project->getID()) . '/admin/invitations';
    }

    public static function getMembersUrl(\Project $project): string
    {
        return '/project/' . urlencode((string) $project->getID()) . '/admin/members';
    }
}
