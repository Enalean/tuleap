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
     * Returns the base on language file
     *
     * @return String
     */
    function getTextBase() {
        return 'include_exit';
    }
    
    
     /**
     * Returns the build interface parameters
     *
     * @return Array
     */
    abstract function returnBuildInterfaceParam();
    
    /**
     * Returns the url link after modification if needed else returns the same string
     *  
     * @param String $link
     * @param BaseLanguage $language
     */
    function getRedirectLink($link, $language) {
        return $link;
    }

    /**
     * Build the user interface to ask for membership
     * 
     */
    function buildInterface() {
        $url= new URL();
        $groupId =  (isset($GLOBALS['group_id'])) ? $GLOBALS['group_id'] : $url->getGroupIdFromUrl($_SERVER['REQUEST_URI']);
        $userId = $this->getUserManager()->getCurrentUser()->getId();
        
        $param = $this->returnBuildInterfaceParam();
        
        echo "<b>".$GLOBALS['Language']->getText($this->getTextBase(), 'perm_denied')."</b>";
        echo '<br></br>';
        echo "<br>".$GLOBALS['Language']->getText($this->getTextBase(), $param['index']);
        
        //In case of restricted user, we only show the zone text area to ask for membership 
        //just when the requested page belongs to a project
        if (!(($param['func'] == 'restricted_user_request') && (!isset($groupId)))) {
            $message = $GLOBALS['Language']->getText('project_admin_index', 'member_request_delegation_msg_to_requester');
            $pm = ProjectManager::instance();
            $dar = $pm->getMessageToRequesterForAccessProject($groupId);
            if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                if ($row['msg_to_requester'] != "member_request_delegation_msg_to_requester" ) {
                    $message = $row['msg_to_requester'];
                }
            } 
            echo $GLOBALS['Language']->getText($this->getTextBase(), 'request_to_admin');
            echo '<br></br>';
            echo '<form action="'.$param['action'].'" method="post" name="display_form">
                  <textarea wrap="virtual" rows="5" cols="70" name="'.$param['name'].'">'.$message.' </textarea></p>
                  <input type="hidden" id="func" name="func" value="'.$param['func'].'">
                  <input type="hidden" id="groupId" name="groupId" value="' .$groupId. '">
                  <input type="hidden" id="userId" name="userId" value="' .$userId. '">
                  <input type="hidden" id="data" name="url_data" value="' .$_SERVER['REQUEST_URI']. '">
                  <br><input name="Submit" type="submit" value="'.$GLOBALS['Language']->getText('include_exit', 'send_mail').'"/></br>
              </form>';
        }
    }

    /**
     * Return the sql request retreiving project admins of given project
     * 
     * @param Integer $groupId
     * 
     * @return String
     */
    function returnProjectAdminsByGroupId($groupId) {
        $sql = 'SELECT u.email as email  FROM user u
                    JOIN user_group ug 
                    USING(user_id) 
                    WHERE ug.admin_flags="A" 
                    AND u.status IN ("A", "R") 
                    AND ug.group_id ='.db_ei($groupId);
        return $sql;
    }

    /**
     * Return the sql request retreiving project admins of given static group
     * 
     * @param Integer $groupId
     * @param Array $ugroups
     * 
     * @return String
     */
    function retrieveAdminProjectByStaticUGroupId($groupId, $ugroups) {
        $sql = 'SELECT u.email as email FROM user u
                    JOIN ugroup_user uu 
                    USING(user_id)
                    JOIN user_group ug 
                    USING(user_id) 
                    WHERE ug.admin_flags="A" 
                    AND u.status IN ("A", "R") 
                    AND ug.group_id ='.db_ei($groupId).' 
                    AND u.status IN ("A", "R") 
                    AND uu.ugroup_id IN ('.implode(",",$ugroups).')';
        return $sql;
    }

    /**
     *
     * Returns the administrators' list of a given project
     *
     * @param Project $project
     *
     * @return Array
     */
    function extractReceiver($project, $urlData) {
        $admins = array();
        $status  = true;
        $pm = ProjectManager::instance();
        $res = $pm->getMembershipRequestNotificationUGroup($project->getId());
        if ($res && !$res->isError()) {
            if ($res->rowCount() == 0) {
                $sql = $this->returnProjectAdminsByGroupId($project->getId());
            } else {
                /* We can face one of these composition for ugroups array:
                 * 1 - UGROUP_PROJECT_ADMIN
                 * 2 - UGROUP_PROJECT_ADMIN, UGROUP_1, UGROUP_2,.., UGROUP_n
                 * 3 - UGROUP_1, UGROUP_2,.., UGROUP_n
                 */
                $ugroups = array();
                foreach ($res as $row) {
                    if ($row['ugroup_id'] == $GLOBALS['UGROUP_PROJECT_ADMIN']) {
                        $stm [] = $this->returnProjectAdminsByGroupId($project->getId());
                    } else {
                        $ugroups[] = $row['ugroup_id'];
                    }
                }
                if (count($ugroups) > 0) {
                    $stm[] = $this->retrieveAdminProjectByStaticUGroupId($project->getId(), $ugroups);
                }
                $sql =  implode(" UNION ",$stm);
            }

            $res = db_query($sql);
            if (db_numrows($res) > 0) {
                while ($row = db_fetch_array($res)) {
                    $admins[] = $row['email'];
                }
            } else {
                $status = false;
                $res = db_query($this->returnProjectAdminsByGroupId($project->getId()));
                while ($row = db_fetch_array($res)) {
                    $admins[] = $row['email'];
                }
            }
        }
        return array('admins' => $admins, 'status' => $status);
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
        $mail = new Mail();

        //to
        $adminList = $this->extractReceiver($project, $urlData);
        $to = implode(',', $adminList['admins']);
        $mail->setTo($to);

        //from
        $from = $user->getEmail();
        $hdrs = 'From: '.$from."\n";
        $mail->setFrom($from);

        $mail->setSubject($GLOBALS['Language']->getText($this->getTextBase(), 'mail_subject_'.$this->getType(), array($project->getPublicName(), $user->getRealName())));

        $link = $this->getRedirectLink($urlData, $GLOBALS['Language']);
        $body = $GLOBALS['Language']->getText($this->getTextBase(), 'mail_content_'.$this->getType(), array($user->getRealName(), $user->getName(), $link, $project->getPublicName(), $hrefApproval, $messageToAdmin, $user->getEmail()));
        if ($adminList['status']== false) {
            //The mail body will be changed
            $body .= "\n\n". $GLOBALS['Language']->getText($this->getTextBase(), 'mail_content_unvalid_ugroup', array($project->getPublicName()));
        }
        $mail->setBody($body);

        if (!$mail->send()) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
        }

        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('include_exit', 'request_sent'), CODENDI_PURIFIER_DISABLED);
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