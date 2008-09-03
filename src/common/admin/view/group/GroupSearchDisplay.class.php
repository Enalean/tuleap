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
require_once 'account.php';
require_once 'www/project/admin/ugroup_utils.php';
require_once 'common/admin/view/AdminSearchDisplay.class.php';

/**
 * GroupSearchDisplay
 *
 */
class GroupSearchDisplay extends AdminSearchDisplay
{
    /**
     * $_mainGroupIterator
     *
     * @type mixed $_mainGroupItertator
     */
    private $_mainGroupIterator;

    /**
     * $_adminEmailIterator
     *
     * @type mixed $_adminEmailIterator
     */
    private $_adminEmailIterator;

    /**
     * $_offset
     *
     * @type int $_offset
     */
    private $_offset;

    /**
     * $_nbrows
     *
     * @type int $_nbrows
     */
    private $_nbrows;

    /**
     * $_start
     *
     * @type int $_start
     */
    private $_start;

    /*
     * $_end
     *
     * @type int $_end
     */
    private $_end;

    /**
     * $_nbgroup
     *
     * @type int $_nbgroup
     */
    private $_nbgroup;

    /**
     * $_offsetmax
     *
     * @type int $_offsetmax
     */
    private $_offsetmax;

    /**
     * $_shortcut
     *
     * @type string $_shortcut
     */
    private $_shortcut;

    /**
     * $_name
     *
     * @type string $_name
     */
    private $_name;

    /**
     * $_status
     *
     * @type string $_status
     */
    private $_status;

    /**
     * $_state
     *
     * @type string $_state
     */
    private $_state;

    /**
     * $_type
     *
     * @type string $_type
     */
    private $_type;

    /**
     * constructor
     *
     * @param mixed  $mainGroupIterator  contain Groups' information except admins' email
     * @param mixed  $adminEmailIterator contain admin's email
     * @param int    $offset             offset of the two iterators
     * @param int    $nbrows             number of group to display once
     * @param int    $nbgroup            number of group (result after a search)
     * @param char   $shortcut           the fisrt letter of group name
     * @param string $name               group name
     * @param string $status             group status
     * @param string $state              group state (public ?)
     * @param string $type               group type
     */
    function __construct($mainGroupIterator, $adminEmailIterator, $offset, $nbrows, $nbgroup, $shortcut, $name, $status, $state, $type) 
    {
        $this->_mainGroupIterator  = $mainGroupIterator;
        $this->_adminEmailIterator = $adminEmailIterator;
        $this->_offset             = $offset;
        $this->_nbrows             = $nbrows;
        $this->_nbgroup            = $nbgroup;
        $this->_shortcut           = $shortcut;
        $this->_name               = $name;
        $this->_status             = $status;
        $this->_state              = $state;
        $this->_type               = $type;
    }

    /**
     * initStart()
     *
     * @return void
     */
    function initStart() 
    {
        if ($this->_nbgroup == 0) {
            $this->_start = 0;
        } else {
            $this->_start = $this->_offset+1;
        }
    }

    /**
     * initEnd()
     *
     * @return void
     */
    function initEnd() 
    {
        if ($this->_nbrows > $this->_nbgroup) {
            $this->_end = $this->_nbgroup;
        } else {
            $this->_end = $this->_offset + $this->_nbrows;
        }
    }

    /**
     * initMaxOffset()
     *
     * @return void
     */
    function initMaxOffset() 
    {
        $this->_offsetmax = $this->_nbgroup - $this->_nbrows;
    }

