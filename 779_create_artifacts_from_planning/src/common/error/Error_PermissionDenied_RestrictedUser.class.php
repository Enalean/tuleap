<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


require_once('Error_PermissionDenied.class.php');

class Error_PermissionDenied_RestrictedUser extends Error_PermissionDenied {

    function getType() {
        return 'restricted_user';
    }

    /**
     * Returns the parameters needed to build interface 
     * according to the classe which makes the call
     * 
     * @return Array
     */
    function returnBuildInterfaceParam() {
        $param = array();
        $param['name']   = 'msg_restricted_user';
        $param['func']   = 'restricted_user_request';
        $param['action'] = '/sendmessage.php';
        $param['index']  = 'restricted_user_no_perm';
        return $param;
    }

}
?>