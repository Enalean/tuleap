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
require_once 'pre.php';
require_once 'common/admin/view/AdminSearchDisplay.class.php';
require_once 'common/admin/view/user/UserSearchDisplay.class.php';
require_once 'common/admin/view/user/UserEditDisplay.class.php';
require_once 'common/admin/view/user/UserChangeNameDisplay.class.php';
require_once 'common/dao/CodexDataAccess.class.php';
require_once 'common/dao/UserDao.class.php';
require_once 'common/dao/GroupDao.class.php';
require_once 'common/mvc/Controler.class.php';

/**
 * UserControler()
 */
class UserControler extends Controler
{
    /**
     * $_userIterator
     *
     * @type mixed $_userIterator
     */
    private $_userIterator;

    /** 
     * $_limit
     *
     * @type int $_limit
     */
    private $_limit;

    /**
     * $_offset
     *
     * @type int $_offset
     */
    private $_offset;

    /**
     * $_nbuser
     *
     * @type int $_nbuser
     */
    private $_nbuser;

    /**
     * $_userparam an array that contains the params of a user (for the editing mode)
     *
     * @type array $_userparam
     */
    private $_userparam;
    
    /**
     * $_userid 
     *
     * @type int $_userid
     */
    private $_userid;

    /**
     * $_groupid
     *
     * @type int $_groupid
     */
    private $_groupid;

    /**
     * $_task
     *
     * @type string $_task
     */
    private $_task;

    /**
     * $_adminflag
     *
     * @type string $_adminflag
     */
    //    private $_adminflag;

    /**
     * $_shortcut
     *
     * @type string $_shortcut
     */
    private $_shortcut;

    /**
     * $_username
     *
     * @type string $_username
     */
    private $_username;

    /**
     * $_group
     *
     * @type string $_group
     */
    private $_group;

    /**
     * $_status
     *
     * @type string $_status
     */
    private $_status;

    /**
     * $_groupparam
     *
     * @type mixed $_groupparam
     */
    private $_groupparam;

    /**
     * $_newUserName
     *
     * @type string $_newUserName
     */
    private $_newUserName;

    /**
     * $_displayDirection
     *
     * @type boolean $_displayDirection
     */
    private $_displayDirection;


    /**
     * constructor
     *
     */    
    function __construct()
    {
        $GLOBALS['Language']->loadLanguageMsg('admin/admin');
    }

    /**
     * viewManagement()
     *
     * @return void
     */
    function viewsManagement()
    {
        if ($this->_userid) {
            if ($this->_task == 'change_user_name') {
                $view = new UserChangeNameDisplay($this->_userparam, 
                                                  $this->_groupparam, 
                                                  $this->_task, 
                                                  $this->_displayDirection, 
                                                  $this->_newUserName);
            } else {
                $view = new UserEditDisplay($this->_userparam, 
                                            $this->_groupparam, 
                                            $this->_task);
            }
        } else {
            $view = new UserSearchDisplay($this->_userIterator, 
                                          $this->_offset, 
                                          $this->_limit, 
                                          $this->_nbuser, 
                                          $this->_shortcut, 
                                          $this->_username, 
                                          $this->_group, 
                                          $this->_status);
        }
        $view->display();
    }

    /**
     * setNbUser()
     *
     * @return void
     */
    function setNbUser()
    {
        $dao           = new UserDao(CodexDataAccess::instance());
        $this->_nbuser = $dao->getFoundRows();
    }

    /**
     * setOffset()
     *
     * @return void
     */
    function setOffset()
    {
        $request =& HTTPRequest::instance();

        $validoffset = new Valid_UInt('offset');
        $validoffset->required();

        if ($request->valid($validoffset)) {
            $offset        = $request->get('offset');
            $this->_offset = $offset;
        } else {
            $this->_offset = 0;
        }
    }

    /**
     * setLimit()
     *
     * @return void
     */
    function setLimit()
    {
        $request =& HTTPRequest::instance();

        //valid parameters

        //valid limit
        $limit      = '';
        $validLimit = new Valid_UInt('limit');

        if ($request->valid($validLimit)) {
            $limit = $request->get('limit');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_limit'));
        }

        //valid nbtodisplay
        $nbtodisplay      = '';
        $validNbToDisplay = new Valid_UInt('nbtodisplay');

        if ($request->valid($validNbToDisplay)) {
            $nbtodisplay = $request->get('nbtodisplay');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_limit'));
        }

        if ($nbtodisplay != '') {
            $this->_limit = $nbtodisplay;
        } elseif ($limit != '') {
            $this->_limit = $limit;
        } else {
            $this->_limit = 50;
        }
    }

