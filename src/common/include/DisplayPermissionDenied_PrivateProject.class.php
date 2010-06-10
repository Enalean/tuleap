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


require_once('DisplayPermissionDenied.class.php');

class DisplayPermissionDenied_PrivateProject extends DisplayPermissionDenied {
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
        echo "<br>".$GLOBALS['Language']->getText('include_exit','private_project_no_perm');
        echo "<em> Or you can request to be added as member to this project by just adding a message here and hinting send button.</em>";
        
        echo '<br></br>';
        echo '<form action="/sendmessage.php" method="POST" name="display_form"  enctype="multipart/form-data">
                 <textarea wrap="virtual" rows="5" cols="70" name="admin_msg"></textarea></p>
                 <input TYPE="HIDDEN" id="groupId" name="group_id" VALUE="' .$groupId. '">
                 <input TYPE="HIDDEN" id="userId" name="user_id" VALUE="' .$userId. '">
                 <br><input name="Submit" type="submit" value="Send"/></br>';
        echo '</form>';
    }


    /**
     * Prepare the mail inputs
     */
    function customizeMessage() {
        $request =HTTPRequest::instance();
        
        $pm = $this->getProjectManager();
        $project = $pm->getProject($request->get('group_id'));
    
        $um = $this->getUserManager();
        $user = $um->getUserById($request->get('user_id'));
        

        $messageToAdmin = trim($request->get('admin_msg'));
        $messageToAdmin = ereg_replace("(\r\n)|(\n)","###", $messageToAdmin);
        
        $subject = "Request for private project membership: ".$project->getPublicName();
        $body = $user->getRealName()." as(".$user->getName().") has just asked to be member of ".$project->getPublicName()."\n\n".
                $user->getName()." has added his personal message:  ".$messageToAdmin."\n\n".
                " Please take the appropriate actions to grant him access or not and communicate that information to him \n\n";
        return $this->sendMail($project, $subject, $body);
    }
 
}
?>
