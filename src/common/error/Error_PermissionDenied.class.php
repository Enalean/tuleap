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

/**
 * It allows the management of permission denied error.
 * It offres to user the possibility to request the project membership directly.
 */
abstract class Error_PermissionDenied {

    /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct() {
        
    }

    /**
     * Returns the type of the error to manage
     *
     * @return String
     */
    abstract function getType();

    /**
     * Build the user interface to ask for membership
     * 
     * @param String $name
     * @param String $func
     * @param String $index
     */
    function buildInterface($name, $func, $index) {
        $url= new URL();
        $groupId =  (isset($GLOBALS['group_id'])) ? $GLOBALS['group_id'] : $url->getGroupIdFromUrl($_SERVER['REQUEST_URI']);
        $userId = $this->getUserManager()->getCurrentUser()->getId();
        
        echo "<b>".$GLOBALS['Language']->getText('include_exit','perm_denied')."</b>";
        echo '<br></br>';
        echo "<br>".$GLOBALS['Language']->getText('include_exit',$index);
        
        //In case of restricted user, we only show the zone text area to ask for membership 
        //just when the requested page belongs to a project
        if (!(($func == 'restricted_user_request') && (!isset($groupId)))) {
            echo $GLOBALS['Language']->getText('include_exit', 'request_to_admin');
            echo '<br></br>';
            echo '<form action="/sendmessage.php" method="post" name="display_form">
                  <textarea wrap="virtual" rows="5" cols="70" name="'.$name.'"></textarea></p>
                  <input type="hidden" id="func" name="func" value="'.$func.'">
                  <input type="hidden" id="groupId" name="groupId" value="' .$groupId. '">
                  <input type="hidden" id="userId" name="userId" value="' .$userId. '">
                  <input type="hidden" id="data" name="url_data" value="' .$_SERVER['REQUEST_URI']. '">
                  <br><input name="Submit" type="submit" value="'.$GLOBALS['Language']->getText('include_exit', 'send_mail').'"/></br>
              </form>';
        }
    }
    
    /**
     * 
     * Returns the administrators' list of a given project
     *  
     * @param Project $project
     * 
     * @return Array
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
     * @return String $messageToAdmin
     */
    function processMail($messageToAdmin) {
        $request =HTTPRequest::instance();
        
        $pm = $this->getProjectManager();
        $project = $pm->getProject($request->get('groupId'));
    
        $um = $this->getUserManager();
        $user = $um->getUserById($request->get('userId'));
        
        $messageToAdmin = trim($messageToAdmin);
        $messageToAdmin ='>'.$messageToAdmin;
        $messageToAdmin = str_replace(array("\r\n"),"\n>", $messageToAdmin);
        
        $hrefApproval = get_server_url().'/project/admin/?group_id='.$request->get('groupId');
        $urlData = get_server_url().$request->get('url_data');
        
        
        return $this->sendMail($project, $user, $urlData, $hrefApproval, $messageToAdmin);
    }
    


    /**
     * Send mail to administrators with the apropriate subject and body   
     * 
     * @param Project $project
     * @param User    $user
     * @param String  $urlData
     * @param String  $hrefApproval
     * @param String  $messageToAdmin
     */
    function sendMail($project, $user, $urlData, $hrefApproval,$messageToAdmin) {
        $adminList = $this->extractReceiver($project);
        $from = $user->getEmail();
        $hdrs = 'From: '.$from."\n";
        
        foreach ($adminList as $to => $lang) {
            // Send a notification message to the project administrator
            //according to his prefered language
            $mail = new Mail();
            $mail->setTo($to);
            $mail->setFrom($from);
 
            $language = new BaseLanguage($GLOBALS['sys_supported_languages'], $GLOBALS['sys_lang']);
            $language->loadLanguage($lang);
            
            $mail->setSubject($language->getText('include_exit', 'mail_subject_'.$this->getType(), array($project->getPublicName(), $user->getRealName())));
            
            $body = $language->getText('include_exit', 'mail_content_'.$this->getType(), array($user->getRealName(), $user->getName(), $urlData, $project->getPublicName(), $hrefApproval, $messageToAdmin, $user->getEmail()));
            $mail->setBody($body);
             
            if (!$mail->send()) {
                exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
            }
        }

        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('include_exit', 'request_sent'));
        $GLOBALS['Response']->redirect('/my');
        exit;
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