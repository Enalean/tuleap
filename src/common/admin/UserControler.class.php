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
require_once('common/dao/CodexDataAccess.class.php');
require_once('common/dao/UserDao.class.php');
require_once('common/mvc/Controler.class.php');

class UserControler extends Controler {

    private $userIterator;
    
    private $limit;

    private $offset;

    private $startlist;

    private $endlist;

    /**
     * constructor
     *
     */    
    function __construct() {

    }

    function viewsManagement() {
        $userSearchDisplay = new UserSearchDisplay($this->userIterator,$this->limit);
      
        $userSearchDisplay->display();
    }

//     function setUserIterator() {
        
//         $dao = new UserDao(CodexDataAccess::instance());
//         // $suIter = new SearchUserIterator();

// //         var_dump($suIter);

//         $request =& HTTPRequest::instance();

//         //search by first letter
//         if(isset($_GET['user_name_search'])){       

//             $whiteListArray = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

//             $v = new Valid('user_name_search');
//             $v->addRule(new Rule_WhiteList($whiteListArray));
                        
//             if($request->valid($v)) {
//                 $user_name_search = $request->get('user_name_search');
//                 $usersearch = $user_name_search;
//                 $this->uIterator = $dao->searchByNameFirstLetter($usersearch, $this->offset, $this->nbrowstodisplay);
//             } 
//             else {
//                 $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
//             }
//         

//         if ($user_all_name_search !== '' && $group_name_search !== '' && $user_status_search !== '') {

//             //escape data dans la requete
//             $clean_user_name = db_escape_string($user_all_name_search);
//             $clean_group_name = db_escape_string($group_name_search);
//             $clean_status = db_escape_string($user_status_search);

//             $select = 'SELECT DISTINCT user_name,user.user_id, email, user_pw, realname, user.register_purpose, user.status, shell, unix_pw, unix_status, unix_uid, user.unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme ';

//             $from = 'FROM user, user_group, groups ';

//             $where = 'WHERE user.user_id = user_group.user_id '.
//                            'AND user_group.group_id = groups.group_id '.
//                            'AND (user_name LIKE \'%'.$clean_user_name.'%\' '.
//                            'OR realname LIKE \'%'.$clean_user_name.'%\') '.
//                            'AND  (groups.group_name LIKE \'%'.$clean_group_name.'%\' '.
//                            'OR groups.usni_group_name LIKE \'%'.$clean_group_name.'%\') '.
//                            'AND status = '.$clean_status.' ';

//             $orderby = 'ORDER BY user_name, realname, status ';


//             $suIter = new SearchUserIterator($select, $from, $where, $orderby);

//             var_dump($suIter);

            
//             //$suIter = SearchUserIterator::setStatement($select, $from, $where, $orderby);
//             //            $suIter->getStatement();
            
//             $this->uIterator = $dao->searchByCriteria($suIter, $this->offset, $this->nbrowstodisplay);
//         }
//         elseif ($user_all_name_search !== '' && $group_name_search !== '') {

//             //escape data
//             $clean_user_name = db_escape_string($user_all_name_search);
//             $clean_group_name = db_escape_string($group_name_search);

//             $this->select =  'SELECT DISTINCT user_name,user.user_id, email, user_pw, realname, user.register_purpose, user.status, shell, unix_pw, unix_status, unix_uid, user.unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme ';

//             $this->from = 'FROM user, user_group, groups ';

//             $this->where = 'WHERE user.user_id = user_group.user_id '.
//                            'AND user_group.group_id = groups.group_id '.
//                            'AND (user_name LIKE \'%'.$clean_user_name.'%\' '.
//                            'OR realname LIKE \'%'.$clean_user_name.'%\') '.
//                            'AND  (groups.group_name LIKE \'%'.$clean_group_name.'%\' '.
//                            'OR groups.usni_group_name LIKE \'%'.$clean_group_name.'%\') ';
            
//             $this->orderby = 'ORDER BY user_name, realname';
            
//             $this->suIter = $this->getStatement();
            
//             $this->uIterator = $dao->searchByCriteria($this->suIter, $this->offset, $this->nbrowstodisplay);
            
//         }

//         elseif ($group_name_search !== '' && $user_status_search !== '') {

//             //escape data
//             $clean_group_name = db_escape_string($group_name_search);
//             $clean_status = db_escape_string($user_status_search);

//             $this->select =  'SELECT DISTINCT user_name,user.user_id, email, user_pw, realname, user.register_purpose, user.status, shell, unix_pw, unix_status, unix_uid, user.unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme ';
            
//             $this->from = 'FROM user, user_group, groups ';

//             $this->where = 'WHERE user.user_id = user_group.user_id '.
//                            'AND user_group.group_id = groups.group_id '.
//                            'AND  (groups.group_name LIKE \'%'.$clean_group_name.'%\' '.
//                            'OR groups.usni_group_name LIKE \'%'.$clean_group_name.'%\') '.
//                            'AND status = '.$clean_status.' ';

//             $this->orderby = 'ORDER BY user_name, realname, status ';

//             $this->suIter = $this->getStatement();
            
//             $this->uIterator = $dao->searchByCriteria($this->suIter, $this->offset, $this->nbrowstodisplay);
                
//         }

        
//         //default search : all
//         else {
//             //    $usersearch = '';  //a quoi sert cette instruction ?
//             $this->uIterator = $dao->searchAll($this->offset, $this->nbrowstodisplay);
//         }
        
//     }

    function setOffset($offset) {
        if (!is_null($offset)) {
            $this->offset = $offset;
        }
        else {
            $this->offset = 0;
        }
    }

    function setLimit($limit) {

        $request =& HTTPRequest::instance();

        $v = new Valid('nbtodisplay');
        $v->addRule(new Rule_Int());
        
        if (!isset($limit)) {
         
            if ($request->valid($v)) {
                
                if ($request->isPost()) {
                    $limit = $request->get('nbtodisplay');
                    $this->limit = $limit;
               }
            }
            else {
                $GLOBALS['Response']->addFeedback('error', 'You must enter an integer');
                $this->setLimit($limit);
            }
        }
        else {
            $this->limit = 50;
        }
    }


    function setUserIterator() {

        $dao = new UserDao(CodexDataAccess::instance());

        $criteria = array();

        $request =& HTTPRequest::instance();

        //search by user name
        if ($_POST['user_name_search'] !== '') {
           
            $vuName = new Valid_String('user_name_search');
 
            if ($request->valid($vuName)) {

                if ($request->isPost()) {
                    $name = $request->get('user_name_search');
                    $namecriteria = array();
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
        if ($_POST['user_group_search'] !== '') {
           
            $vuGroup = new Valid_String('user_group_search');

            if ($request->valid($vuGroup)) {

                if ($request->isPost()) {
                    $group = $request->get('user_group_search');
                    $groupcriteria = array();
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
                    $statuscriteria = array();                  
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
    
    function getOffset() {
        return $this->offset;
    }

    function getLimit() {
        return $this->limit;
    }
    
    function request() {

        $this->setOffset($this->offset);

        $this->setLimit($_POST['nbtodisplay']);
        
        $this->setUserIterator();
    }
}

?>
