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
     * @type Iterator $userIterator
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
     * $useradminflag
     *
     * @type string $useradminflag
     */
    private $useradminflag;

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
     * constructor
     *
     */    
    function __construct() {

    }

    /**
     * viewManagement()
     */
    function viewsManagement() {
     
        if ($this->userid) {
            $view = new UserEditDisplay($this->userparam, $this->useradminflag);
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
     *
     * @param int $offset
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


        if ($limit != '') {
            $this->limit = $limit;
        }
        elseif ($nbtodisplay != '') {
            $this->limit = $nbtodisplay;
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

        $dar = $dao->searchByUserId($userid);
            
        $this->userparam = $dar->getRow();
    }


    /**
     * setUserAdminFlag
     */
    function setUserAdminFlag($userid) {

        $dao =  new UserDao(CodexDataAccess::instance());

        $dar = $dao->searchAdminFlag($userid);

        $this->useradminflag = $dar->getRow();
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
     * request()
     */
    function request() {
        
        $request =& HTTPRequest::instance();

        
        //valid parameters

        //valid user id
        $validUserId = new Valid('user_id');
        $validUserId->addRule(new Rule_Int());
        
        if ($request->valid($validUserId)) {
            $this->userid = $request->get('user_id');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        if ($this->userid) {

            $this->setUserParam($this->userid);
            $this->setUserAdminFlag($this->userid);
        }

        $this->setOffset();        

        $this->setLimit();
               
        $this->setUserIterator();
        
        $this->setNbUser();
    }
}

?>
