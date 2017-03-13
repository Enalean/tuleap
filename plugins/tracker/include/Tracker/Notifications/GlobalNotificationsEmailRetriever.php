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

use ProjectUGroup;
use Tracker_GlobalNotification;
use TrackerFactory;
use UGroupManager;

class GlobalNotificationsEmailRetriever
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
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        UsersToNotifyDao $user_dao,
        UgroupsToNotifyDao $ugroup_dao,
        UGroupManager $ugroup_manager,
        TrackerFactory $tracker_factory
    ) {
        $this->user_dao        = $user_dao;
        $this->ugroup_dao      = $ugroup_dao;
        $this->ugroup_manager  = $ugroup_manager;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @return string[]
     */
    public function getNotifiedEmails(Tracker_GlobalNotification $notification)
    {
        $emails = $this->transformNotificationAddressesStringAsArray($notification);
        $this->addUsers($notification, $emails);
        $this->addUgroups($notification, $emails);

        return array_unique($emails);
    }

    private function addUsers(Tracker_GlobalNotification $notification, array &$emails)
    {
        foreach ($this->user_dao->searchUsersByNotificationId($notification->getId()) as $row) {
            $emails[] = $row['email'];
        }
    }

    private function addUgroups(Tracker_GlobalNotification $notification, array &$emails)
    {
        $tracker = $this->tracker_factory->getTrackerById($notification->getTrackerId());
        if ($tracker) {
            $project = $tracker->getProject();
            foreach ($this->ugroup_dao->searchUgroupsByNotificationId($notification->getId()) as $row) {
                $ugroup = $this->ugroup_manager->getUGroup($project, $row['ugroup_id']);
                if ($ugroup) {
                    $this->addUgroupMembers($ugroup, $emails);
                }
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

    /**
     * @return string[]
     */
    private function transformNotificationAddressesStringAsArray(Tracker_GlobalNotification $notification)
    {
        $addresses = $notification->getAddresses();
        $addresses = preg_split('/[,;]/', $addresses);
        $addresses = array_map('trim', $addresses);
        return $addresses;
    }
}
