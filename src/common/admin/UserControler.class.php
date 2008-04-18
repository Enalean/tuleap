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
    
    private $nbrowstodisplay;

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
        $userSearchDisplay = new UserSearchDisplay($this->userIterator,$this->nbrowstodisplay);
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
//         }


//         //code pour filtrer les entrees       

//         if (isset($_POST['user_all_name_search'])) {
            
//             $whiteListArray = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '-', '_', '\'', '0', 1, 2, 3, 4, 5, 6, 7, 8, 9);
            
//             $v = new Valid('user_all_name_search');
//             $v->addRule(new Rule_WhiteList($whiteListArray));
            
//             if($request->valid($v)) {
                
//                 if($request->isPost()) {
//                     $user_all_name_search = $request->get('user_all_name_search');
//                     //$usersearch = $user_all_name_search;
//                     //                    $this->uIterator = $dao->searchByAllNames($usersearch, $this->offset, $this->nbrowstodisplay);
//                 }
//                 else {
//                     $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');
//                 }
//             }
//             else {
//                 $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
//             }
//         }
       
//         if (isset($_POST['group_name_search'])) { 
            
//             if($request->isPost()) {
//                 $group_name_search = $request->get('group_name_search');
//                 //        $usersearch = $group_name_search;
//                 //$this->uIterator = $dao->searchByGroupName($usersearch, $this->offset, $this->nbrowstodisplay);
//             }
//             else {
//                 $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');
//             }
//         }
       
//         if (isset($_POST['user_status_search'])) {
            
//             $whiteListArray = array('A', 'R', 'V', 'P', 'D', 'W', 'S');
            
//             $v = new Valid('user_status_search');
//             $v->addRule(new Rule_WhiteList($whiteListArray));
            
//             if($request->valid($v)) {
        
//                 if($request->isPost()) {
//                     $user_status_search = $request->get('user_status_search');
//                     //            $usersearch = $user_status_search;
//                     ///$this->uIterator = $dao->searchByStatus($usersearch, $this->offset, $this->nbrowstodisplay);
//                 }
//                 else {
//                     $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');
//                 }
//             }
//             else{
//                 $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
//             }
//         }



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
//         elseif ($user_all_name_search !== '' && $user_status_search !== '') {
            
//             //escape data
//             $clean_user_name = db_escape_string($user_all_name_search);
//             $clean_status = db_escape_string($user_status_search);

//             $select = 'SELECT * ';
            
//             $from = 'FROM user ';

//             $where =  'WHERE user_name LIKE \'%'.$clean_user_name.'%\' '.
//                             'OR realname LIKE \'%'.$clean_user_name.'%\' '.
//                             'AND status = '.$clean_status.' ';
 
//             $orderby =  'ORDER BY user_name, realname, status ';

//             $this->suIter = $this->setStatement($select, $from, $where, $orderby);
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
//         elseif($user_all_name_search !== '') {

//             //escape data
//             $clean_user_name = db_escape_string($user_all_name_search);
            
//             $this->select = 'SELECT * ';

//             $this->from = 'FROM user ';

//             $this->where = 'WHERE (user_name LIKE \'%'.$clean_user_name.'%\' '.
//                            'OR realname LIKE \'%'.$clean_user_name.'%\') ';

//             $this->orderby = 'ORDER BY user_name,realname, status ';

//             $suIter = $this->getStatement();
            
//             $this->uIterator = $dao->searchByCriteria($this->suIter, $this->offset, $this->nbrowstodisplay);
                
//         }
//         elseif($group_name_search !== '') {

//             //escape data
//             $clean_group_name = db_escape_string($group_name_search);

//             $this->select =  'SELECT DISTINCT user_name,user.user_id, email, user_pw, realname, user.register_purpose, user.status, shell, unix_pw, unix_status, unix_uid, user.unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme ';
 
//                 $this->from = 'FROM user, user_group, groups ';
                
//                 $this->where =  'WHERE user.user_id = user_group.user_id '.
//                                 'AND user_group.group_id = groups.group_id '.
//                                 'AND (groups.group_name LIKE \'%'.$clean_group_name.'%\' '.
//                                 'OR groups_unix_group_name LIKE \'%'.$clean_group_name.'%\' ';

//                 $this->orderby = 'ORDER BY user_name,realname, status ';

//         $suIter = new SearchUserIterator($this->select, $this->from, $this->where, $this->orderby);
//                 $this->suIter = $this->getStatement();
                
//                 $this->uIterator = $dao->searchByCriteria($this->suIter, $this->offset, $this->nbrowstodisplay);
                
//         }
//         elseif($user_status_search !== '') {

//             //escape data
//             $clean_status = db_escape_string($user_status_search);

//             $this->select = 'SELECT * ';
            
//             $this->from = 'FROM user ';
            
//             $this->where = 'WHERE status = '.$clean_status.' ';
            
