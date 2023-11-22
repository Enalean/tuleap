<?php
/**
 * Copyright Enalean (c) 2017-Present. All rights reserved.
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

namespace Tuleap\Git\Notifications;

use GitRepository;
use Tuleap\Notification\UserInvolvedInNotificationPresenter;

class CollectionOfUserToBeNotifiedPresenterBuilder
{
    /**
     * @var UsersToNotifyDao
     */
    private $dao;

    public function __construct(UsersToNotifyDao $dao)
    {
        $this->dao = $dao;
    }

    public function getCollectionOfUserToBeNotifiedPresenter(GitRepository $repository)
    {
        $presenters = [];
        foreach ($this->dao->searchUsersByRepositoryId($repository->getId()) as $row) {
            $user         = new \PFUser($row);
            $presenters[] = new UserInvolvedInNotificationPresenter(
                $row['user_id'],
                $row['user_name'],
                $row['realname'],
                $user->getAvatarUrl()
            );
        }
        $this->sortUsersAlphabetically($presenters);

        return $presenters;
    }

    private function sortUsersAlphabetically(&$presenters)
    {
        usort($presenters, function (UserInvolvedInNotificationPresenter $a, UserInvolvedInNotificationPresenter $b) {
            return strnatcasecmp($a->label, $b->label);
        });
    }
}