    /**
     * displayHeader()
     *
     * @return void
     */
    function displayHeader() 
    {
        $GLOBALS['Language']->loadLanguageMsg('admin/admin');

        session_require(array('group'=>'1', 'admin_flags'=>'A'));

        $GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('admin_grouplist', 'title')));

        parent::displayHeader($GLOBALS['Language']->getText('admin_grouplist', 'for_categ').' <b>'.$GLOBALS['Language']->getText('admin_grouplist', 'all_categ').'</b>');

        ?>

            <script type="text/javascript" src="/scripts/prototype/prototype.js"></script>
                 <script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>
                 <script type="text/javascript">

                 Event.observe(window, 'load', function () {
                         var ori = $('group_name');
                         if (ori) {
                             var update = Builder.node('div', 
                                                       {id:'group_name_choices', 
                                                               style:'background:white', 
                                                               class:'autocompletion'});
                             Element.hide(update);    
                             ori.parentNode.appendChild(update);
                             new Ajax.Autocompleter('group_name', 
                                                    update, 
                                                    '/project/autocompletion.php', 
                                                    {
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
     * @return void
     */
    function displaySearchFilter() 
    {
        parent::displaySearchFilter($GLOBALS['Language']->getText('admin_main', 'display_group'), 
                                    'group_shortcut_search', 
                                    $this->_offset, 
                                    $this->_nbrows);

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
        print '<option value="I">'.$GLOBALS['Language']->getText('admin_groupedit', 'incomplete').'</option>';
        print '<option value="A">'.$GLOBALS['Language']->getText('admin_groupedit', 'active').'</option>';
        print '<option value="P">'.$GLOBALS['Language']->getText('admin_groupedit', 'pending').'</option>';
        print '<option value="H">'.$GLOBALS['Language']->getText('admin_groupedit', 'holding').'</option>';
        print '<option value="D">'.$GLOBALS['Language']->getText('admin_groupedit', 'deleted').'</option>';
        print '</select>';

        print '</td>';

        print '<td align="center" width=25%>';

        print '<b>Public? </b><br />';

        print '<select name="group_state_search" id="state_id">';
        print '<option value="any">'.$GLOBALS['Language']->getText('global', 'any').'</option>';
        print '<option value="1">'.$GLOBALS['Language']->getText('global', 'yes').'</option>';
        print '<option value="0">'.$GLOBALS['Language']->getText('global', 'no').'</option>';
        print '</select>';

        print '</td>';

        print '<td align="center" width=25%>';
        print '<b>Project type </b><br />';

        print '<select name="group_type_search" id="type_id">';
        print '<option value="any">'.$GLOBALS['Language']->getText('global', 'any').'</option>';
        print '<option value="1">'.$GLOBALS['Language']->getText('include_common_template', 'project').'</option>';
        print '<option value="2">'.$GLOBALS['Language']->getText('include_common_template', 'template').'</option>';
        print '<option value="3">'.$GLOBALS['Language']->getText('include_common_template', 'test_project').'</option>';
        print '</select>';

        print '</td>';

        print '</tr>';

        print '</table>';

        print '<p><input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_browse').'" name="SUBMIT"/> <input type="text" value="'.$this->_nbrows.'" maxlength="5" size="3" name="nbrows"/> groups at once.</p>';

        print '</form>';

        print $this->_nbgroup.' '.$GLOBALS['Language']->getText('tracker_include_report', 'matching');
    }

    /**
     * displayBrowse()
     *
     * @return void
     */
    function displayBrowse() 
    {
        $this->initStart();
        $this->initEnd();
        $this->initMaxOffset();

        $link = '&group_shortcut_search='.$this->_shortcut.
                '&group_name_search='.$this->_name.
                '&group_status_search='.$this->_status.
                '&group_state_search='.$this->_state.
                '&group_type_search='.$this->_type;

        parent::displayBrowse($this->_start, 
                              $this->_end, 
                              $this->_offset, 
                              $this->_nbrows, 
                              $this->_nbgroup, 
                              $this->_offsetmax, 
                              $link);
    }

    /**
     * displaySearch()
     *
     * @return void
     */
    function displaySearch() 
    {       
        $odd_even = array('boxitem', 'boxitemalt');
        $i        = 1;

        print '<table width=100% cellspacing=0 cellpadding=0 border="1" align="center">';

        print '<tr><th>'.$GLOBALS['Language']->getText('admin_groupedit', 'grp_name').' '.$GLOBALS['Language']->getText('admin_grouplist', 'click').'</th>';

        print '<th>'.$GLOBALS['Language']->getText('admin_groupedit', 'unix_grp').'</th>';

        print '<th>'.$GLOBALS['Language']->getText('global', 'status').'</th>';

        print '<th>'.$GLOBALS['Language']->getText('admin_groupedit', 'group_type').'</th>';

        print '<th>'.$GLOBALS['Language']->getText('admin_groupedit', 'public').'</th>';

        print '<th>'.$GLOBALS['Language']->getText('admin_groupedit', 'license').'</th>';

        print '<th>'.$GLOBALS['Language']->getText('admin_grouplist', 'members').'</th>';

        print '<th>'.$GLOBALS['Language']->getText('admin_user_search', 'mailto').'</th></tr>';

        if ($this->_nbgroup != 0) {  

            foreach ($this->_mainGroupIterator as $mGroupIterator =>$val) {
                $iGroup = $val['group_id'];

                $group_name      = $val['group_name'];
                $unix_group_name = $val['unix_group_name']; 
                $status          = $val['status'];  
                $name            = $val['name'];
                $is_public       = $val['is_public'];
                $license         = $val['license'];
                $c               = $val['c'];
                $email           = null;

                print '<tr class="'.$odd_even[$i++ % count($odd_even)].'">
 <td align="center"><a href="/admin/groupedit.php?group_id='.$iGroup.'">'.$group_name.'</a></td>
 <td>'.$unix_group_name.'</td>
 <td>'.$status.'</td>
 <td>'.$name.'</td>
 <td>'.$is_public.'</td>
 <td>'.$license.'</a></td>
 <td>'.$c.'</td>';

                do {
                    
                    $groupMatch = true;
                    $valaEmail  = $this->_adminEmailIterator->current();
                    if ($valaEmail['group_id'] == $iGroup) {
                        $email .= $valaEmail['email'].';';
                        $this->_adminEmailIterator->next();
                    } else {
                        $groupMatch = false;
                    }
                } while ($this->_adminEmailIterator->valid() && $groupMatch);

                $email = substr($email, 0, strlen($email) - 1);

                if ($email) {
                    print '<td><a href="mailto:'.$email.'">'.$GLOBALS['Language']->getText('admin_user_search', 'mailto').'</a></td></tr>';
                } else {
                    print '<td></td></tr>';
                }
            }
        }
        print '</table>';
    }

    /**
     * displayFooter()
     *
     * @return void
     */
    function displayFooter() 
    {
        $GLOBALS['HTML']->footer(array());
    }
}

?>
