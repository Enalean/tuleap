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
require_once('account.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('common/admin/view/AdminSearchDisplay.class.php');


/**
 * UserSearchDisplay
 *
 */
class UserSearchDisplay extends AdminSearchDisplay {

    /**
     * $userIterator
     *
     * @type Iterator $userIterator
     */
    private $userIterator;

    /**
     * $offset
     *
     * @type int $offset
     */
    private $offset;

    /**
     * $nbrows
     *
     * @type int $nbrows
     */
    private $nbrows;

    /**
     * $start
     *
     * @type int $start
     */
    private $start;

    /**
     * $end
     *
     * @type int $end
     */
    private $end;

    /**
     * $nbuser
     *
     * @type int $nbuser
     */
    private $nbuser;

    /**
     * $offsetmax
     *
     * @type int $offsetmax
     */
    private $offsetmax;

    /**
     * $shortcut
     *
     * @type string $shortcut
     */
    private $shortcut;

    /**
     * $username
     *
     * @type string $username
     */
    private $username;

    /**
     * $group
     *
     * @type string $group
     */
    private $group;
    
    /**
     * $status
     *
     * @type string $status
     */
    private $status;


    /**
     * constructor
     */
    function __construct($userIterator, $offset, $nbrows, $nbuser, $shortcut, $username, $group, $status) {
        
        parent::__construct(); 
        $this->userIterator = $userIterator;
        $this->offset = $offset;
        $this->nbrows = $nbrows;
        $this->nbuser = $nbuser;
        $this->shortcut = $shortcut;
        $this->username = $username;
        $this->group = $group;
        $this->status = $status;
    }

    /**
     * initStart()
     *
     */
    function initStart() {
        if ($this->nbuser == 0) {
            $this->start = 0;
        }
        else {
            $this->start = $this->offset + 1;
        }
    }

    /**
     * initEnd()
     *
     */
    function initEnd() {

        if ($this->nbrows > $this->nbuser) {
            $this->end = $this->nbuser;
        }
        else {
            $this->end = $this->offset + $this->nbrows;
        }
    }

    /**
     * initMaxOffset()
     *
     */
    function initMaxOffset() {

        $this->offsetmax = $this->nbuser - $this->nbrows;
    }

    /**
     * displayHeader()
     *
     */
    function displayHeader() {

        session_require(array('group'=>'1','admin_flas'=>'A'));

        $GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('admin_userlist','title')));
        parent::displayHeader($GLOBALS['Language']->getText('admin_userlist','user_list').': <b>'.$GLOBALS['Language']->getText('admin_userlist','all_groups').'</b>');

        
        ?>
            <script type="text/javascript" src="/scripts/prototype/prototype.js"></script>
                 <script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>
                 <script type="text/javascript" src="/scripts/formcontrol.js"></script>
                 <script type="text/javascript">
                 
                 
                 Event.observe(window, 'load', function () {
                         var ori = $('user_name');
                         if (ori) {
                             var update = Builder.node('div', {id:'user_name_choices', style:'background:white', class:'autocompletion'});
                             Element.hide(update);
                             ori.parentNode.appendChild(update);
                             new Ajax.Autocompleter('user_name', update, '/user/autocompletion.php', {
                                 tokens: ',', paramName: 'value'
                                         });
                         }
                     });
    
        
        Event.observe(window, 'load', function () {
                var ori = $('user_group');
                if (ori) {
                    var update = Builder.node('div', {id:'user_group_choices', style:'background:white', class:'autocompletion'});
                    
                    Element.hide(update);
                    
                    ori.parentNode.appendChild(update);
                    new Ajax.Autocompleter('user_group', update, '/project/autocompletion.php', {
                        tokens: ',', paramName: 'value'
                                });
                }
            }); 
        
               
        </script>
              
              <?php 
              }
    
    
    /**
     *displaySearchFilter()
     *
     */
    function displaySearchFilter() {

        parent::displaySearchFilter($GLOBALS['Language']->getText('admin_main','display_user'), 'user_shortcut_search',$this->offset, $this->nbrows);
        
        print '<table width=100%>';

        print '<tr>';

        print '<td align="center" width=33%>';

        print 'Search (Login Name, Real Name):';
        print '<form name="usersearch" action="index.php" method="POST">';
        
        print '<input type="text" class="autocompletion" name="user_name_search" id="user_name" />';

        print '</td>';

        print '<td align="center" width=33%>';
      
        print 'Search (GroupName, GroupUnixName):<br />';
        print '<input type="text" class="autocompletion" name="user_group_search" id="user_group" />';
        print '<input type="hidden" name="user_group_search_id" id="group_id" />';

        print '</td>';
        
        print '<td align="center" width=33%>';
        
        print '<b>Status <a href="javascript:help_window(\'/help/browse_tracker_query_field.php?helpid=101%7C101%7Cstatus_id\')"><b>[?]</b></a></b><br />';
         
        print '<select name="user_status_search" id="status_id">';
        
        print '<option value="all">All</option>';
        print '<option value="A">'.$GLOBALS['Language']->getText('admin_userlist','active').'</option>';
        print '<option value="R">'.$GLOBALS['Language']->getText('admin_userlist','restricted').'</option>';
        print '<option value="V">'.$GLOBALS['Language']->getText('admin_userlist','validated').'</option>';
        print '<option value="P">Pending</option>';
        print '<option value="D">'.$GLOBALS['Language']->getText('admin_userlist','deleted').'</option>';
        print '<option value="W">Validated as Restricted</option>';
        print '<option value="S">'.$GLOBALS['Language']->getText('admin_userlist','suspended').'</option>';
        print '</select>'; 
        
        print '</td>';

        print '</tr>';
        print '<table>';

        print '<p><input type="submit" value="'.$GLOBALS['Language']->getText('global','btn_browse').'" name="SUBMIT"/> <input type="text" value="'.$this->nbrows.'" maxlength="5" size="3" name="nbtodisplay"/> users at once.</p>';
        
        print '</form>';

        print $this->nbuser.' '.$GLOBALS['Language']->getText('tracker_include_report','matching');
    }

    /**
     * displayBrowse()
     *
     */
    function displayBrowse() {
        $this->initStart();
        $this->initEnd();
        $this->initMaxOffset();


        $this->link = '&user_shortcut_search='.$this->shortcut.'&user_name_search='.$this->username.'&user_group_search='.$this->group.'&user_status_search='.$this->status;
   
        parent::displayBrowse($this->start, $this->end, $this->offset, $this->nbrows, $this->nbuser, $this->offsetmax, $this->link);
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


        print '<form name="userdisplay action="index.php" method="POST">';

        foreach($this->userIterator as $u) {

            print '<tr class="'.$odd_even[$i++ % count($odd_even)].'">
<td align="center"><input type="checkbox" name="user_id[]" value="'.$u['user_id'].'" align="center"/></td>
<td><a href="?user_id='.$u['user_id'].'">'.$u['user_name'].'</a></td>
<td>'.$u['realname'].'</td>
<td><a href="/users/'.$u['user_name'].'">[DevProfil]</a></td>
<td>';

            if ($u['status'] == 'A') print $GLOBALS['Language']->getText('admin_userlist','active');
            if ($u['status'] == 'R') print $GLOBALS['Language']->getText('admin_userlist','restricted');
            if ($u['status'] == 'V') print $GLOBALS['Language']->getText('admin_userlist','validated');
            if ($u['status'] == 'P') print 'Pending';
            if ($u['status'] == 'D') print $GLOBALS['Language']->getText('admin_userlist','deleted');
            if ($u['status'] == 'W') print $GLOBALS['Language']->getText('admin_userlist','restricted');
            if ($u['status'] == 'S') print $GLOBALS['Language']->getText('admin_userlist','suspended');   
            print '</td>
<td><a href="'.$u['email'].'">'.$u['email'].'</a></td></tr>';
        }
        print '</table>';       
        print '<p><a onClick="checkAll(1);">Check all items</a> - <a onClick="checkAll(0);">Clear all items</a></p>';

        print '<input type="submit" value="'.$GLOBALS['Language']->getText('global','btn_submit').'" name="submit">';

    print '</form>';
    }

    /**
     * displayFooter()
     */
    function displayFooter() {
        $GLOBALS['HTML']->footer(array());
    }
}
?>
