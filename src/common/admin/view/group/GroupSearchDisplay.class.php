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
     * $mainGroupIterator
     *
     * @type mixed $mainGroupItertator
     */
    private $mainGroupIterator;

    /**
     * $adminEmailIterator
     *
     * @type mixed $adminEmailIterator
     */
    private $adminEmailIterator;

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

    /**
     * $shortcut
     *
     * @type string $shortcut
     */
    private $shortcut;

    /**
     * $name
     *
     * @type string $name
     */
    private $name;

    /**
     * $status
     *
     * @type string $status
     */
    private $status;

    /**
     * $state
     *
     * @type string $state
     */
    private $state;

    /**
     * $type
     *
     * @type string $type
     */
    private $type;

    /**
     * constructor
     */
    function __construct($mainGroupIterator, $adminEmailIterator, $offset, $nbrows, $nbgroup, $shortcut, $name, $status, $state, $type) {

        $this->mainGroupIterator = $mainGroupIterator;
        $this->adminEmailIterator = $adminEmailIterator;
        $this->offset = $offset;
        $this->nbrows = $nbrows;
        $this->nbgroup = $nbgroup;
        $this->shortcut = $shortcut;
        $this->name = $name;
        $this->status = $status;
        $this->state = $state;
        $this->type = $type;
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

        print '<br><a href="index.php?action=add_group">['.$GLOBALS['Language']->getText('admin_grouplist','add_group').']</a>';
              
        parent::displayHeader($GLOBALS['Language']->getText('admin_grouplist','for_categ').' <b>'.$GLOBALS['Language']->getText('admin_grouplist','all_categ').'</b>');

        ?>

            <script type="text/javascript" src="/scripts/prototype/prototype.js"></script>
                 <script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>
                 <script type="text/javascript">
                 
                 Event.observe(window, 'load', function () {
                         var ori = $('group_name');
                         if (ori) {
                             var update = Builder.node('div', {id:'group_name_choices', style:'background:white', class:'autocompletion'});
                             
                             Element.hide(update);
                             
                             ori.parentNode.appendChild(update);
                             new Ajax.Autocompleter('group_name', update, '/project/autocompletion.php', {
                                 tokens: ',', paramName: 'value'
                                         });
                         }
                     }); 
                       
        </script>
              
              
              
              <?php
              }
    
    /**
     * displaySearchFilter()
     *
     */
    function displaySearchFilter() {
        
        parent::displaySearchFilter($GLOBALS['Language']->getText('admin_main','display_group'), 'group_shortcut_search', $this->offset, $this->nbrows);
        
        print '<table width=100%>';
        
        print '<tr>';

        print '<td align="center" width=25%>';
        
        print 'Search (GroupName, GroupUnixName):';
        print '<form name="groupsearch" action="index.php" method="POST">';
        print '<input type="text" name="group_name_search" id="group_name">';
           
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
        
        print '<p><input type="submit" value="Browse" name="SUBMIT"/> <input type="text" value="'.$this->nbrows.'" maxlength="5" size="3" name="nbrows"/> groups at once.</p>';

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
        
        $link = '&group_shortcut_search='.$this->shortcut.'&group_name_search='.$this->name.'&group_status_search='.$this->status.'&group_state_search='.$this->state.'&group_type_search='.$this->type;

        parent::displayBrowse($this->start, $this->end, $this->offset, $this->nbrows, $this->nbgroup, $this->offsetmax, $link);
        
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
            
            foreach ($this->mainGroupIterator as $mGroupIterator =>$val) {
                $iGroup = $val['group_id'];
                
                $group_name = $val['group_name'];
                $unix_group_name = $val['unix_group_name']; 
                $status = $val['status'];  
                $name = $val['name'];
                $is_public = $val['is_public'];
                $license = $val['license'];
                $c = $val['c'];
                $email = null;

                print '<tr class="'.$odd_even[$i++ % count($odd_even)].'">
 <td align="center"><a href="/admin/groupedit.php?group_id='.$iGroup.'">'.$group_name.'</a></td>
 <td>'.$unix_group_name.'</td>
 <td >'.$status.'</td>
 <td>'.$name.'</td>
 <td>'.$is_public.'</td>
 <td>'.$license.'</a></td>
 <td>'.$c.'</td>';
   
                do {
                    $groupMatch = true;
                    $valaEmail = $this->adminEmailIterator->current();
                    if($valaEmail['group_id'] == $iGroup) {
                        $email .= $valaEmail['email'].';';
                        $this->adminEmailIterator->next();
                    } else {
                        $groupMatch = false;
                    }
                } while ($this->adminEmailIterator->valid() && $groupMatch);
                
                $email = substr($email,0,strlen($email) - 1);

                if ($email) {
                    print '<td><a href="mailto:'.$email.'">Mailto</a></td></tr>';
                } else {
                    print '<td></td></tr>';
                }
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
