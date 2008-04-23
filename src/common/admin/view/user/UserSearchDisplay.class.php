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
 * UserSearchDisplay
 *
 */
class UserSearchDisplay extends AdminSearchDisplay {

    function __construct($userIterator, $nbrows) {
        
        $this->userIterator = $userIterator;
        $this->nbrows = $nbrows;
    }

    /**
     * displayHeader()
     *
     */
    function displayHeader() {
        $GLOBALS['Language']->loadLanguageMsg('admin/admin');

        session_require(array('group'=>'1','admin_flas'=>'A'));

        $GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('admin_userlist','title')));
        parent::displayHeader($GLOBALS['Language']->getText('admin_userlist','user_list').': <b>'.$GLOBALS['Language']->getText('admin_userlist','all_groups').'</b>');
    }

    /**
     *displaySearchFilter()
     *
     */
    function displaySearchFilter() {
      
        parent::displaySearchFilter($GLOBALS['Language']->getText('admin_main','display_user'), '?user_shortcut_search');
       
        print '<table width=100%>';

        print '<tr>';

        print '<td align="center" width=33%>';

        print 'Search (Login Name, Real Name):';
        print '<form name="usersearch" action="index.php" method="POST">';
        print '<input type="text" name="user_name_search">';
        
        print '</td>';

        print '<td align="center" width=33%>';
      
        print 'Search (GroupName, GroupUnixName):';
        print '<input type="text" name="user_group_search">';

        print '</td>';
        
        print '<td align="center" width=33%>';
        
        print '<b>Status <a href="javascript:help_window(\'/help/browse_tracker_query_field.php?helpid=101%7C101%7Cstatus_id\')"><b>[?]</b></a></b><br />';
         
        print '<select name="user_status_search" id="status_id">';
        
        print '<option value="all">All</option>';
        print '<option value="A">Active</option>';
        print '<option value="R">Restricted</option>';
        print '<option value="V">Validated</option>';
        print '<option value="P">Pending</option>';
        print '<option value="D">Deleted</option>';
        print '<option value="W">Validated as Restricted</option>';
        print '<option value="S">Suspended</option>';
        print '</select>'; 
        
        print '</td>';

        print '</tr>';
        print '<table>';

        print '<p><input type="submit" value="Browse" name="SUBMIT"/> <input type="text" value="'.$this->nbrows.'" maxlength="5" size="3" name="nbtodisplay"/> users at once.</p>';
        
        print '</form>';
    }

    /**
     * displayBrowse
     *
     */
    function displayBrowse() {
        parent::displayBrowse();
    }

    /**
     * displaySearch()
     *
     */
    function displaySearch() {

        $odd_even = array('boxitem', 'boxitemalt');
        $i = 1;
        
        print '<table width=100% cellspacing=0 cellpadding=0 border="1" align="center">';
        
        print '<tr><th>Select?</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('include_user_home','login_name').'</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('include_user_home','real_name').'</th>';
        
        print '<th>Profile</th>';
        
        print '<th>Status</th>';
        
        print '<th>Mail to</th></tr>';        
        
        foreach($this->userIterator as $u) {

            print '<tr class="'.$odd_even[$i++ % count($odd_even)].'">
<td align="center"><input type="checkbox" name="admin" id="admin" align="center"/></td>
<td class="user_active"><a href="#">'.$u['user_name'].'</a></td>
<td><a href="#">'.$u['realname'].'</a></td>
<td><a href="#">[DevProfil]</a></td>
<td>';

            if ($u['status'] == 'A') print 'Active';
            if ($u['status'] == 'R') print 'Restricted';
            if ($u['status'] == 'V') print 'Validated';
            if ($u['status'] == 'P') print 'Pending';
            if ($u['status'] == 'D') print 'Deleted';
            if ($u['status'] == 'W') print 'Restricted';
            if ($u['status'] == 'S') print 'Suspended';   
            print '</td>
<td><a href="'.$u['email'].'">'.$u['email'].'</a></td></tr>';
        }
        print '</table>';       
    }

    function displayFooter() {
        $GLOBALS['HTML']->footer(array());
    }
}
?>
