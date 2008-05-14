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
     * constructor
     *
     */    
    function __construct() {

    }

    /**
     * viewManagement()
     */
    function viewsManagement() {
     
       //  if($this-> == 'ajax') {
//             $view = new UserSearchAjaxDisplay($this->userIterator,$this->offset,$this->limit, $this->nbuser);
//         } 
//         else {
        if (!is_null($_GET['user_id'])) {
            $view = new UserEditDisplay();
        
        }
        else {

            $view = new UserSearchDisplay($this->userIterator,$this->offset,$this->limit, $this->nbuser);
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
    function setOffset($offset = null) {
       
        if ($offset === null) {
            $this->offset = 0;
        }
        else {
            $request =& HTTPRequest::instance();
            
            $voffset = new valid('offset');
            $voffset->addRule(new Rule_Int());
            
            if ($request->valid($voffset)) {
                $offset = $request->get('offset');
                $this->offset = $offset;
            }
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
            if ($request->isPost()) {
                $nbtodisplay = $request->get('nbtodisplay');
            }
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
            $shortcut = $request->get('user_shortcut_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        //valid user name
        $validUserName = new Valid_String('user_name_search');
      
        if ($request->valid($validUserName)) {
            $name = $request->get('user_name_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');            
        }

        //valid user group
        $validUserGroup = new Valid_String('user_group_search');
                
        if ($request->valid($validUserGroup)) {
            $group = $request->get('user_group_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }
    
        //valid status
        $validStatus = new Valid('user_status_search');                
        $validStatus->addRule(new Rule_WhiteList($statusWhiteList), 'Your (status) data are not valid');

        if ($request->valid($validStatus)) {
            $status = $request->get('user_status_search');                
        }
        else{
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        if ($shortcut != '') {
            $filter[] = new UserShortcutFilter($shortcut);
        }
        if ($name != '') {
            $filter[] = new UserNameFilter($name);
        }
        if ($group != '') {
            $filter[] = new UserGroupFilter($group);
        }
        if ($status != '' && $status != 'all') {
            $filter[] = new UserStatusFilter($status);
        }
        
        $this->userIterator = $dao->searchUserByFilter($filter, $this->offset, $this->limit);    
    }

   
    /**
     * request()
     */
    function request() {
               
        $this->setOffset($_GET['offset']);
        
        $this->setLimit();
               
        $this->setUserIterator();
        
        $this->setNbUser();
    }
}

?>
