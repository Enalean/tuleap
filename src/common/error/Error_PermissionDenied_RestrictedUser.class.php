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
    /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct() {
        parent::__construct();
    }


    /**
     * Dispaly interface to ask for membership
     */
    function displayInterface() {
        $url= new URL();
        $groupId =  (isset($GLOBALS['group_id'])) ? $GLOBALS['group_id'] : $url->getGroupIdFromUrl($_SERVER['REQUEST_URI']);
        $userId = $this->getUserManager()->getCurrentUser()->getId();
        
        echo "<b>".$GLOBALS['Language']->getText('include_exit','perm_denied')."</b>";
        echo '<br></br>';
        echo "<br>".$GLOBALS['Language']->getText('include_exit','restricted_user_no_perm');
        echo $GLOBALS['Language']->getText('include_exit', 'request_to_admin');
        
        echo '<br></br>';
        echo '<form action="/sendmessage.php" method="post" name="display_form">
                 <textarea wrap="virtual" rows="5" cols="70" name="msg_restricted_user"></textarea></p>
                 <input type="hidden" id="func" name="func" value="restricted_user_request">
                 <input type="hidden" id="groupId" name="groupId" value="' .$groupId. '">
                 <input type="hidden" id="userId" name="userId" value="' .$userId. '">
                 <input type="hidden" id="data" name="url_data" value="' .$_SERVER['SCRIPT_URI']. '">
                 <br><input name="Submit" type="submit" value="'.$GLOBALS['Language']->getText('include_exit', 'send_mail').'"/></br>';
        echo '</form>';
    }

}
?>

