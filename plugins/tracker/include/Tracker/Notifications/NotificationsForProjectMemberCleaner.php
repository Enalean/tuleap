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

namespace Tuleap\Tracker\Notifications;

use PFUser;
use Project;
use Tracker_NotificationsManager;
use TrackerFactory;

class NotificationsForProjectMemberCleaner
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_NotificationsManager
     */
    private $emails_to_notify_manager;

    /**
     * @var UsersToNotifyDao
     */
    private $users_to_notify_dao;

    public function __construct(
        TrackerFactory $tracker_factory,
        Tracker_NotificationsManager $email_to_notify_manager,
        UsersToNotifyDao $users_to_notify_dao
    ) {
        $this->tracker_factory          = $tracker_factory;
        $this->emails_to_notify_manager = $email_to_notify_manager;
        $this->users_to_notify_dao      = $users_to_notify_dao;
    }

    public function cleanNotificationsAfterUserRemoval(Project $project, PFUser $user)
    {
        if ($project->isPublic()) {
            return;
        }

        if ($user->isMember($project->getID())) {
            return;
        }

        $trackers = $this->tracker_factory->getTrackersByGroupId($project->getID());
        foreach ($trackers as $tracker) {
            if (! $tracker->userCanView($user)) {
                $this->emails_to_notify_manager->removeAddressByTrackerId($tracker->getId(), $user);
                $this->users_to_notify_dao->deleteByTrackerIdAndUserId($tracker->getId(), $user->getId());
            }
        }
    }
}
