<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/include/URLVerification.class.php';

class Tracker_URLVerification extends URLVerification {

    function getUrl() {
        return new Tracker_URL();
    }

    /**
     * Always permit requests for localhost, or for api and for system tracker templates
     *
     * @param Array $server
     *
     * @return Boolean
     */
    function isException($server) {
        $userRequestsDefaultTemplates = $server['REQUEST_URI'] == TRACKER_BASE_URL .'/index.php?group_id=100' && HTTPRequest::instance()->isAjax();
        $userRequestsDefaultTemplates |= $server['REQUEST_URI'] == TRACKER_BASE_URL .'/invert_comments_order.php';
        $userRequestsDefaultTemplates |= $server['REQUEST_URI'] == TRACKER_BASE_URL .'/invert_display_changes.php';
        $userRequestsDefaultTemplates |= $server['REQUEST_URI'] == TRACKER_BASE_URL .'/unsubscribe_notifications.php';
        $userRequestsDefaultTemplates |= (strpos($server['REQUEST_URI'], TRACKER_BASE_URL .'/config.php') === 0);

        return $userRequestsDefaultTemplates || parent::isException($server);
    }
    /**
     * Ensure given user can access given project
     *
     * @param PFUser  $user
     * @param Project $project
     * @return boolean
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessRestrictedException
     * @throws Project_AccessPrivateException
     */
    public function userCanAccessProject(PFUser $user, Project $project) {
        $tracker_manager = new TrackerManager();
        if ($tracker_manager->userCanAdminAllProjectTrackers($user)) {
            return true;
        }

        return parent::userCanAccessProject($user, $project);
    }

}
?>
