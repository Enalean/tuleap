<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\User;

use PFUser;
use Project;
use ProjectUGroup;
use Rule_Email;
use UGroupManager;
use UserManager;

/**
 * I am responsible to parse data coming from the autocompleter (add someone or a group to the notification table)
 * and provide relevant data
 */
class RequestFromAutocompleter
{
    public const UGROUP_PREFIX = '_ugroup:';

    /**
     * @var string[]
     */
    private $emails;

    /**
     * @var ProjectUGroup[]
     */
    private $ugroups;

    /**
     * @var PFUser[]
     */
    private $users;

    /**
     * @var Rule_Email
     */
    private $rule_email;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var PFUser
     */
    private $current_user;

    /**
     * @var Project
     */
    private $project;
    /**
     * @var InvalidEntryInAutocompleterCollection
     */
    private $invalid_entries;

    public function __construct(
        InvalidEntryInAutocompleterCollection $invalid_entries,
        Rule_Email $rule_email,
        UserManager $user_manager,
        UGroupManager $ugroup_manager,
        PFUser $current_user,
        Project $project,
        $data
    ) {
        $this->invalid_entries = $invalid_entries;
        $this->rule_email      = $rule_email;
        $this->user_manager    = $user_manager;
        $this->ugroup_manager  = $ugroup_manager;
        $this->current_user    = $current_user;
        $this->project         = $project;

        $this->emails  = [];
        $this->ugroups = [];
        $this->users   = [];

        $list_of_listeners = array_filter(explode(',', $data));

        foreach ($list_of_listeners as $listener) {
            $listener = trim($listener);
            if ($this->isLookingLikeAnEmail($listener)) {
                $this->addEmailFromListener($listener);
            } elseif ($this->isLookingLikeAnUgroup($listener)) {
                $this->addUgroupFromListener($listener);
            } else {
                $this->addUserFromListener($listener);
            }
        }
    }

    /** @return string[] */
    public function getEmails()
    {
        return $this->emails;
    }

    /** @return ProjectUGroup[] */
    public function getUgroups()
    {
        return $this->ugroups;
    }

    /** @return PFUser[] */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return bool
     */
    public function isNotificationEmpty()
    {
        $emails  = $this->getEmails();
        $ugroups = $this->getUgroups();
        $users   = $this->getUsers();
        return empty($emails) && empty($ugroups) && empty($users);
    }


    private function isLookingLikeAnEmail($listener)
    {
        return $this->rule_email->isValid($listener);
    }

    private function isLookingLikeAnUgroup($listener)
    {
        return strpos($listener, self::UGROUP_PREFIX) === 0;
    }

    private function addEmailFromListener($listener)
    {
        $this->emails[] = $listener;
    }

    private function addUgroupFromListener($listener)
    {
        $name   = substr($listener, strlen(self::UGROUP_PREFIX));
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, $name);
        if ($ugroup && $this->userCanSeeUgroup($this->current_user, $ugroup, $this->project)) {
            $this->ugroups[] = $ugroup;
        } else {
            $this->invalid_entries->add($name);
        }
    }

    private function userCanSeeUgroup(PFUser $current_user, ProjectUGroup $ugroup, Project $project)
    {
        $allowed_static_ugroups = [ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN];

        return in_array($ugroup->getId(), $allowed_static_ugroups)
            || $current_user->isMemberOfUGroup($ugroup->getId(), $project->getId())
            || $current_user->isAdmin($project->getID());
    }

    private function addUserFromListener($listener)
    {
        $user = $this->user_manager->findUser($listener);
        if ($user) {
            $this->users[] = $user;
        } else {
            $this->invalid_entries->add($listener);
        }
    }
}
