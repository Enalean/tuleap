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

namespace Tuleap\InviteBuddy\Admin;

use Tuleap\InviteBuddy\Invitation;
use Tuleap\InviteBuddy\InvitationDao;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\RetrieveUserById;

final readonly class InvitedByPresenterBuilder
{
    public function __construct(
        private InvitationDao $dao,
        private RetrieveUserById $user_manager,
        private ProjectByIDFactory $project_manager,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    public function getInvitedByPresenter(\PFUser $user, \PFUser $current_user): InvitedByPresenter
    {
        $invited_by_users                   = [];
        $has_used_an_invitation_to_register = false;
        foreach ($this->dao->searchByCreatedUserId((int) $user->getId()) as $invitation) {
            if ($invitation->status === Invitation::STATUS_USED) {
                $has_used_an_invitation_to_register = true;
            }

            $from_user = $this->user_manager->getUserById($invitation->from_user_id);
            if (! $from_user) {
                continue;
            }

            $invited_by = $this->getInvitedByUserPresenter($from_user, $invited_by_users);
            if ($invitation->to_project_id) {
                $project    = $this->project_manager->getProjectById($invitation->to_project_id);
                $invited_by = $invited_by->withProject($project, $current_user);
            }

            $invited_by_users[$from_user->getId()] = $invited_by;
        }

        return new InvitedByPresenter(array_values($invited_by_users), $has_used_an_invitation_to_register);
    }

    private function getInvitedByUserPresenter(\PFUser $from_user, array $presenters): InvitedByUserPresenter
    {
        if (isset($presenters[$from_user->getId()])) {
            return $presenters[$from_user->getId()];
        }

        return InvitedByUserPresenter::fromUser($from_user, $this->provide_user_avatar_url);
    }
}
