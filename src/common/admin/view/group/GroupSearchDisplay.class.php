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
require_once('admin_view.php');

/**
 * GroupSearchDisplay
 *
 */
class GroupSearchDisplay extends AdminSearchDisplay {
    
    function __construct() {
        
    }
    
    function displayHeader() {
        parent::displayHeader($GLOBALS['Language']->getText('admin_grouplist','for_categ').' <b>All Categories</b>');
    }

    function displaySearchFilter() {
        
        parent::displaySearchFilter($GLOBALS['Language']->getText('admin_main','display_group'));

        print '<table width=100%>';
        
        print '<tr>';

        print '<td align="center" width=25%>';
        
        print 'Search (GroupName, GroupUnixName):';
        print '<form name="usersrch" action="search.php" method="POST">';
        print '<input type="text" name="search">';
        print '<input type="hidden" name="usersearch" value="1">';
        print '<input type="submit" value="'.$GLOBALS['Language']->getText('admin_main', 'search').'">';
        print '</form>';
        
        print '</td>';
        
        print '<td align="center" width=25%>';
        
        print '<b>Status <a href="javascript:help_window(\'/help/browse_tracker_query_field.php?helpid=101%7C101%7Cstatus_id\')"><b>[?]</b></a></b><br />';
        
        print '<select name="status_id" id="status_id">';
        print '<option value="">Incomplete</option>';
        print '<option value="">Active</option>';
        print '<option value="">Pending</option>';
        print '<option value="">Holding</option>';
        print '<option value="">Deleted</option>';
        print '</select>';
        
        print '</td>';
        
        print '<td align="center" width=25%>';
        
        print '<b>Public? </b><br />';
        
        print '<select name="status_id" id="status_id">';
        print '<option value="">Any</option>';
        print '<option value="">Yes</option>';
        print '<option value="">No</option>';
        print '</select>';
    
        print '</td>';
        
        print '<td align="center" width=25%>';
        print '<b>Project type </b><br />';
        
        print '<select name="status_id" id="status_id">';
        print '<option value="">Project</option>';
        print '<option value="">Template</option>';
        print '<option value="">Test Projet</option>';
        print '</select>';
        
        print '</td>';

        print '</tr>';

        print '</table>';
        
        print '<p><input type="submit" value="Browse" name="SUBMIT"/> <input type="text" value="50" maxlength="5" size="3" name="chunksz"/> groups at once.</p>';

    }

    function displayBrowse() {
        parent::displayBrowse();
    }
   
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
        
        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';


        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_suspended"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>s</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_pending"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>P</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_holding"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >H</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';


        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_deleted"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>D</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_incomplete"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>I</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';


        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';


        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';


        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';


        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';


        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';


        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';
        
        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td >A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';


        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">
<td align="center" class="group_active"><a href="#">CodeX Administration Project</a></td>
<td>codex</td>
<td>A</td>
<td>Project</td>
<td>1</td>
<td>xrx</a></td>
<td>0</td>
<td>1</td>
<td><a href="mailto:admin@domain">mailto</a></td></tr>';

        print '</table>';
    }
}
?>