    /**
     * setUserParam
     *
     * @param int $userid the user id
     *
     * @return void
     */
    function setUserParam($userid)
    {
        $dao = new UserDao(CodexDataAccess::instance());

        if (is_array($userid)) {
            foreach ($userid as $uid) {
                $dar         = $dao->searchByUserId($uid);
                $userparam[] = $dar->getRow();
            }
        } else {
            $dar       = $dao->searchByUserId($userid);
            $userparam = $dar->getRow();
        }
        $this->_userparam = $userparam;
    }

    /**
     * setGroupParam
     *
     * @param int $userid the user id
     *
     * @return void
     */
    function setGroupParam($userid)
    {
        $dao = new UserDao(CodexDataAccess::instance());

        if (is_array($userid)) {
            $userid = implode(",", $userid);
        }

        $groupparam        = $dao->searchGroupByUserId($userid);
        $this->_groupparam = $groupparam;
    }

    /**
     * setUserIterator()
     *
     * @return void
     */
    function setUserIterator()
    {
        $dao     = new UserDao(CodexDataAccess::instance());        
        $filter  = array();
        $request =& HTTPRequest::instance();

        //define white lists for parameters
        $shortcutWhiteList = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 
                                   'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 
                                   'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', 
                                   '4', '5', '6', '7', '8', '9');

        $statusWhiteList = array('all', 'A', 'R', 'V', 'P', 'D', 'W', 'S');

        //valid parameters

        //valid shortcut
        $validShortcut = new Valid('user_shortcut_search');
        $validShortcut->addRule(new Rule_WhiteList($shortcutWhiteList));

        if ($request->valid($validShortcut)) {
            $this->_shortcut = $request->get('user_shortcut_search');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_shortcut'));
        }

        //valid user name
        $validUserName = new Valid_String('user_name_search');

        if ($request->valid($validUserName)) {
            $this->_username = $request->get('user_name_search');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_username'));
        }

        //valid user group
        $validUserGroup = new Valid_String('user_group_search');

        if ($request->valid($validUserGroup)) {
            $this->_group = $request->get('user_group_search');
            $this->_group = explode(',', $this->_group);
            $this->_group = $this->_group[0];

            if ( preg_match('#^.*\((.*)\)$#', $this->_group, $matches)) {
                $this->_group = $matches[1];
            } 
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_group_name'));
        }

        //valid status
        $validStatus = new Valid('user_status_search');                
        $validStatus->addRule(new Rule_WhiteList($statusWhiteList));
        $validStatus->required();
        $this->_status = $request->getValidated('user_status_search', $validStatus, 'all');

