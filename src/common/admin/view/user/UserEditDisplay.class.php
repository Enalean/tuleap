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
require_once('common/admin/view/AdminEditDisplay.class.php');

/**
 * UserEditDisplay
 *
 */
class UserEditDisplay extends AdminEditDisplay {

    /**
     * $userparam
     *
     * @type mixed $userparam
     */
    private $userparam;

    /**
     * $groupparam
     *
     * @type mixed $groupparam
     */
    private $groupparam;

    /**
     * $task
     *
     * @type string $task
     */

    function __construct($userparam, $groupparam, $task) {
        parent::__construct();
        $this->userparam = $userparam;
        $this->groupparam = $groupparam;
        $this->task = $task;
    }

    /**
     * displayHeader()
     *
     */
    function displayHeader() {
        $GLOBALS['Language']->loadLanguageMsg('admin/admin');
        
        session_require(array('group'=>'1','admin_flag'=>'A'));
        
        $GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('admin_usergroup','title')));

        if(isset($this->userparam['user_id'])) {
  parent::displayHeader('<h2>'.$GLOBALS['Language']->getText('admin_usergroup','header').': '.$this->userparam['user_name'].' (ID '.$this->userparam['user_id'].')</h2>');
        }
        elseif(count($this->userparam) == 1 ) {
            parent::displayHeader('<h2>'.$GLOBALS['Language']->getText('admin_usergroup','header').': '.$this->userparam[0]['user_name'].' (ID '.$this->userparam[0]['user_id'].')</h2>');
        }
        ?>
            <script type="text/javascript" src="/scripts/autoselectlist.js"></script>
                 
                 <?php
                 }

    /**
     * displayUnixAccountInformation()
     *
     */
    function displayUnixAccountInformation() {

        $shell = array('/bin/sh', '/bin/bash', 'sbin/nologin', '/bin/bash2', '/bin/ash', '/bin/bsh', '/bin/ksh', '/bin/tcsh', '/bin/csh', '/bin/zsh');

        print '<h3>'.$GLOBALS['Language']->getText('admin_usergroup','account_info').'</h3>';

      

        print '<form method="post" name="update_user" action="/admin/usergroup.php">';


        //clic on user link
        if(isset($this->userparam['user_id'])) {


            print '<p>Shell:';

            print '<select name="form_shell">';

            for ($i = 0; $i < count($shell); $i++) {
                print '<option value="'.$shell[$i].'" ';
                if($this->userparam['shell'] == $shell[$i]) print 'selected="selected"';
                print ' >'.$shell[$i].'</option>';
            }
            
            print '</select></p>';


            print '<p>Codex Account Status:';

            print '<select name="form_codexstatus" id="codexstatus" onChange="autochangeStatus(this.form)">';
            
            print '<option value="A"';
            if ($this->userparam['status'] == 'A') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','active').'</option>';
            
            print '<option value="R"';
            if ($this->userparam['status'] == 'R' || $this->userparam['status'] == 'W') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','restricted').'</option>';
            
            print '<option value="V"';
            if ($this->userparam['status'] == 'V') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','validated').'</option>';
            
            print '<option value="P"';
            if ($this->userparam['status'] == 'P') print 'selected="selected"';
            print '>Pending</option>';
            
            print '<option value="D"';
            if ($this->userparam['status'] == 'D') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','deleted').'</option>'; 
            
            print '<option value="S"';
            if ($this->userparam['status'] == 'S') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','suspended').'</option>';
        
            print '</select></p>';


            print '<p>'.$GLOBALS['Language']->getText('admin_usergroup','unix_status').':';
        
            print '<select name="form_unixstatus">';
        
            print '<option value="N"';
            if($this->userparam['unix_status'] == 'N') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_usergroup','no_account').'</option>';

            print '<option value="A"';
            if($this->userparam['unix_status'] == 'A') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_usergroup','active').'</option>';
        
            print '<option value="S"';
            if($this->userparam['unix_status'] == 'S') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_usergroup','suspended').'</option>';
        
            print '<option value="D"';
            if($this->userparam['unix_status'] == 'D') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_usergroup','deleted').'</option>';
        
            print '</select></p>';

        
            print '<p>Email:';
            print '<input name="email" value="'.$this->userparam['email'].'" size="35" maxlength="55" type="text"></p>';
            

            print '<p>Expiry Date:';

            print '<input id="expiry_date" name="expiry_date" value="" size="15" maxlength="10" type="text">';
            print '<a href="javascript:show_calendar(\'document.update_user.expiry_date\', $(\'expiry_date\').value,\'/themes/CodeXTab/css/CodeXTab_normal.css\',\'/themes/CodeXTab/images/\');"><img src="/themes/CodeXTab/images/calendar/cal.png" alt="Click Here to Pick up a date " border="0" height="16" width="16"></a></p>';
            
                
            print '<p><input name="Update_Unix" value="Update" type="submit"></p>';

            print '</form><hr>';
        }
        // select one user
        elseif(count($this->userparam) == 1 ) {
            
            print '<p>Shell:';
            
            print '<select name="form_shell">';
            
            for ($i = 0; $i < count($shell); $i++) {
                print '<option value="'.$shell[$i].'" ';
                if($this->userparam[0]['shell'] == $shell[$i]) print 'selected="selected"';
                print ' >'.$shell[$i].'</option>';
            }
         
            print '</select></p>';

        
            print '<p>Codex Account Status:';

            print '<select name="form_codexstatus" id="codexstatus" onChange="autochangeStatus(this.form)">';

            print '<option value="A"';
            if ($this->userparam[0]['status'] == 'A') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','active').'</option>';
            
            print '<option value="R"';
            if ($this->userparam[0]['status'] == 'R' || $this->userparam[0]['status'] == 'W') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','restricted').'</option>';
            
            print '<option value="V"';
            if ($this->userparam[0]['status'] == 'V') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','validated').'</option>';
            
            print '<option value="P"';
            if ($this->userparam[0]['status'] == 'P') print 'selected="selected"';
            print '>Pending</option>';
            
            print '<option value="D"';
            if ($this->userparam[0]['status'] == 'D') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','deleted').'</option>'; 
            
            print '<option value="S"';
            if ($this->userparam[0]['status'] == 'S') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_userlist','suspended').'</option>';
            
            print '</select></p>';
          

            print '<p>'.$GLOBALS['Language']->getText('admin_usergroup','unix_status').':';
            
            print '<select name="form_unixstatus">';
            
            print '<option value="N"';
            if($this->userparam[0]['unix_status'] == 'N') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_usergroup','no_account').'</option>';

            print '<option value="A"';
            if($this->userparam[0]['unix_status'] == 'A') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_usergroup','active').'</option>';
        
            print '<option value="S"';
            if($this->userparam[0]['unix_status'] == 'S') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_usergroup','suspended').'</option>';
            
            print '<option value="D"';
            if($this->userparam[0]['unix_status'] == 'D') print 'selected="selected"';
            print '>'.$GLOBALS['Language']->getText('admin_usergroup','deleted').'</option>';
            
            print '</select></p>';


            print '<p>Email:';
            print '<input name="email" value="'.$this->userparam[0]['email'].'" size="35" maxlength="55" type="text"></p>';
        

            print '<p>Expiry Date:';
            
            print '<input id="expiry_date" name="expiry_date" value="" size="15" maxlength="10" type="text">';
            print '<a href="javascript:show_calendar(\'document.update_user.expiry_date\', $(\'expiry_date\').value,\'/themes/CodeXTab/css/CodeXTab_normal.css\',\'/themes/CodeXTab/images/\');"><img src="/themes/CodeXTab/images/calendar/cal.png" alt="Click Here to Pick up a date " border="0" height="16" width="16"></a></p>';
            
            
            print '<p><input name="Update_Unix" value="Update" type="submit"></p>';

            print '</form><hr>';
        }
        //select several user
        else {

            print '<p>Shell:';
            
            print '<select name="form_shell">';
            
            for ($i = 0; $i < count($shell); $i++) {
                print '<option value="'.$shell[$i].'">'.$shell[$i].'</option>';
            }

            print '</select></p>';

        
            print '<p>Codex Account Status:';

            print '<select name="form_codexstatus" id="codexstatus" onChange="autochangeStatus(this.form)">';

            print '<option value="A">'.$GLOBALS['Language']->getText('admin_userlist','active').'</option>';
            
            print '<option value="R">'.$GLOBALS['Language']->getText('admin_userlist','restricted').'</option>';
            
            print '<option value="V">'.$GLOBALS['Language']->getText('admin_userlist','validated').'</option>';
            
            print '<option value="P">Pending</option>';
            
            print '<option value="D">'.$GLOBALS['Language']->getText('admin_userlist','deleted').'</option>'; 
            
            print '<option value="S">'.$GLOBALS['Language']->getText('admin_userlist','suspended').'</option>';
            
            print '</select></p>';
          

            print '<p>'.$GLOBALS['Language']->getText('admin_usergroup','unix_status').':';
            
            print '<select name="form_unixstatus">';
            
            print '<option value="N">'.$GLOBALS['Language']->getText('admin_usergroup','no_account').'</option>';

            print '<option value="A">'.$GLOBALS['Language']->getText('admin_usergroup','active').'</option>';
        
            print '<option value="S">'.$GLOBALS['Language']->getText('admin_usergroup','suspended').'</option>';
            
            print '<option value="D">'.$GLOBALS['Language']->getText('admin_usergroup','deleted').'</option>';
            
            print '</select></p>';


            print '<p>Expiry Date:';
            
            print '<input id="expiry_date" name="expiry_date" value="" size="15" maxlength="10" type="text">';
            print '<a href="javascript:show_calendar(\'document.update_user.expiry_date\', $(\'expiry_date\').value,\'/themes/CodeXTab/css/CodeXTab_normal.css\',\'/themes/CodeXTab/images/\');"><img src="/themes/CodeXTab/images/calendar/cal.png" alt="Click Here to Pick up a date " border="0" height="16" width="16"></a></p>';
            
            
            print '<p><input name="Update_Unix" value="Update" type="submit"></p>';

            print '</form>';
        }
    }

    /**
     * displayCurrentGroups()
     *
     */
    function displayCurrentGroups() {

        //select one user
        if(isset($this->userparam['user_id']) || count($this->userparam) == 1) {

            print '<p>';
  
            print '<h3>'.$GLOBALS['Language']->getText('admin_usergroup','current_groups').'</h3>';

            print '</p>';


            print '<table  cellspacing=0 cellpadding=0 border="1">';

            print '<tr>';

            print '<td>Group Name</td>';

            print '<td>Remove from group</td>';

            print '<td>Administration Flag</td>';

            print '</tr>';


            foreach($this->groupparam as $gparam) {
            print '<tr>';

            print '<td><a href="/admin/groupedit.php?group_id='.$gparam['group_id'].'">'.$gparam['group_name'].'</a></td>';

            print '<td><a href="/project/admin/?group_id='.$gparam['group_id'].'">Remove User from group</a></td>';

            print '<td><a href="/project/admin/userperms.php?group_id='.$gparam['group_id'].'">Administration Flag</a></td>';

            print '</tr>';

            }


            print '</table>';

            print '<hr>';
        }
        //select several user => display nothing
    }


    /**
     * displayAddUser()
     *
     */
    function displayAddUser() {

        //clic on user link
        if(isset($this->userparam['user_id'])) {
            
            print '<form action="index.php" method="post">';
            
            print '<p>'.$GLOBALS['Language']->getText('admin_usergroup','add_ug').':<br />';

            print '<input type="hidden" name="task" value="add_user_to_group" />';

            print '<input type="hidden" name="user_id" value="'.$this->userparam['user_id'].'" />';
            
            print '<input name="group_id" length="4" maxlength="5" type="text"></p>';
            
            print '<p><input name="Submit" value="Submit" type="submit"></p>';

            print '<p><a href="user_changepw.php?user_id=101">['.$GLOBALS['Language']->getText('admin_usergroup','change_passwd').']</a></p>';

            print '</form>';
        }
        // select one user
        elseif(count($this->userparam) == 1 ) {

            print '<form action="index.php" method="post">';
            
            print '<p>'.$GLOBALS['Language']->getText('admin_usergroup','add_ug').':<br />';

            print '<input type="hidden" name="task" value="add_user_to_group" />';

            print '<input type="hidden" name="user_id" value="'.$this->userparam[0]['user_id'].'" />';
            
            print '<input name="group_id" length="4" maxlength="5" type="text"></p>';
            
            print '<p><input name="Submit" value="Submit" type="submit"></p>';
            
            print '<p><a href="user_changepw.php?user_id=101">['.$GLOBALS['Language']->getText('admin_usergroup','change_passwd').']</a></p>';

            print '</form>';  
        }
        //select several user => display nothing
    }

    /**
     * displayFooter()
     *
     */
    function displayFooter() {
        $GLOBALS['HTML']->footer(array());
    }


    /**
     * display()
     *
     */
    function display() {

        $this->displayHeader();

        $this->displayUnixAccountInformation();

        if ($this->task != 'remove_user_from_group') {
            $this->displayCurrentGroups();
        }

        $this->displayAddUser();

        $this->displayFooter();
    }
}
?>
