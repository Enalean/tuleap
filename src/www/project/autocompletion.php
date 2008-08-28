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
require_once 'common/dao/GroupDao.class.php';
require_once 'common/dao/CodexDataAccess.class.php';

/**
 * GroupAutocompletionControler()
 */
class GroupAutocompletionControler
{
    /**
     * $_groupIterator
     *
     * @type iterator $_groupIterator
     */
    private $_groupIterator;   


    /**
     * constructor
     */    
    function __construct()
    {  
    }

    /**
     * initGroupIterator()
     *
     * @return void
     */
    function initGroupIterator()
    {
        $dao = new GroupDao(CodexDataAccess::instance());

        $filter = array();

        $request =& HTTPRequest::instance();

        $validGroupName = new Valid_String('value');

        if ($request->valid($validGroupName)) {

            $name     = $request->get('value');
            $filter[] = new GroupNameFilter($name);

        } else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }

        $this->_groupIterator = $dao->searchGroupByFilter($filter, 0, 10);
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

        foreach ($this->_groupIterator as $g) {

            //list only the 10 first results
            if ($i >= 10){
                print '<li>...</li></ul>';
                break;
            }

            print '<li class="autocompletion"><div>'.$g['group_id'].' ('.$g['unix_group_name'].')<span class="informal"> '.$g['group_name'].'</span></div>';

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
        $this->initGroupIterator();
        $this->display();
    }
}

$groupautocompletion = new GroupAutocompletionControler();
$groupautocompletion->request();
?>
