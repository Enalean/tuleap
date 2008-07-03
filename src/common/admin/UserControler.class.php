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
require_once('www/admin/user/UserAutocompletionForm.class.php');
require_once('common/dao/CodexDataAccess.class.php');
require_once('common/dao/UserDao.class.php');
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
     * $action
     *
     * @type string $action
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
            $view = new UserEditDisplay($this->userparam, $this->groupparam, $this->task);
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

        $validoffset = new valid('offset');
        $validoffset->required();
        $validoffset->addRule(new Rule_Int());
        
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
        $validLimit = new Valid('limit');
        $validLimit->addRule(new Rule_Int());
                
        if($request->valid($validLimit)) {
            $limit = $request->get('limit');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        //valid nbtodisplay
        $validNbToDisplay = new Valid('nbtodisplay');
        $validNbToDisplay->addRule(new Rule_Int());

        if($request->valid($validNbToDisplay)) {
            $nbtodisplay = $request->get('nbtodisplay');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
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
                $dar = $dao->searchAllByUserId($uid);
                $userparam[] = $dar->getRow();
            }
        }
        else {
            $dar = $dao->searchAllByUserId($userid);
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
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        //valid user name
        $validUserName = new Valid_String('user_name_search');
      
        if ($request->valid($validUserName)) {
            $this->username = $request->get('user_name_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');            
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
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }
    
        //valid status
        $validStatus = new Valid('user_status_search');                
        $validStatus->addRule(new Rule_WhiteList($statusWhiteList), 'Your (status) data are not valid');

        if ($request->valid($validStatus)) {
            $this->status = $request->get('user_status_search');                
        }
        else{
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
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

        if ($this->view == 'ajax_projects') {
         
            $dao = new UserDao(CodexDataAccess::instance());
            $filter = array();
            $request =& HTTPRequest::instance();
            $vuName = new Valid_String('user_name_search');
            
            if ($request->valid($vuName)) {
                $name = $request->get('user_name_search');
                $filter[] = new UserNameFilter($name);
            }
            else {
                $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
            }
            $this->userIterator = $dao->searchUserByFilter($filter, 0, 10);
        }
     }


    /**
     * add user to a group
     */
    function addUserToGroup() {
        
        $dao = new UserDao(CodexDataAccess::instance());

        if(!$this->userid) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_usergroup', 'error_nouid'));
        }        
        elseif(!$this->groupid) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_usergroup', 'error_nogid'));
        }
        else {

            $dao->searchGroupById($this->groupid);

            //if the doesn't group exist
            if(!$dao || $dao->getFoundRows() <1) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_userlist','error_noadd'));
            }
            else {
                $dao->searchUserInUserGroup($this->userid, $this->groupid);
                
                //if user doesn't belong to this group
                if (!$dao || $dao->getFoundRows() < 1) {
                    $dao->addUserToGroup($this->userid, $this->groupid);
                
                    //if there is problem in adding user to this group
                    if (!$dao || $dao->getFoundRows() < 1) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_usergroup','error_add_ug'));
                    } 
                    else {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_usergroup','success_add_ug'));
                        $GLOBALS['Response']->redirect('/admin/user/index.php?user_id='.$this->userid);
                    }
                } 
                else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_usergroup','error_member',$this->groupid));
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
        $shellWhiteList = array('/bin/sh', '/bin/bash', '/sbin/nologin', '/bin/bash2', '/bin/ash', '/bin/bsh', '/bin/tcsh', '/bin/csh', '/bin/zsh');

        $validShell = new Valid('shell');
        $validShell->addRule(new Rule_WhiteList($shellWhiteList));


        if ($request->valid($validShell)) {
            $shell = $request->get('shell');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        //valid codex status
        $codexStatusWhiteList = array('A', 'R', 'V', 'P', 'D', 'S');

        $validCodexStatus = new Valid('codexstatus');
        $validCodexStatus->addRule(new Rule_WhiteList($codexStatusWhiteList));

        if ($request->valid($validCodexStatus)) {
            $codexstatus = $request->get('codexstatus');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        //valid unix status
        $unixStatusWhiteList = array('N', 'A', 'S', 'D');

        $validUnixStatus = new Valid('unixstatus');
        $validUnixStatus->addRule(new Rule_WhiteList($unixStatusWhiteList));

        if ($request->valid($validUnixStatus)) {
            $unixstatus = $request->get('unixstatus');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        //valid email
        $validEmail = new Valid('email');

        if ($request->valid($validEmail)) {
            $email = $request->get('email');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }


        //valid date
        $validExpiryDate = new Valid('expiry_date');
        $validExpiryDate->addRule(new Rule_Date());

        if ($request->valid($validExpiryDate)) {
            $expirydate = $request->get('expiry_date');
        }
        else {           
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_usergroup','data_not_parsed'));
        }

        $dao = new UserDao(CodexDataAccess::instance());


        //update one user
        if ($shell && $codexstatus && $unixstatus && $email && isset($expirydate)) {
         
            $date = util_date_to_unixtime($expirydate);

            //check if unix uid exists
            if ($unixstatus != 'N' ){

                $dao->checkUnixUid($this->userid);
               
                $unixuidexist = $dao->getFoundRows();

                // create unix uid if it doesn't exists
                if ($unixuidexist > 0) {
                    $dao->createUnixUid($this->userid);
                }
            }
            //update
            $dao->updateUser($this->userid, $shell, $codexstatus, $unixstatus, $email, $date[0]);
        }
        //update several users
         elseif ($shell && $codexstatus && $unixstatus && isset($expirydate)) {
             
             $date = util_date_to_unixtime($expirydate);

             $dao->checkUnixUid($this->userid);

             $unixuidexist = $dao->getFoundRows();

             if ($unixuidexist <= 0) {
                 $dao->createUnixUid($this->userid);
             }

             //update
            $dao->updateUsers($this->userid, $shell, $codexstatus, $unixstatus, $email, $date[0]);

         }

         //Update in plugin
         require_once('common/event/EventManager.class.php');
         $em =& EventManager::instance();
         $em->processEvent('usergroup_update', array('HTTP_POST_VARS' =>  $HTTP_POST_VARS,
                                                     'user_id' => $this->userid )); 
         $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_usergroup', 'success_upd_u'));
         $GLOBALS['Response']->redirect('/admin/user/index.php?user_id='.$this->userid);

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
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        //valid group id
        $validGroupId = new Valid_UInt('group_id');

        if ($request->valid($validGroupId)) {
            $this->groupid = $request->get('group_id');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        //valid task
        $validTask = new Valid_String('task');

        if ($request->valid($validTask)) {
            $this->task = $request->get('task');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }


        if ($this->userid) {
            //            header('Location:/admin/user/index.php?userid='.$this->user_id);
            
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
        }

        $this->setOffset();        

        $this->setLimit();
            
        $this->setUserIterator();
        
        $this->setNbUser();
    }
}

?>
