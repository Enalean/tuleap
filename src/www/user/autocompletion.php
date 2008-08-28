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
require_once 'common/dao/UserDao.class.php';
require_once 'common/dao/CodexDataAccess.class.php';

/**
 * UserAutocompletionControler()
 */
class UserAutocompletionControler
{

    /**
     * $_userIterator
     *
     * @type mixed $_userIterator
     */
    private $_userIterator;

    /**
     * Constructor
     */    
    function __construct()
    {
    }

    /**
     * initUserIterator()
     *
     * @return void
     */
    function initUserIterator()
    {
        $dao = new UserDao(CodexDataAccess::instance());

        $filter = array();

        $request =& HTTPRequest::instance();
        
        //valid parameters

        //valid user name
        $validUserName = new Valid_String('value');

        if ($request->valid($validUserName)) {
            $name     = $request->get('value');
            $filter[] = new UserNameFilter($name);

        } else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        $this->_userIterator = $dao->searchUserByFilter($filter, 0, 10);
    }

    /**
     * display()
     *
     * @return void
     */
    function display()
    {
        print '<ul class="autocompletion">'; 

        $i = 0;

        foreach ($this->_userIterator as $u) {
          
            //list only the 10 first results
            if ($i >= 10){
                print '<li>...</li></ul>';
                break;
            }

            print '<li class="autocompletion"><div><span class="informal">('.$u['user_id'].') </span>'.$u['user_name'].'<span class="informal"> '.$u['realname'].'</span></div>';

            print '</li>';   

           
            $i++;
        }
        print '</ul>';
    }

    /**
     * request()
     *
     * @return void
     */
    function request()
    {
        $this->initUserIterator();
        $this->display();
    }
}

$userautocompletion = new UserAutocompletionControler();
$userautocompletion->request();
?>
