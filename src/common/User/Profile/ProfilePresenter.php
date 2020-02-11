<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\User\Profile;

use PFUser;
use Project;
use Tuleap\User\StatusPresenter;

class ProfilePresenter
{
    public $real_name;
    public $has_avatar;
    public $avatar_url;
    public $user_name;
    public $id;
    public $email;
    public $member_since_date;
    public $member_since_date_long;
    public $projects;
    public $nb_projects;
    public $status;
    public $additional_entries;

    public function __construct(PFUser $user, array $additional_entries, Project ...$projects)
    {
        $this->id         = $user->getId();
        $this->real_name  = $user->getRealName();
        $this->user_name  = $user->getUserName();
        $this->has_avatar = $user->hasAvatar();
        $this->avatar_url = $user->getAvatarUrl();
        $this->email      = $user->getEmail();

        $this->additional_entries = $additional_entries;

        $this->member_since_date = strftime(
            $GLOBALS['Language']->getText('system', 'strfdateshortfmt'),
            $user->getAddDate()
        );

        $this->member_since_date_long = strftime(
            $GLOBALS['Language']->getText('system', 'strfdatefmt'),
            $user->getAddDate()
        );

        $this->status = new StatusPresenter($user->getStatus());

        $this->projects = [];
        foreach ($projects as $project) {
            $this->projects[] = [
                'unix_name'   => $project->getUnixNameMixedCase(),
                'public_name' => $project->getPublicName(),
                'description' => $project->getDescription(),
                'nb_members'  => count($project->getMembersId())
            ];
        }
        usort(
            $this->projects,
            function ($a, $b) {
                return strnatcasecmp($a['public_name'], $b['public_name']);
            }
        );
        $this->nb_projects = count($this->projects);
    }
}
