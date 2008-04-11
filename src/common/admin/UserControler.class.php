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
require_once('www/admin/user/UserSearchDisplay.class.php');
require_once('common/dao/CodexDataAccess.class.php');
require_once('common/dao/UserDao.class.php');
require_once('common/mvc/Controler.class.php');

class UserControler extends Controler {

    private $uIterator;
    
    private $nbrowstodisplay;
    
    function __construct() {
        
    }

    function viewsManagement() {
        $this->initNbRowsToDisplay($this->nbrowstodisplay);
        $userSearchDisplay = new UserSearchDisplay($this->uIterator,$this->nbrowstodisplay);
        $userSearchDisplay->display();
    }

    function setUserIterator() {
        
        $dao = new UserDao(CodexDataAccess::instance());
        $request =& HTTPRequest::instance();

        if(isset($_GET['user_name_search'])){       

            $whiteListArray = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', 1, 2, 3, 4, 5, 6, 7, 8, 9);

            $v = new Valid('user_name_search');
            $v->addRule(new Rule_WhiteList($whiteListArray));
            
            
            if($request->valid($v)) {
                $user_name_search = $request->get('user_name_search');
                $usersearch = $user_name_search;
                $this->uIterator = $dao->searchByNameFirstLetter($usersearch);    
            } 
            else {
                $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
            }
        }       
        elseif (isset($_POST['user_all_name_search'])) {
            
            $whiteListArray = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '-', '_', '\'', '0', 1, 2, 3, 4, 5, 6, 7, 8, 9);
            
            $v = new Valid('user_all_name_search');
            $v->addRule(new Rule_WhiteList($whiteListArray));
            
            if($request->valid($v)) {
                
                if($request->isPost()) {
                    $user_all_name_search = $request->get('user_all_name_search');
                    $usersearch = $user_all_name_search;
                    $this->uIterator = $dao->searchByAllNames($usersearch);
                }
                else {
                    $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');
                }
            }
            else {
                $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
            }
        }
        elseif (isset($_POST['group_name_search'])) { 
            
            if($request->isPost()) {
                $group_name_search = $request->get('group__name_search');
                $usersearch = $group_name_search;
                $this->uIterator = $dao->searchByGroupName($usersearch);
            }
            else {
                $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');
            }
        }
        elseif (isset($_POST['user_status_search'])) {
            
            $whiteListArray = array('A', 'R', 'V', 'P', 'D', 'W', 'S');
            
            $v = new Valid('user_status_search');
            $v->addRule(new Rule_WhiteList($whiteListArray));
            
            if($request->valid($v)) {
        
                if($request->isPost()) {
                    $user_status_search = $request->get('user_status_search');
                    $usersearch = $user_status_search;
                    $this->uIterator = $dao->searchByStatus($usersearch);
                }
                else {
                    $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');
                }
            }
            else{
                $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
            }
        }
        else {
            $usersearch = '';
            $this->uIterator = $dao->searchAll();
        }
    }

    function initNbRowsToDisplay($nbrowstodisplay) {
        if (!empty($this->nbrowstodisplay)) {
            $this->nbrowstodisplay = $nbrowstodisplay;
        }
        else {
            $this->nbrowstodisplay = 50;
        }
    }
    
    function getNbRowsToDisplay() {
        return $this->setNbRowsToDisplay($this->nbrowstodisplay);
    }
    
    function request() {
        $this->setUserIterator();
    }
}
?>