//             $this->orderby = 'ORDER BY user_name';

//             $this->suIter = $this->getStatement();

//             $this->uIterator = $dao->searchByCriteria($this->suIter, $this->offset, $this->nbrowstodisplay);
                
//         }
        
//         //default search : all
//         else {
//             //    $usersearch = '';  //a quoi sert cette instruction ?
//             $this->uIterator = $dao->searchAll($this->offset, $this->nbrowstodisplay);
//         }
        
//     }

    function setUserIterator() {


        $dao = new UserDao(CodexDataAccess::instance());

        $criteria = array();

        $request =& HTTPRequest::instance();

        
        $whiteListArray = array('A', 'R', 'V', 'P', 'D', 'W', 'S');
        

        $vuStatus = new Valid('user_status_search');

        $vuStatus->addRule(new Rule_WhiteList($whiteListArray));

        if ($request->valid($vuStatus)) {

            if($request->isPost()) {
                $status = $request->get('user_status_search');

                
                $criteria[] = new UserStatusCriteria($status);
                
      
            }
            else {
                $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST');
            }
        }
        else{
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }
        $this->userIterator = $dao->searchUserByCriteria($criteria);


    }

    



 
    function getOffset() {
        return $this->offset;
    }

    /**
     * init the number of rows to display in the user search view
     *
     * @param int $nbrowstodisplay
     */
    function initNbRowsToDisplay($nbrowstodisplay) {

      //   $request =& HTTPRequest::instance();

//         $v = new Valid('nbtodisplay');
//         $v->addRule(new Rule_Int());
        

//         if (!is_null($nbrowstodisplay)) {

//             if($request->valid($v)) {
            
//                 if($request->isPost()) {
//                     $nbrowstodisplay = $request->get('nbtodisplay');
//                     $this->nbrowstodisplay = $nbrowstodisplay;
//                 }
//             }
//             else {
//                 $GLOBALS['Response']->addFeedback('error', 'You must enter an integer');
//                 $this->initNbRowsToDisplay($nbrowstodisplay);
//             }
//         }
//         else {
//             $this->nbrowstodisplay = 50;
//         }
    }

    /**
     * init the start parameter of the list in the browse part
     *
     * @param int $startlist
     */
    function initStartList($startlist) {

    }

    /**
     * init the end parameter of the list in the browse part
     *
     * @param int $endlist
     */
    function initEndList($endlist) {

    }
    
    /**
     * @return int the number of rows to display
     */
    function getNbRowsToDisplay() {
        return $this->nbrowstodisplay;
    }
    
    function request() {
  
        //        $this->initNbRowsToDisplay($_POST['nbtodisplay']);

        // $this->initOffset($offset);
        
        $this->setUserIterator();
    }
}




Class SearchUserIterator {

    private $select;

    private $from;

    private $where;

    private $orderby;

    /**
     * Constructor
     */
    function __contruct($select, $from, $where, $orderby) {
        $this->select = $select;
        $this->from = $from;
        $this->where = $where;
        $this->orderby = $orderby;
    }

    /**
     * init the all  statements
     *
     * @param string $criteria
     */
//     function setStatement($select, $from, $where, $orderby) {
//         $this->select = $select;
//         $this->from = $from;
//         $this->where = $where;
//         $this->orderby = $orderby;
//     }

    /**
     * @return string the select statement
     */
    function getSelect() {
        return $this->select;
    }

    /**
     * @return string the from statement
     */
    function getFrom() {
        return $this->from;
    }

    /**
     * @return string the where statement
     */
    function getWhere() {
        return $this->where;
    }

    /**
     * @return string the order by statement
     */
    function getOrderBy() {
        return $this->orderby;
    }


    function getStatement() {
        $this->getSelect();
        $this->getFrom();
        $this->getWhere();
        $this->getOrderBy();
    }
}


class CriteriaIterator implements Iterator {

    function __construct() {

    }

    function current() {}

    function next() {}

    function key() {}

    function valid() {}

    function rewind() {}

    function setOffset($offset) {
        if (!is_null($offset)) {
            $this->offset = $offset;
        }
        else {
            $this->offset = 0;
        }
    }

    function getOffset() {
        return $this->offset;
    }

    function setLimit($llimit) {

        $request =& HTTPRequest::instance();

        $v = new Valid('nbtodisplay');
        $v->addRule(new Rule_Int());
        

        if (!is_null($limit)) {

            if($request->valid($v)) {
            
                if($request->isPost()) {
                    $limit = $request->get('nbtodisplay');
                    $this->limit = $limit;
                }
            }
            else {
                $GLOBALS['Response']->addFeedback('error', 'You must enter an integer');
                $this->initNbRowsToDisplay($limit);
            }
        }
        else {
            $this->limit = 50;
        }
    }

    function getLimit() {
        return $this->limit;
    }
}



?>
