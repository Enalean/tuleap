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
 * GroupSearchDisplay
 *
 */
class GroupSearchDisplay extends AdminSearchDisplay {
    
    /**
     * $groupIterator
     *
     * @type Iterator $groupIterator
     */
    private $groupIterator;

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
     * $nbgroup
     *
     * @type int $nbgroup
     */
    private $nbgroup;

    /**
     * $offsetmax
     *
     * @type int $offsetmax
     */
    private $offsetmax;

    function __construct($groupIterator, $offset, $nbrows, $nbuser) {

        $this->groupIterator = $groupIterator;
        $this->offset = $offset;
        $this->nbrows = $nbrows;
        $this->nbgroup = $nbgroup;        
    }
    
    /**
     * initStart()
     *
     */
    function initStart() {
        $this->start = $this->offset+1;
    }

    /**
     * initEnd()
     *
     */
    function initEnd() {

        if ($this->nbrows > $this->nbgroup) {
            $this->end = $this->nbgroup;
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

        $this->offsetmax = $this->nbgroup - $this->nbrows;

    }

    /**
     * displayHeader()
     *
     */
    function displayHeader() {


        $GLOBALS['Language']->loadLanguageMsg('admin/admin');

        session_require(array('group'=>'1','admin_flas'=>'A'));
        //changer le titre
        $GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('admin_userlist','title')));
        

        
        //regarder si il n'y a pas de all categorie dans le site-content
        parent::displayHeader($GLOBALS['Language']->getText('admin_grouplist','for_categ').' <b>All Categories</b>');
    }

    function displaySearchFilter() {
        
        parent::displaySearchFilter($GLOBALS['Language']->getText('admin_main','display_group'), '?group_shortcut_search');

        print '<table width=100%>';
        
        print '<tr>';

        print '<td align="center" width=25%>';
        
        print 'Search (GroupName, GroupUnixName):';
        print '<form name="groupsrch" action="index.php" method="POST">';
        print '<input type="text" name="group_name_search">';
        print '<input type="submit" value="'.$GLOBALS['Language']->getText('admin_main', 'search').'">';
        
        print '</td>';
        
        print '<td align="center" width=25%>';
        
        print '<b>Status <a href="javascript:help_window(\'/help/browse_tracker_query_field.php?helpid=101%7C101%7Cstatus_id\')"><b>[?]</b></a></b><br />';
        
        print '<select name="group_status_search" id="status_id">';
        print '<option value="all">All</options>';
        print '<option value="">Incomplete</option>';
        print '<option value="">Active</option>';
        print '<option value="">Pending</option>';
        print '<option value="">Holding</option>';
        print '<option value="">Deleted</option>';
        print '</select>';
        
        print '</td>';
        
        print '<td align="center" width=25%>';
        
        print '<b>Public? </b><br />';
        
        print '<select name="group_state_search" id="state_id">';
        print '<option value="">Any</option>';
        print '<option value="">Yes</option>';
        print '<option value="">No</option>';
        print '</select>';
    
        print '</td>';
        
        print '<td align="center" width=25%>';
        print '<b>Project type </b><br />';
        
        print '<select name="group_type_search" id="type_id">';
        print '<option value="">Project</option>';
        print '<option value="">Template</option>';
        print '<option value="">Test Projet</option>';
        print '</select>';
        
        print '</td>';

        print '</tr>';

        print '</table>';
        
        print '<p><input type="submit" value="Browse" name="SUBMIT"/> <input type="text" value="50" maxlength="5" size="3" name="chunksz"/> groups at once.</p>';

    }

    /**
     * displayBrowse()
     *
     */
    function displayBrowse() {
        $this->initStart();
        $this->initEnd();
        $this->initMaxOffset();
        parent::displayBrowse($this->start, $this->end, $this->offset, $this->nbrows, $this->nbgroup, $this->offsetmax);
    }
   
    /**
     * displaySearch()
     *
     */
    function displaySearch() {
     
        $odd_even = array('boxitem', 'boxitemalt');
        $i = 1;

        print '<table width=100% cellspacing=0 cellpadding=0 border="1" align="center">';
        
        print '<tr><th>Group Name '.$GLOBALS['Language']->getText('admin_grouplist','click').'</th>';
        
        print '<th>Unix Group Name</th>';
        
        print '<th>Status</th>';
        
        print '<th>Project Type</th>';
        
        print '<th>Status</th>';
        
        print '<th>License</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('admin_grouplist','categ').'</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('admin_grouplist','members').'</th>';
        
        print '<th>Mailto</th></tr>';

        foreach($this->userIterator as $u) {
        
            //rajouter les test sur les status et rajouter les valeurs des status dans la liste

            print '<tr class="'.$odd_even[$i++ % count($odd_even)].'">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';
        }
        print '</table>';
    }

    /**
     * displayFooter()
     */
    function displayFooter() {
        $GLOBALS['HTML']->footer(array());
    }
}

?>
