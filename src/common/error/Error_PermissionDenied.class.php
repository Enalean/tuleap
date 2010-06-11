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

require_Once('common/include/URL.class.php');
require_once('common/mail/Mail.class.php');
require_once('common/project/Project.class.php');
require_once('common/user/User.class.php');


class Error_PermissionDenied {
    /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct() {
        
    }

    /**
     * 
     * Returns the administrators' list of a given project
     *  
     * @param Project $project
     * 
     * @return string
     */
    function extractReceiver($project) {
        $admins = array();
        $um = $this->getUserManager();
        $sql = 'SELECT email FROM user u JOIN user_group ug USING(user_id) WHERE ug.admin_flags="A" AND u.status IN ("A", "R") AND ug.group_id = '.db_ei($project->getId());
        $res = db_query($sql);
        while ($row = db_fetch_array($res)) {
            $admins[] = $row['email'];
        }
        return implode(",", $admins);
    }

    /**
     * Prepare the mail inputs
     */
    function processMail($subject, $messageToAdmin) {
        $request =HTTPRequest::instance();
        
        $pm = $this->getProjectManager();
        $project = $pm->getProject($request->get('groupId'));
    
        $um = $this->getUserManager();
        $user = $um->getUserById($request->get('userId'));
        

        $messageToAdmin = trim($messageToAdmin);
        $messageToAdmin = ereg_replace("(\r\n)|(\n)","###", $messageToAdmin);
        
        $hrefApproval = get_server_url().'/project/admin/?group_id='.$request->get('groupId');
        
        $subject = $subject.$project->getPublicName();
        $body = "Dear Administrator(s),\n\n".
                        " The user ".$user->getRealName()." as(".$user->getName().") has no access to this data ".
                        $request->get('url_data').".\n".
                        " The concerned project is ".$project->getPublicName()." available here: ".$hrefApproval.".\n". 
                        " The user is not a member of your project. He requests to be a member and to have correct". 
                        " access right to consult the above data.\n".
                        " If you decide to accept the request, please take the appropriate actions to grant access ".
                        " and communicate that information to him.\n".
                        " Otherwise, please inform the requester that he will not get access to the requested data.\n\n".
                        " This is the requester message for you:\n\n".
                        $messageToAdmin."\n\n".
                        " This is an automatic message please do not reply.\n\n".
                        " Best regards,\n".
                        "-- The ".$GLOBALS['sys_name']." Team";
                        
        return $this->sendMail($project, $subject, $body);
    }
    
    /**
     * Send mail to administrators with the apropriate subject and body   
     * 
     * @param Project $project
     * @param String  $subject
     * @param String  $body
     */
    function sendMail($project, $subject, $body) {
        $to = $this->extractReceiver($project);
        $from = $GLOBALS['sys_noreply'];
        $hdrs = 'From: '.$from."\n";
        
        // Send a notification message to the project administrator(s)
        $mail = new Mail();
        $mail->setTo($to);
        $mail->setFrom($from);
        $mail->setBody($body);
        $mail->setSubject($subject);
       
        if (!$mail->send()) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
        } else {
            site_header(array('title'=>'')); 
            $GLOBALS['feedback'] .= "<p>Your request has been sent to project administrator. You will be informed about any news</p>";
            site_footer(array());
        }
       
    }

    /**
     * Get an instance of UserManager. Mainly used for mock
     * 
     * @return UserManager
     */
    protected function getUserManager() {
        return UserManager::instance();
    }

    /**
     * Get an instance of UserManager. Mainly used for mock
     * 
     * @return ProjectManager
     */
    protected function getProjectManager() {
        return ProjectManager::instance();
    }
    
}
?>