<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Arnaud Salvucci, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('pre.php');
require_once('common/admin/view/AdminSearchDisplay.class.php');
require_once('common/admin/view/user/UserSearchDisplay.class.php');
require_once('common/admin/view/user/UserEditDisplay.class.php');
require_once('common/admin/view/user/UserChangeNameDisplay.class.php');
require_once('www/admin/user/UserAutocompletionForm.class.php');
require_once('common/dao/CodexDataAccess.class.php');
require_once('common/dao/UserDao.class.php');
require_once('common/dao/GroupDao.class.php');
require_once('common/mvc/Controler.class.php');


class UserControler extends Controler {

    /**
     * $userIterator
     *
     * @type mixed $userIterator
     */
    private $userIterator;

    /** 
     * $limit
     *
     * @type int $limit
     */
    private $limit;

    /**
     * $offset
     *
     * @type int $offset
     */
    private $offset;

    /**
     * $nbuser
     *
     * @type int $nbuser
     */
    private $nbuser;

    /**
     * $userparam an array that contains the params of a user (for the editing mode)
     *
     * @type array $userparam
     */
    private $userparam;
    
    /**
     * $userid 
     *
     * @type int $userid
     */
    private $userid;

    /**
     * $groupid
     *
     * @type int $groupid
     */
    private $groupid;

    /**
     * $task
     *
     * @type string $task
     */
    private $task;

    /**
     * $adminflag
     *
     * @type string $adminflag
     */
    private $adminflag;

    /**
     * $shortcut
     *
     * @type string $shortcut
     */
    private $shortcut;

    /**
     * $username
     *
     * @type string $username
     */
    private $username;

    /**
     * $group
     *
     * @type string $group
     */
    private $group;

    /**
     * $status
     *
     * @type string $status
     */
    private $status;

    /**
     * $groupparam
     *
     * @type mixed $groupparam
     */
    private $groupparam;

    /**
     * $newUserName
     *
     * @type string $newUserName
     */
    private $newUserName;

    /**
     * $displayDirection
     *
     * @type boolean $displayDirection
     */
    private $displayDirection;


    /**
     * constructor
     *
     */    
    function __construct() {
        $GLOBALS['Language']->loadLanguageMsg('admin/admin');
    }

    /**
     * viewManagement()
     */
    function viewsManagement() {

        if ($this->userid) {
            if ($this->task == 'change_user_name') {
                $view = new UserChangeNameDisplay($this->userparam, $this->groupparam, $this->task, $this->displayDirection, $this->newUserName);
            }
            else {
                $view = new UserEditDisplay($this->userparam, $this->groupparam, $this->task);
            }
        }
        else {
            $view = new UserSearchDisplay($this->userIterator,$this->offset,$this->limit, $this->nbuser, $this->shortcut, $this->username, $this->group, $this->status);
        }
        $view->display();
    }

    /**
     * setNbUser()
     */
    function setNbUser() {
        $dao = new UserDao(CodexDataAccess::instance());
        $this->nbuser = $dao->getFoundRows();
    }

    /**
     * setOffset()
     */
    function setOffset() {

        $request =& HTTPRequest::instance();

        $validoffset = new Valid_UInt('offset');
        $validoffset->required();

        if ($request->valid($validoffset)) {
            $offset = $request->get('offset');
            $this->offset = $offset;
        }
        else {
            $this->offset = 0;
        }
    }

    /**
     * setLimit()
     */
    function setLimit() {

        $request =& HTTPRequest::instance();

        //valid parameters

        //valid limit
        $limit = '';
        $validLimit = new Valid_UInt('limit');

        if($request->valid($validLimit)) {
            $limit = $request->get('limit');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_limit'));
        }

        //valid nbtodisplay
        $nbtodisplay = '';
        $validNbToDisplay = new Valid_UInt('nbtodisplay');

        if($request->valid($validNbToDisplay)) {
            $nbtodisplay = $request->get('nbtodisplay');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_limit'));
        }

        if ($nbtodisplay != '') {
            $this->limit = $nbtodisplay;
        }
        elseif ($limit != '') {
            $this->limit = $limit;
        }
        else {
            $this->limit = 50;
        }
    }

    /**
     * setUserParam
     */
    function setUserParam($userid) {

        $dao = new UserDao(CodexDataAccess::instance());

        if(is_array($userid)) {
            foreach($userid as $uid) {
                $dar = $dao->searchByUserId($uid);
                $userparam[] = $dar->getRow();
            }
        }
        else {
            $dar = $dao->searchByUserId($userid);
            $userparam = $dar->getRow();
        }
        $this->userparam = $userparam;
    }