        if ($this->_shortcut != '') {
            $filter[] = new UserShortcutFilter($this->_shortcut);
        }
        if ($this->_username != '') {
            $filter[] = new UserNameFilter($this->_username);
        }
        if ($this->_group != '') {
            $filter[] = new UserGroupFilter($this->_group);
        }
        if ($this->_status != '' && $this->_status != 'all') {
            $filter[] = new UserStatusFilter($this->_status);
        }
        $this->_userIterator = $dao->searchUserByFilter($filter, 
                                                       $this->_offset, 
                                                       $this->_limit);    
    }

    /**
     * add user to a group
     *
     * @return void
     */
    function addUserToGroup()
    {
        $user  = UserManager::instance()->getUserById($this->_userid);
        $group = project_get_object($this->_groupid);
        
        if (!$user) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'error_nouid'));
        } elseif (!$group) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'error_nogid'));
        } else {
            if(account_add_user_to_group($group->getID(), $user->getId())) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_user_controler', 'success_add_ug'));
                $GLOBALS['Response']->redirect('/admin/user/index.php?user_id='.$this->_userid);
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'error_add_ug'));
            }
        }
    }

    /**
     * update user
     *
     * @return void
     */
    function updateUser()
    {
        $request =& HTTPRequest::instance();
        //valid parameters

        //valid shell
        $shell          = '';
        $shellWhiteList = array('/bin/sh', 
                                '/bin/bash', 
                                '/sbin/nologin', 
                                '/bin/bash2', 
                                '/bin/ash', 
                                '/bin/bsh', 
                                '/bin/tcsh', 
                                '/bin/csh', 
                                '/bin/zsh');

        $validShell = new Valid('shell');
        $validShell->addRule(new Rule_WhiteList($shellWhiteList));

        if ($request->valid($validShell)) {
            $shell = $request->get('shell');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_shell'));
        }

        //valid codex status
        $cidexstatus          = '';
        $codexStatusWhiteList = array('A', 'R', 'V', 'P', 'D', 'S');

        $validCodexStatus = new Valid('codexstatus');
        $validCodexStatus->addRule(new Rule_WhiteList($codexStatusWhiteList));

        if ($request->valid($validCodexStatus)) {
            $codexstatus = $request->get('codexstatus');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_status'));
        }

        //valid unix status
        $unixstatus          = '';
        $unixStatusWhiteList = array('N', 'A', 'S', 'D');

        $validUnixStatus = new Valid('unixstatus');
        $validUnixStatus->addRule(new Rule_WhiteList($unixStatusWhiteList));

        if ($request->valid($validUnixStatus)) {
            $unixstatus = $request->get('unixstatus');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_unix_status'));
        }

        //valid email
        $email      = '';
        $validEmail = new Valid_Email('email');

        if ($request->valid($validEmail)) {
            $email = $request->get('email');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_email'));
        }

        //valid date
        $validExpiryDate = new Valid('expiry_date');
        $validExpiryDate->addRule(new Rule_Date());

        if ($request->valid($validExpiryDate)) {
            $expirydate = $request->get('expiry_date');
        } else {           
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'data_not_parsed'));
        }

        $dao = new UserDao(CodexDataAccess::instance());

        if ($shell != '' && $codexstatus != '' && $unixstatus != '' && ($email != '' || is_array($this->_userid)) && isset($expirydate)) {

            $date = util_date_to_unixtime($expirydate);

            $dao->checkUnixUid($this->_userid);

            $unixuidexist = $dao->getFoundRows();

            // create unix uid if it doesn't exists
            if ($unixuidexist <= 0 && $unixstatus != 'N') {
                $dao->createUnixUid($this->_userid);
            }

            //update
            $dao->updateUser($this->_userid, 
                             $shell, 
                             $codexstatus, 
                             $unixstatus, 
                             $email, 
                             $date[0]);

            //Update in plugin
            require_once 'common/event/EventManager.class.php';
            $em =& EventManager::instance();
            $em->processEvent('usergroup_update', array('HTTP_POST_VARS' =>  $_POST,
                                                     'user_id' => $this->_userid )); 

            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_user_controler', 'success_upd_u'));
        }

        if (is_array($this->_userid)) {
            $GLOBALS['Response']->redirect('/admin/user/index.php');
        } else {
            $GLOBALS['Response']->redirect('/admin/user/index.php?user_id='.$this->_userid);
        }
    }

    /**
     * This method init the new user name
     *
     * @return void
     */
    function setNewUserName()
    {
        $request =& HTTPRequest::instance();

        //valid new user name
        $validNewUserName = new Valid_String('new_user_name');

        $this->_newUserName = '';
        if ($request->valid($validNewUserName)) {
            $this->_newUserName = $request->get('new_user_name');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'user_new_name'));
        }
    }

    /**
     * This method check if the new user name doesn't exist yet
     *
     * @return void
     */
    function checkUserNameExist() 
    {
        $dao    = new UserDao(CodexDataAccess::instance());        
        $filter = array();

        if ($this->_newUserName != '') {
            $filter[] = new FullUserNameFilter($this->_newUserName);
            $dao->searchUserByFilter($filter);
            $newUserNameExist = $dao->getFoundRows();

            if ($newUserNameExist == 0) {
                $this->_displayDirection = true;
            } else {
                $this->_displayDirection = false;
                $GLOBALS['Response']->addFeedBack('error', $GLOBALS['Language']->getText('admin_user_controler', 'user_name_exists'));
            }
        }
    }

    /**
     * check if user follow the direction to change user name
     *
     * @return void
     */
    function checkUserNameDirection()
    {
        if (isset($this->_userparam['user_name'])) {
            $oldusername = $this->_userparam['user_name'];
        } elseif (count($this->_userparam) == 1) {
            $oldusername = $this->_userparam[0]['user_name'];
        }
        
        if ($f = fopen('./../../../../../../../etc/group', 'r')) {
          
            $dao    = new UserDao(CodexDataAccess::instance());
            $filter = array();

            $filter[]  = new UserGroupByNameFilter($oldusername);
            $userGroup = $dao->searchUserByFilter($filter);

            $line = file('./../../../../../../../etc/group');

            $oldNamePattern = '#'.$oldusername.'#';

            $newNamePattern = '#^'.$this->_newUserName.'.*'.$this->_newUserName.'$#';

            //test if the user belong to his group
            foreach ($line as $l) {

                foreach ($userGroup as $ug) {
                    $userGroupPattern = '#^'.$ug['unix_group_name'].'.*'.$this->_newUserName.'#';

                    if (preg_match($userGroupPattern, $l)) {
                        echo 'ce quon recupere '.$l.'<br />';
                    }
                }
                if (preg_match('#[^'.$ug['unix_group_name'].'.]*'.$this->_newUserName.'#', $l)) {
                    echo $l.'<br />';
                    $GLOBALS['Response']->addFeedBack('error', 'This user is contained in a group that he doesn\'t belong to'); 
                }
            }
        } else {
            $GLOBALS['Response']->addFeedBack('error', 'This file cannot be opened');
        }
    }

    /**
     * replace the old user name by the new one in the database
     *
     * @return void
     */
    function changeUserNameInDB()
    {
        if (isset($this->_userparam['user_name'])) {
            $oldusername = $this->_userparam['user_name'];
        } elseif (count($this->_userparam) == 1) {
            $oldusername = $this->_userparam[0]['user_name'];
        }

        $dao = new UserDao(CodexDataAccess::instance());
        $dao->changeName($oldusername, $this->_newUserName);
        $dao->changeEmail($oldusername, $this->_newUserName);
        $dao->changeArtifact($oldusername, $this->_newUserName);
        $dao->changeWikiPage($oldusername, $this->_newUserName);
        $dao->changeSupportMessage($oldusername, $this->_newUserName);
        $dao->changeBug($oldusername, $this->_newUserName);
        $dao->changeProject($oldusername, $this->_newUserName);
    }

    /**
     * This method call other methods that check one specific point of the direction to change user name
     *
     * @return void
     */
    function changeUserName()
    {
        $this->checkUserNameDirection();
        $this->changeUserNameInDB();
    }

    /**
     * request()
     *
     * @return void
     */
    function request()
    {
        $request =& HTTPRequest::instance();

        //valid parameters

        //valid user id
        $validUserId = new Valid_UInt('user_id');

        if ($request->validArray($validUserId) || $this->_userid == '') {
            $this->_userid = $request->get('user_id');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_uid'));
        }

        //valid group id
        $validGroupId = new Valid_UInt('group_id');

        if ($request->valid($validGroupId)) {
            $this->_groupid = $request->get('group_id');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_gid'));
        }


        //valid task
        $validTask = new Valid_String('task');

        if ($request->valid($validTask)) {
            $this->_task = $request->get('task');
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_user_controler', 'wrong_task'));
        }

        if ($this->_userid) {            
            $this->setUserParam($this->_userid);
            $this->setGroupParam($this->_userid);
        }

        if ($this->_task) {

            if ($this->_task == 'add_user_to_group') {
                $this->addUserToGroup();
                $this->setUserParam($this->_userid);
                $this->setGroupParam($this->_userid);

            } elseif ($this->_task == 'update_user') {
                $this->updateUser();
                $this->setUserParam($this->_userid);
                $this->setGroupParam($this->_userid);

            } elseif ($this->_task == 'change_user_name') {
                $this->setNewUserName();
                $this->checkUserNameExist();
            
            } elseif ($this->_task == 'check_instruction') {            
                $this->setNewUserName();
                $this->changeUserName();
            }
        }

        if ($this->_task != 'change_user_name') {
            $this->setOffset();        

            $this->setLimit();

            $this->setUserIterator();

            $this->setNbUser();
        }
    }
}

?>
