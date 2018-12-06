<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Notifications;

use PFUser;
use Project;
use Tuleap\SVN\Admin\MailNotificationDao;

class NotificationsForProjectMemberCleaner
{
    /**
     * @var UsersToNotifyDao
     */
    private $user_dao;
    /**
     * @var MailNotificationDao
     */
    private $notification_dao;

    public function __construct(UsersToNotifyDao $user_dao, MailNotificationDao $notification_dao)
    {
        $this->user_dao         = $user_dao;
        $this->notification_dao = $notification_dao;
    }

    public function cleanNotificationsAfterUserRemoval(Project $project, PFUser $user)
    {
        if ($project->isPublic()) {
            return;
        }

        if ($user->isMember($project->getID())) {
            return;
        }

        $this->user_dao->deleteUserFromAllNotificationsInProject($user->getId(), $project->getID());
        $this->notification_dao->deleteEmptyNotificationsInProject($project->getID());
    }
}
