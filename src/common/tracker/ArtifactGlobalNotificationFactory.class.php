<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


/**
* ArtifactGlobalNotificationFactory
*/
class ArtifactGlobalNotificationFactory
{

    public function getGlobalNotificationsForTracker($tracker_id)
    {
        $notifs = array();
        $dao = new ArtifactGlobalNotificationDao(CodendiDataAccess::instance());
        $dar = $dao->searchByTrackerId($tracker_id);
        if ($dar) {
            $notifs = array();
            while ($dar->valid()) {
                $row = $dar->current();
                $notifs[$row['id']] = new ArtifactGlobalNotification($row);
                $dar->next();
            }
        }
        return $notifs;
    }

    public function addGlobalNotificationForTracker($tracker_id)
    {
        $dao = new ArtifactGlobalNotificationDao(CodendiDataAccess::instance());
        return $dao->create($tracker_id, '', 0, 1);
    }
    public function removeGlobalNotificationForTracker($global_notification_id, $tracker_id)
    {
        $dao = new ArtifactGlobalNotificationDao(CodendiDataAccess::instance());
        return $dao->delete($global_notification_id, $tracker_id);
    }
    public function updateGlobalNotification($global_notification_id, $data)
    {
        $dao = new ArtifactGlobalNotificationDao(CodendiDataAccess::instance());
        $feedback = '';
        $arr_email_address = preg_split('/[,;]/D', $data['addresses']);
        if (!util_validateCCList($arr_email_address, $feedback, false)) {
            $GLOBALS['Response']->addFeedback('error', $feedback);
        } else {
            $data['addresses'] = util_cleanup_emails(implode(', ', $arr_email_address));
            return $dao->modify($global_notification_id, $data);
        }
        return false;
    }
    /**
     * @param bool $update true if the action is an update one (update artifact, add comment, ...) false if it is a create action.
     */
    public function getAllAddresses($tracker_id, $update = false)
    {
        $addresses = array();
        $notifs = $this->getGlobalNotificationsForTracker($tracker_id);
        foreach ($notifs as $key => $nop) {
            if (!$update || $notifs[$key]->isAllUpdates()) {
                foreach (preg_split('/[,;]/D', $notifs[$key]->getAddresses()) as $address) {
                    $addresses[] = array('address' => $address, 'check_permissions' => $notifs[$key]->isCheckPermissions());
                }
            }
        }
        return $addresses;
    }
}
