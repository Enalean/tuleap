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
     * $groupArray
     *
     * @type Array $groupArray
     */
    private $groupArray;

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

    function __construct($groupArray, $offset, $nbrows, $nbgroup) {

        $this->groupArray = $groupArray;
        $this->offset = $offset;
        $this->nbrows = $nbrows;
        $this->nbgroup = $nbgroup;        
    }
    
    /**
     * initStart()
     *
     */
    function initStart() {
        if ($this->nbgroup == 0) {
            $this->start = 0;
        }
        else {
            $this->start = $this->offset+1;
        }
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

        session_require(array('group'=>'1','admin_flags'=>'A'));
       
        $GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('admin_grouplist','title')));
              
        parent::displayHeader($GLOBALS['Language']->getText('admin_grouplist','for_categ').' <b>'.$GLOBALS['Language']->getText('admin_grouplist','all_categ').'</b>');
    }

    function displaySearchFilter() {
        
        parent::displaySearchFilter($GLOBALS['Language']->getText('admin_main','display_group'), '?group_shortcut_search');

        print '<table width=100%>';
        
        print '<tr>';

        print '<td align="center" width=25%>';
        
        print 'Search (GroupName, GroupUnixName):';
        print '<form name="groupsearch" action="index.php" method="POST">';
        print '<input type="text" name="group_name_search">';
           
        print '</td>';
        
        print '<td align="center" width=25%>';
        
        print '<b>Status <a href="javascript:help_window(\'/help/browse_tracker_query_field.php?helpid=101%7C101%7Cstatus_id\')"><b>[?]</b></a></b><br />';
        
        print '<select name="group_status_search" id="status_id">';
        print '<option value="all">All</options>';
        print '<option value="I">Incomplete</option>';
        print '<option value="A">Active</option>';
        print '<option value="P">Pending</option>';
        print '<option value="H">Holding</option>';
        print '<option value="D">Deleted</option>';
        print '</select>';
        
        print '</td>';
        
        print '<td align="center" width=25%>';
        
        print '<b>Public? </b><br />';
        
        print '<select name="group_state_search" id="state_id">';
        print '<option value="any">Any</option>';
        print '<option value="1">Yes</option>';
        print '<option value="0">No</option>';
        print '</select>';
    
        print '</td>';
        
        print '<td align="center" width=25%>';
        print '<b>Project type </b><br />';
        
        print '<select name="group_type_search" id="type_id">';
        print '<option value="any">Any</option>';
        print '<option value="1">Project</option>';
        print '<option value="2">Template</option>';
        print '<option value="3">Test Projet</option>';
        print '</select>';
        
        print '</td>';

        print '</tr>';

        print '</table>';
        
        print '<p><input type="submit" value="Browse" name="SUBMIT"/> <input type="text" value="50" maxlength="5" size="3" name="chunksz"/> groups at once.</p>';

        print '</form>';

        print $this->nbgroup.' '.$GLOBALS['Language']->getText('tracker_include_report','matching');
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
        
        print '<tr><th>'.$GLOBALS['Language']->getText('admin_groupedit','grp_name').' '.$GLOBALS['Language']->getText('admin_grouplist','click').'</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('admin_groupedit','unix_grp').'</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('global','status').'</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('admin_groupedit','group_type').'</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('admin_groupedit','public').'</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('admin_groupedit','license').'</th>';
        
        print '<th>'.$GLOBALS['Language']->getText('admin_grouplist','members').'</th>';
        
        print '<th>Mailto</th></tr>';

        
        if ($this->nbgroup != 0) {    

            foreach($this->groupArray as $ga) {
            
                print '<tr class="'.$odd_even[$i++ % count($odd_even)].'">
<td align="center" class="group_active"><a href="#">'.$ga['group_name'].'</a></td>
<td>'.$ga['unix_group_name'].'</td>
<td >'.$ga['status'].'</td>
<td>'.$ga['name'].'</td>
<td>'.$ga['is_public'].'</td>
<td>'.$ga['license'].'</a></td>
<td>'.$ga['c'].'</td>
<td><a href="mailto:'.$ga['email'].'">Mailto</a></td></tr>';
            }
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
