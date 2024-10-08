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

use PFUser;
use Tuleap\Project\ProjectPresenter;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;

final class InvitedByUserPresenter
{
    public readonly bool $invited_in_projects;
    public readonly bool $invited_in_more_than_one_project;

    /**
     * @param ProjectPresenter[] $projects
     */
    private function __construct(
        public readonly string $display_name,
        public readonly string $url,
        public readonly bool $has_avatar,
        public readonly string $avatar_url,
        public readonly array $projects,
    ) {
        $this->invited_in_projects              = count($this->projects) > 0;
        $this->invited_in_more_than_one_project = count($this->projects) > 1;
    }

    public static function fromUser(PFUser $user, ProvideUserAvatarUrl $provide_user_avatar_url): self
    {
        return new self(
            (string) \UserHelper::instance()->getDisplayNameFromUser($user),
            '/admin/usergroup.php?' . http_build_query(['user_id' => $user->getId()]),
            $user->hasAvatar(),
            $provide_user_avatar_url->getAvatarUrl($user),
            [],
        );
    }

    public function withProject(\Project $project, PFUser $current_user): self
    {
        $already_existing = false;
        foreach ($this->projects as $presenter) {
            if ($presenter->id === (int) $project->getID()) {
                $already_existing = true;
            }
        }

        $projects = $this->projects;
        if (! $already_existing) {
            $projects[] = ProjectPresenter::fromProject($project, $current_user);
        }

        return new self(
            $this->display_name,
            $this->url,
            $this->has_avatar,
            $this->avatar_url,
            $projects,
        );
    }
}
