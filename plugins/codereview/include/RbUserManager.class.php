<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once 'common/curl/TuleapCurl.class.php';

/**
 * Manager of RB users
 */
class RbUserManager {

    /**
     * Search RB user
     *
     * @param String  $url           URL of the command to execute
     * @param Boolean $includeHeader If true display the header in returned output
     * @param String  $authUser      Username if an authentication is required
     * @param String  $authPassword  Password for the authentication
     * @param Array   $postfields    Fields to send if the action is a post
     * @param String  $username      Login of the user to search
     *
     * @return Boolean
     */
    public function searchUser($url, $includeHeader = false, $authUser = null, $authPassword = null, $postfields = null, $username) {
        $curl   = new TuleapCurl();
        $result = $curl->execute($url, $authUser, $authPassword, $postfields, $includeHeader);
        // @TODO: Handle errors
        $users  = $result['return']['users'];
        foreach ($users as $user) {
            if ($user['username'] == $username) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve User rbsessionid
     *
     * @param String  $url           URL of the command to execute
     * @param String  $authUser      Username for whom we retrieve his rbsessionid
     * @param String  $authPassword  Password of the user
     *
     * @return String
     */
    public function retrieveRbSessionId($url, $authUser = null, $authPassword = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $authUser.":".$authPassword);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        // @TODO: Handle errors
        preg_match('/rbsessionid=(.*?);/', $result, $matches);
        $rbsessionid = $matches[1];
        return $rbsessionid;
    }

}

?>