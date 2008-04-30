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
            $view = new UserSearchDisplay($this->userIterator,$this->offset,$this->limit, $this->nbuser);
            // }
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
        
        if ($_GET['limit']) {

            $vlimit = new Valid('limit');
            $vlimit->addRule(new Rule_Int());

            if ($request->valid($vlimit)) {
                $limit = $request->get('limit');
                $this->limit = $limit;
            }
            else {

                $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
            }
        }
        elseif ($_POST['nbtodisplay']) {

            $v = new Valid('nbtodisplay');
            $v->addRule(new Rule_Int());

            if ($request->valid($v)) {
                if ($request->isPost()) {
                    $limit = $request->get('nbtodisplay');
                    $this->limit = $limit;
                }
                else {
                    $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST'); 
                }
            }
            else {
                $GLOBALS['Response']->addFeedback('error', 'Your data are not valid'); 
            }
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
        
        $criteria = array();

        $request =& HTTPRequest::instance();

        if (isset($_GET['user_shortcut_search']) || $_POST['user_name_search'] != '' || $_POST['user_group_search'] != '') {

            //search by shortcut
            if(isset($_GET['user_shortcut_search'])){       
                
                $whiteListArray = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
            
                $v = new Valid('user_shortcut_search');
                $v->addRule(new Rule_WhiteList($whiteListArray));
                
                if($request->valid($v)) {
                    $shortcut = $request->get('user_shortcut_search');
                    $criteria[] = new UserShortcutCriteria($shortcut);
                }
                else {
                    $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
                }
            }

            //search by user name
            if ($_POST['user_name_search'] != '') {
                
                $vuName = new Valid_String('user_name_search');
                
                if ($request->valid($vuName)) {
                    
                    if ($request->isPost()) {
                        $name = $request->get('user_name_search');
                        $criteria[] = new UserNameCriteria($name);
                    }
                    else {
                        $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');
                    }
                }
                else {
                    $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
                }
            }
            
            //search by group name
            if ($_POST['user_group_search'] != '') {
                
                $vuGroup = new Valid_String('user_group_search');
                
                if ($request->valid($vuGroup)) {
                    
                    if ($request->isPost()) {
                        $group = $request->get('user_group_search');
                        $criteria[] = new UserGroupCriteria($group);
                    }
                    else {
                        $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');
                    }
                }
                else {
                    $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
                }
            }
            
            //search by status
            if (isset($_POST['user_status_search']) && $_POST['user_status_search'] != 'all') {
                
                $whiteListArray = array('A', 'R', 'V', 'P', 'D', 'W', 'S');
                
                $vuStatus = new Valid('user_status_search');
                
                $vuStatus->addRule(new Rule_WhiteList($whiteListArray));
                
                if ($request->valid($vuStatus)) {
                    
                    if ($request->isPost()) {
                        $status = $request->get('user_status_search');                
                        $criteria[] = new UserStatusCriteria($status);
                    }
                    else {
                        $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');


                    }
                }
                else {
                    $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
                }
            }
            $this->userIterator = $dao->searchUserByCriteria($criteria, $this->getOffset(), $this->getLimit());    
        }
        else {
            $this->userIterator = $dao->searchAll($this->getOffset(), $this->getLimit());         
        }
    }

    /**
     * getOffset()
     */    
    function getOffset() {
        return $this->offset;
    }

    /**
     * getLimit()
     */
    function getLimit() {
        return $this->limit;
    }

    /**
     * getNbUser()
     */
    function getNbUser() {
        return $this->nbuser;
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
