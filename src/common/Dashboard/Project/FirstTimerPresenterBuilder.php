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

namespace Tuleap\Dashboard\Project;

use Tuleap\Config\ConfigurationVariables;
use Tuleap\Dashboard\User\FirstTimerPresenter;
use Tuleap\InviteBuddy\UsedInvitationRetriever;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\RetrieveUserById;

final class FirstTimerPresenterBuilder
{
    public function __construct(
        private UsedInvitationRetriever $invitation_dao,
        private RetrieveUserById $user_manager,
        private readonly ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    public function buildPresenter(\PFUser $user, \Project $project): ?FirstTimerPresenter
    {
        if (! $user->isFirstTimer()) {
            return null;
        }

        $invited_into_project = null;
        $invitation           = $this->invitation_dao->searchInvitationUsedToRegister((int) $user->getId());
        if ($invitation && (int) $project->getID() === $invitation->to_project_id) {
            $invited_into_project = $project;
        }

        return new FirstTimerPresenter(
            $user->getRealName(),
            \ForgeConfig::get(ConfigurationVariables::NAME),
            $invitation ? $this->user_manager->getUserById($invitation->from_user_id) : null,
            $invited_into_project,
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/first-timer/frontend-assets',
                    '/assets/core/first-timer',
                ),
                'src/first-timer.ts',
            ),
            $this->provide_user_avatar_url,
        );
    }
}
