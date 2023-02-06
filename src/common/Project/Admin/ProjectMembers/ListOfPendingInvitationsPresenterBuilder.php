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

namespace Tuleap\Project\Admin\ProjectMembers;

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\InviteBuddy\Invitation;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\InviteBuddy\PendingInvitationsForProjectRetriever;
use Tuleap\Project\Admin\Invitations\CSRFSynchronizerTokenProvider;
use Tuleap\Project\Admin\Invitations\ManageProjectInvitationsController;

final class ListOfPendingInvitationsPresenterBuilder
{
    public function __construct(
        private InviteBuddyConfiguration $invite_buddy_configuration,
        private PendingInvitationsForProjectRetriever $invitation_dao,
        private TlpRelativeDatePresenterBuilder $date_presenter_builder,
        private CSRFSynchronizerTokenProvider $token_provider,
    ) {
    }

    public function getPendingInvitationsPresenter(
        \Project $project,
        \PFUser $current_user,
    ): ?ListOfPendingInvitationsPresenter {
        if (! $this->invite_buddy_configuration->isFeatureEnabled()) {
            return null;
        }

        $pending_invitations_with_possible_duplicate_emails = array_map(
            fn(Invitation $invitation): PendingInvitationPresenter => new PendingInvitationPresenter(
                $invitation->id,
                $invitation->to_email,
                $this->date_presenter_builder->getTlpRelativeDatePresenterInInlineContext(
                    new \DateTimeImmutable('@' . $invitation->created_on),
                    $current_user
                )
            ),
            $this->invitation_dao->searchPendingInvitationsForProject((int) $project->getID()),
        );

        $deduplicated_pending_invitations = array_reduce(
            $pending_invitations_with_possible_duplicate_emails,
            static function (array $pending_invitations, PendingInvitationPresenter $invitation): array {
                if (isset($pending_invitations[$invitation->email])) {
                    unset($pending_invitations[$invitation->email]);
                }
                $pending_invitations[$invitation->email] = $invitation;

                return $pending_invitations;
            },
            [],
        );

        if (! $deduplicated_pending_invitations) {
            return null;
        }

        return new ListOfPendingInvitationsPresenter(
            ManageProjectInvitationsController::getUrl($project),
            CSRFSynchronizerTokenPresenter::fromToken($this->token_provider->getCSRF($project)),
            array_values($deduplicated_pending_invitations),
        );
    }
}
