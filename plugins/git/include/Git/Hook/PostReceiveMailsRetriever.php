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

namespace Tuleap\Git\Hook;

use GitRepository;
use ProjectUGroup;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use UGroupManager;

class PostReceiveMailsRetriever
{
    /**
     * @var UsersToNotifyDao
     */
    private $user_dao;

    /**
     * @var UgroupsToNotifyDao
     */
    private $ugroup_dao;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        UsersToNotifyDao $user_dao,
        UgroupsToNotifyDao $ugroup_dao,
        UGroupManager $ugroup_manager
    ) {
        $this->user_dao       = $user_dao;
        $this->ugroup_dao     = $ugroup_dao;
        $this->ugroup_manager = $ugroup_manager;
    }

    /**
     * @return string[]
     */
    public function getNotifiedMails(GitRepository $repository)
    {
        $emails = $repository->getNotifiedMails();
        $this->addUsers($repository, $emails);
        $this->addUgroups($repository, $emails);

        return array_unique($emails);
    }

    private function addUsers(GitRepository $repository, array &$emails)
    {
        foreach ($this->user_dao->searchUsersByRepositoryId($repository->getId()) as $row) {
            $emails[] = $row['email'];
        }
    }

    private function addUgroups(GitRepository $repository, array &$emails)
    {
        foreach ($this->ugroup_dao->searchUgroupsByRepositoryId($repository->getId()) as $row) {
            $ugroup = $this->ugroup_manager->getUGroup($repository->getProject(), $row['ugroup_id']);
            if ($ugroup) {
                $this->addUgroupMembers($ugroup, $emails);
            }
        }
    }

    private function addUgroupMembers(ProjectUGroup $ugroup, array &$emails)
    {
        foreach ($ugroup->getMembers() as $user) {
            if ($user->isAlive()) {
                $emails[] = $user->getEmail();
            }
        }
    }
}
