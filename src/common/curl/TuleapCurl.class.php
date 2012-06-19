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

/**
 * Wrapper for cURL operations
 */
class TuleapCurl {

    /**
     * Perform cURL request
     *
     * @param String  $url           URL of the command to execute
     * @param Boolean $includeHeader If true display the header in returned output
     * @param String  $authUser      Username if an authentication is required
     * @param String  $authPassword  Password for the authentication
     * @param Array   $postfields    Fields to send if the action is a post
     *
     * @return Array
     */
    public function execute($url, $includeHeader = false,$authUser = null, $authPassword = null, $postfields = null) {
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($authUser) && !empty($authPassword)) {
            curl_setopt($ch, CURLOPT_USERPWD, $authUser.':'.$authPassword);
        }
        if (!empty($postfields)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields, "", "&"));
        }
        if ($includeHeader) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        $return = json_decode(curl_exec($ch), true);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);
        return array('return' => $return, 'status' => $status, 'error' => $error);
    }
    


}

?>