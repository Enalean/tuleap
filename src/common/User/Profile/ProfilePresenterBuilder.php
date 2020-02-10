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

use Codendi_HTMLPurifier;
use EventManager;
use PFUser;
use Project;

class ProfilePresenterBuilder
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $purifier;

    public function __construct(EventManager $event_manager, Codendi_HTMLPurifier $purifier)
    {
        $this->event_manager = $event_manager;
        $this->purifier = $purifier;
    }

    public function getPresenter(PFUser $user, PFUser $current_user)
    {
        $projects = $this->getProjectsCurrentUserCanSee($user, $current_user);

        return new ProfilePresenter(
            $user,
            $this->getAdditionalEntries($user),
            ...$projects
        );
    }

    /**
     *
     * @return array
     */
    private function getAdditionalEntries(PFUser $user)
    {
        $additional_entry_labels = [];
        $additional_entry_values = [];

        $this->event_manager->processEvent(
            'user_home_pi_entry',
            [
                'user_id'     => $user->getId(),
                'entry_label' => &$additional_entry_labels,
                'entry_value' => &$additional_entry_values
            ]
        );

        $additional_entries = [];
        foreach ($additional_entry_labels as $key => $label) {
            $value = $additional_entry_values[$key];

            $additional_entries[] = [
                'label' => $label,
                'purified_value' => $this->purifier->purify($value, CODENDI_PURIFIER_LIGHT)
            ];
        }

        return $additional_entries;
    }

    /**
     *
     * @return Project[]
     */
    private function getProjectsCurrentUserCanSee(PFUser $user, PFUser $current_user)
    {
        $projects = [];
        foreach ($user->getGroups() as $project) {
            if ($current_user->isMember($project->getID())) {
                $projects[] = $project;
            } elseif ($project->isPublic()) {
                if (! $current_user->isRestricted()) {
                    $projects[] = $project;
                }
            }
        }

        return $projects;
    }
}