    /**
     * setGroupParam
     */
    function setGroupParam($userid) {

        $dao = new UserDao(CodexDataAccess::instance());

        if(is_array($userid)) {
            $userid = implode(",", $userid);
        }

        $groupparam = $dao->searchGroupByUserId($userid);
        $this->groupparam = $groupparam;
    }

    /**
     * setUserIterator()
     */
    function setUserIterator() {

        $dao = new UserDao(CodexDataAccess::instance());        
        $filter = array();
        $request =& HTTPRequest::instance();

        //define white lists for parameters
        $shortcutWhiteList = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

        $statusWhiteList = array('all', 'A', 'R', 'V', 'P', 'D', 'W', 'S');

        //valid parameters

        //valid shortcut
        $validShortcut = new Valid('user_shortcut_search');
        $validShortcut->addRule(new Rule_WhiteList($shortcutWhiteList));

        if($request->valid($validShortcut)) {
            $this->shortcut = $request->get('user_shortcut_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_shortcut'));
        }

        //valid user name
        $validUserName = new Valid_String('user_name_search');

        if ($request->valid($validUserName)) {
            $this->username = $request->get('user_name_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_username'));
        }

        //valid user group
        $validUserGroup = new Valid_String('user_group_search');

        if ($request->valid($validUserGroup)) {
            $this->group = $request->get('user_group_search');
            $this->group = explode(',', $this->group);
            $this->group = $this->group[0];

            if ( preg_match('#^.*\((.*)\)$#',$this->group, $matches)) {
                $this->group = $matches[1];
            }
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_group_name'));
        }

        //valid status
        $validStatus = new Valid('user_status_search');                
        $validStatus->addRule(new Rule_WhiteList($statusWhiteList));

        if ($request->valid($validStatus)) {
            $this->status = $request->get('user_status_search');                
        }
        else{
            $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('admin_user_controler','wrong_status'));
        }

        if ($this->shortcut != '') {
            $filter[] = new UserShortcutFilter($this->shortcut);
        }
        if ($this->username != '') {
            $filter[] = new UserNameFilter($this->username);
        }
        if ($this->group != '') {
            $filter[] = new UserGroupFilter($this->group);
        }
        if ($this->status != '' && $this->status != 'all') {
            $filter[] = new UserStatusFilter($this->status);
        }
        $this->userIterator = $dao->searchUserByFilter($filter, $this->offset, $this->limit);    
     }

    /**
     * add user to a group
     */
    function addUserToGroup() {

        $dao = new UserDao(CodexDataAccess::instance());

        $groupdao = new GroupDao(CodexDataAccess::instance());
        $filter = array();

        if(!$this->userid) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'error_nouid'));
        }        
        elseif(!$this->groupid) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'error_nogid'));
        }
        else {
            //look for group that match with this groupid
            $filter[] = new GroupIdFilter($this->groupid);
            $groupdao->searchGroupByFilter($filter);
            
            //if the group doesn't exist
            if(!$dao || $dao->getFoundRows() <1) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','error_noadd'));
            }
            else {
                $dao->searchUserInUserGroup($this->userid, $this->groupid);
                
                //if user doesn't belong to this group
                if (!$dao || $dao->getFoundRows() < 1) {
                    $dao->addUserToGroup($this->userid, $this->groupid);
                    
                    //if there is problem in adding user to this group
                    if (!$dao || $dao->getFoundRows() < 1) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','error_add_ug'));
                    } 
                    else {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_user_controler','success_add_ug'));
                        $GLOBALS['Response']->redirect('/admin/user/index.php?user_id='.$this->userid);
                    }
                } 
                else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','error_member',$this->groupid));
                }
            }
        }
    }

    /**
     * update user
     */
    function updateUser() {

        $request =& HTTPRequest::instance();
        //valid parameters

        //valid shell
        $shell = '';
        $shellWhiteList = array('/bin/sh', '/bin/bash', '/sbin/nologin', '/bin/bash2', '/bin/ash', '/bin/bsh', '/bin/tcsh', '/bin/csh', '/bin/zsh');

        $validShell = new Valid('shell');
        $validShell->addRule(new Rule_WhiteList($shellWhiteList));

        if ($request->valid($validShell)) {
            $shell = $request->get('shell');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_shell'));
        }

        //valid codex status
        $cidexstatus = '';
        $codexStatusWhiteList = array('A', 'R', 'V', 'P', 'D', 'S');

        $validCodexStatus = new Valid('codexstatus');
        $validCodexStatus->addRule(new Rule_WhiteList($codexStatusWhiteList));

        if ($request->valid($validCodexStatus)) {
            $codexstatus = $request->get('codexstatus');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_status'));
        }

        //valid unix status
        $unixstatus = '';
        $unixStatusWhiteList = array('N', 'A', 'S', 'D');

        $validUnixStatus = new Valid('unixstatus');
        $validUnixStatus->addRule(new Rule_WhiteList($unixStatusWhiteList));

        if ($request->valid($validUnixStatus)) {
            $unixstatus = $request->get('unixstatus');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_unix_status'));
        }

        //valid email
        $email = '';
        $validEmail = new Valid_Email('email');

        if ($request->valid($validEmail)) {
            $email = $request->get('email');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_email'));
        }

        //valid date
        $validExpiryDate = new Valid('expiry_date');
        $validExpiryDate->addRule(new Rule_Date());

        if ($request->valid($validExpiryDate)) {
            $expirydate = $request->get('expiry_date');
        }
        else {           
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','data_not_parsed'));
        }

        $dao = new UserDao(CodexDataAccess::instance());

        if ($shell != '' && $codexstatus != '' && $unixstatus != '' && ($email != '' || is_array($this->userid)) && isset($expirydate)) {

            $date = util_date_to_unixtime($expirydate);

            $dao->checkUnixUid($this->userid);

            $unixuidexist = $dao->getFoundRows();

            // create unix uid if it doesn't exists
            if ($unixuidexist <= 0 && $unixstatus != 'N') {
                $dao->createUnixUid($this->userid);
            }

            //update
            $dao->updateUser($this->userid, $shell, $codexstatus, $unixstatus, $email, $date[0]);

            //Update in plugin
            require_once('common/event/EventManager.class.php');
            $em =& EventManager::instance();
            $em->processEvent('usergroup_update', array('HTTP_POST_VARS' =>  $_POST,
                                                     'user_id' => $this->userid )); 

            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_user_controler', 'success_upd_u'));
        }

        if (is_array($this->userid)) {
            $GLOBALS['Response']->redirect('/admin/user/index.php');
        }
        else {
            $GLOBALS['Response']->redirect('/admin/user/index.php?user_id='.$this->userid);
        }
    }

    /**
     * This method init the new user name
     */
    function setNewUserName() {
        $request =& HTTPRequest::instance();

        //valid new user name
        $validNewUserName = new Valid_String('new_user_name');

        $this->newUserName = '';
        if ($request->valid($validNewUserName)) {
            $this->newUserName = $request->get('new_user_name');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','user_new_name'));
        }
    }


    /**
     * This method check if the new user name doesn't exist yet
     */
    function checkUserNameExist() {

        $dao = new UserDao(CodexDataAccess::instance());        
        $filter = array();

        if($this->newUserName != '') {
            $filter[] = new FullUserNameFilter($this->newUserName);
            $dao->searchUserByFilter($filter);
            $newUserNameExist = $dao->getFoundRows();

            if($newUserNameExist == 0) {
                $this->displayDirection = true;
            }
            else {
                $this->displayDirection = false;
                $GLOBALS['Response']->addFeedBack('error', $GLOBALS['Language']->getText('admin_user_controler','user_name_exists'));
            }
        }
    }

    /**
     * check if user follow the direction to change user name
     */
    function checkUserNameDirection() {

        if(isset($this->userparam['user_name'])) {
            $oldusername = $this->userparam['user_name'];
        }
        elseif(count($this->userparam) == 1) {
            $oldusername = $this->userparam[0]['user_name'];
        }

        //        $old = 'user4'; //ancien nom

        //$new = 'user4'; //nouveau nom ils ont la meme valeur  pour mes tests car je ne fait pas le changement pour l'instant

        //$group = 'Grtest4'; //nom du group

        if($f = fopen('./../../../../../../../etc/group','r')) {

          
            //appelle au dao pour récupérer les groupe auquelle l'utiilsateur appartient
            $dao = new UserDao(CodexDataAccess::instance());
            $filter = array();

            $filter[] = new UserGroupByNameFilter($oldusername);
            $userGroup = $dao->searchUserByFilter($filter);

                 

            $line = file('./../../../../../../../etc/group');

            $oldNamePattern = '#'.$oldusername.'#'; //pattern pour vérifié la présence de l'ancien nom dans le fichier

            $newNamePattern = '#^'.$this->newUserName.'.*'.$this->newUserName.'$#'; //pattern pour vérifier la présence du user avec le group du meme nom

           //  foreach ($line as $l) {
//                 if(preg_match($oldNamePattern,$l)) {
//                     $GLOBALS['Response']->addFeedBack('error','You must remove the old user name in file /etc/group');
//                 }
//                 else {
//                     //echo 'OK plus d\'ancien nom<br />';
//                 }
//             }


//             //Test if the new user name is uniaue in /etc/group
//             $inewName = 0;
//             foreach($line as $l) {
//                 if(preg_match($newNamePattern, $l)) {
//                     $inewName++;
//                 }
//             }
//             if($inewName >1) {
//                 $GLOBALS['Response']->addFeedBack('error','This user must be unique in file /etc/group');
//             }

            //test if the user belong to his group
            foreach($line as $l) {

                foreach($userGroup as $ug) {
                    $userGroupPattern = '#^'.$ug['unix_group_name'].'.*'.$this->newUserName.'#';
                    if(preg_match($userGroupPattern,$l)) {
                        echo 'ce quon recupere '.$l.'<br />';
                    }
                    
                    
                }
                if(preg_match('#[^'.$ug['unix_group_name'].'.]*'.$this->newUserName.'#',$l)) {
                    echo $l.'<br />';
                    $GLOBALS['Response']->addFeedBack('error','This user is contains in a group that he don\'t belong to');
                    
                }
                
                
            }
        }
        else {
            $GLOBALS['Response']->addFeedBack('error', 'This file cannot be opened');
        }

    }

    /**
     * replace the old user name by the new one in the database
     */
    function changeUserNameInDB() {

        if(isset($this->userparam['user_name'])) {
            $oldusername = $this->userparam['user_name'];
        }
        elseif(count($this->userparam) == 1) {
            $oldusername = $this->userparam[0]['user_name'];
        }

        $dao = new UserDao(CodexDataAccess::instance());
        $dao->changeName($oldusername, $this->newUserName);
        $dao->changeEmail($oldusername, $this->newUserName);
        $dao->changeArtifact($oldusername, $this->newUserName);
        $dao->changeWikiPage($oldusername, $this->newUserName);
        $dao->changeSupportMessage($oldusername, $this->newUserName);
        $dao->changeBug($oldusername, $this->newUserName);
        $dao->changeProject($oldusername, $this->newUserName);
    }

    /**
     * This method call other methods that check one specific point of the direction to change user name
     */
    function changeUserName() {

        $this->checkUserNameDirection();

        //$this->changeUserNameInDB();
    }

    /**
     * request()
     */
    function request() {

        $request =& HTTPRequest::instance();

        //valid parameters

        //valid user id
        $validUserId = new Valid_UInt('user_id');

        if ($request->validArray($validUserId) || $this->userid == '') {
            $this->userid = $request->get('user_id');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_uid'));
        }

        //valid group id
        $validGroupId = new Valid_UInt('group_id');

        if ($request->valid($validGroupId)) {
            $this->groupid = $request->get('group_id');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_gid'));
        }


        //valid task
        $validTask = new Valid_String('task');

        if ($request->valid($validTask)) {
            $this->task = $request->get('task');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler','wrong_task'));
        }

        if ($this->userid) {            
            $this->setUserParam($this->userid);
            $this->setGroupParam($this->userid);
        }

        if ($this->task) {

            if($this->task == 'add_user_to_group') {
                $this->addUserToGroup();
                $this->setUserParam($this->userid);
                $this->setGroupParam($this->userid);
            }
            elseif($this->task == 'update_user') {
                $this->updateUser();
                $this->setUserParam($this->userid);
                $this->setGroupParam($this->userid);
            }
            elseif($this->task == 'change_user_name') {
                $this->setNewUserName();
                $this->checkUserNameExist();
            }
            elseif($this->task == 'check_instruction') {            
                $this->setNewUserName();
                $this->changeUserName();
            }
        }

        if ($this->task != 'change_user_name') {
            $this->setOffset();        

            $this->setLimit();

            $this->setUserIterator();

            $this->setNbUser();
        }
    }
}

?>
