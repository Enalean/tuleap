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
require_once('common/dao/UserDao.class.php');
require_once('common/dao/CodexDataAccess.class.php');


/**
 * UserAutocompletionControler()
 */
class UserAutocompletionControler {
   
    /**
     * constructor
     */    
    function __construct() {
       
    }


    /**
     * initUserIterator()
     *
     */
    function initUserIterator() {
        
        $dao = new UserDao(CodexDataAccess::instance());
        
        $filter = array();
        
        $request =& HTTPRequest::instance();

        $vuName = new Valid_String('value');
        
        if ($request->valid($vuName)) {
            
            $name = $request->get('value');
            $filter[] = new UserNameFilter($name);
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }
        
        $this->userIterator = $dao->searchUserByFilter($filter, 0, 10);
    }

    /**
     * display()
     *
     */
    function display() {
        
        print '<ul class="autocomplete">'; 
        
        foreach($this->userIterator as $u) {
            
            print '<li class="autocomplete"><div class="gen_prop_alliwed_project_choices"><span class="informal">('.$u['user_id'].') </span>'.$u['user_name'].'<span class="informal"> '.$u['realname'].'</span></div>';
            
            print '</li>';   
        }
        print '</ul>';
    }
  

    /**
     * request()
     */
    function request() {

        $this->initUserIterator();
        $this->display();
    }
}

$userautocompletion = new UserAutocompletionControler();
$userautocompletion->request();
?>
