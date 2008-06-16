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
require_once('account.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('common/admin/view/AdminSearchDisplay.class.php');

/**
 * GroupAddDisplay
 *
 */
class GroupAddDisplay extends AdminSearchDisplay {
    
    /**
     * constructor
     */
    function __construct() {

    }

    /**
     * displayHeader()
     *
     */
    function displayHeader() {


        $GLOBALS['Language']->loadLanguageMsg('admin/admin');

        session_require(array('group'=>'1','admin_flags'=>'A'));
       
        $GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('admin_grouplist','title')));

        print '<br><a href="index.php?action=add_group">['.$GLOBALS['Language']->getText('admin_grouplist','add_group').']</a>';
              
        parent::displayHeader($GLOBALS['Language']->getText('admin_grouplist','for_categ').' <b>'.$GLOBALS['Language']->getText('admin_grouplist','all_categ').'</b>');

    }
    

    /**
     * displaySearchFilter()
     *
     */
    function displaySearchFilter() {
        
        parent::displaySearchFilter($GLOBALS['Language']->getText('admin_main','display_group'), 'group_shortcut_search', $this->offset, $this->nbrows);
        
    
    }


    /**
     * displayFooter()
     */
    function displayFooter() {
        $GLOBALS['HTML']->footer(array());
    }
}

?>
