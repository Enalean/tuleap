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

require_once('common/include/URL.class.php');
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
        $sql = 'SELECT email, language_id FROM user u JOIN user_group ug USING(user_id) WHERE ug.admin_flags="A" AND u.status IN ("A", "R") AND ug.group_id = '.db_ei($project->getId());
        $res = db_query($sql);
        while ($row = db_fetch_array($res)) {
            $admins[$row['email']] = $row['language_id'];
        }
        return $admins;
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
        $messageToAdmin ='>'.$messageToAdmin;
        $messageToAdmin = ereg_replace("(\r\n)|(\n)","\n>", $messageToAdmin);
        
        $hrefApproval = get_server_url().'/project/admin/?group_id='.$request->get('groupId');
        $urlData = $request->get('url_data');
        
        $subject = $subject.$project->getPublicName();
        return $this->sendMail($project, $subject, $user, $urlData, $hrefApproval,$messageToAdmin);
    }
    
    /**
     * Send mail to administrators with the apropriate subject and body   
     * 
     * @param Project $project
     * @param String  $subject
     * @param String  $body
     */
    function sendMail($project, $subject, $user, $urlData, $hrefApproval,$messageToAdmin) {
        $adminList = $this->extractReceiver($project);
        $from = $GLOBALS['sys_noreply'];
        $hdrs = 'From: '.$from."\n";
        foreach ($adminList as $to => $lang) {
            // Send a notification message to the project administrator
            //according to his prefered language
            $mail = new Mail();
            $mail->setTo($to);
            $mail->setFrom($from);
            $mail->setSubject($subject);
            $Language = new BaseLanguage($GLOBALS['sys_supported_languages'], $GLOBALS['sys_lang']);
            $Language->loadLanguage($lang);
            $body = $Language->getText('include_exit', 'mail_content', array($user->getRealName(), $user->getName(), 
                    $urlData, $project->getPublicName(), $hrefApproval, $messageToAdmin, $GLOBALS['sys_name']));
            $mail->setBody($body);
             
            if (!$mail->send()) {
                exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
            }
        }
        site_header(array('title'=>''));
        $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('include_exit', 'request_sent');
        site_footer(array());
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