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
     * @type array $userparam
     */
    private $userparam;

    /**
     * $useradminflag
     *
     * @type string $useradminflag
     */

    function __construct($userparam, $useradminflag) {
 
       $this->userparam = $userparam;
       $this->useradminflag = $useradminflag;
    }


    /**
     * displayHeader()
     *
     */
    function displayHeader() {
        $GLOBALS['Language']->loadLanguageMsg('admin/admin');
        
        session_require(array('group'=>'1','admin_flas'=>'A'));
        
        $GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('admin_usergroup','title')));
        parent::displayHeader($GLOBALS['Language']->getText('admin_usergroup','header').': ');
        ?>
            <script type="text/javascript" src="/scripts/autoselectlist.js"></script>
                 
                 <?php
                 }


    /**
     * displayUnixAccountInformation()
     *
     */
    function displayUnixAccountInformation() {

        print '<h1><b>'.$GLOBALS['Language']->getText('admin_usergroup','account_info').'</b></h1>';
        
        print '<form method="post" name="update_user" action="index.php">';
        
        print '<p>Shell:';

        print '<select name="form_shell">';
        
        print '<option value="/bin/sh"';
        if($this->userparam['shell'] == '/bin/sh') print 'selected="selected"';
        print ' >/bin/sh</option>';
        
        print '<option value="/bin/bash"';
        if($this->userparam['shell'] == '/bin/bash') print 'selected="selected"';
        print '>/bin/bash</option>';

        print '<option value="/sbin/nologin"';
        if($this->userparam['shell'] == '/sbin/nologin') print 'selected="selected"';
        print '>/sbin/nologin</option>';
    
        print '<option value="/bin/bash2"';
        if($this->userparam['shell'] == '/bin/bash2') print 'selected="selected"';
        print '>/bin/bash2</option>';

        print '<option value="/bin/ash"';
        if($this->userparam['shell'] == '/bin/ash') print 'selected="selected"';
        print '>/bin/ash</option>';

        print '<option value="/bin/bsh"';
        if($this->userparam['shell'] == '/bin/bsh') print 'selected="selected"';
        print '>/bin/bsh</option>';

        print '<option value="/bin/ksh"';
        if($this->userparam['shell'] == '/bin/ksh') print 'selected="selected"';
        print '>/bin/ksh</option>';

        print '<option value="/bin/tcsh"';
        if($this->userparam['shell'] == '/bin/tcsh') print 'selected="selected"';
        print '>/bin/tcsh</option>';

        print '<option value="/bin/csh"';
        if($this->userparam['shell'] == '/bin/csh') print 'selected="selected"';
        print '>/bin/csh</option>';

        print '<option value="/bin/zsh"';
        if($this->userparam['shell'] == '/bin/zsh') print 'selected="selected"';
        print '>/bin/zsh</option>';

        print '</select>';

        print '</p>';


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
        
        print '</select>';
        
        print '</p><p>Email:';
        print '<input name="email" value="'.$this->userparam['email'].'" size="35" maxlength="55" type="text">';
        
        print '</p><p>Expiry Date:';

        print '<input id="expiry_date" name="expiry_date" value="" size="15" maxlength="10" type="text">';
        print '<a href="javascript:show_calendar(\'document.update_user.expiry_date\', $(\'expiry_date\').value,\'/themes/CodeXTab/css/CodeXTab_normal.css\',\'/themes/CodeXTab/images/\');"><img src="/themes/CodeXTab/images/calendar/cal.png" alt="Click Here to Pick up a date " border="0" height="16" width="16"></a>';
        
        print '</p><p>';
        
        print '<input name="Update_Unix" value="Update" type="submit">';
        
        print '</p><hr>';
        
    }


    /**
     * displayCurrentGroups()
     *
     */
    function displayCurrentGroups() {

        print '<p>';
  
        print $GLOBALS['Language']->getText('admin_usergroup','current_groups');

        print '</p>';

        print '<a href="groupedit.php?group_id=1"><b>CodeX Administration Project</b></a>&nbsp;&nbsp;&nbsp;';
        print '<a href="usergroup.php?user_id=101&amp;action=remove_user_from_group&amp;group_id=1">['.$GLOBALS['Language']->gettext('admin_usergroup','remove_ug').']</a><br />';

        print '<form action="index.php" method="post">';

        print '<input type="checkbox" name="adminflag" value="A"';
        if ($this->useradminflag['admin_flags']) print 'checked="checked"';
        print ' />';

        print $GLOBALS['Language']->getText('admin_usergroup','admin_flags').':<br />';


        print '<input name="Update_Group" value="Update" type="submit">';
        print '</form>';

		print '<hr>';

    }


    /**
     * displayAddUser()
     *
     */
    function displayAddUser() {


        print '<form action="index.php" method="post">';

        print '<p>'.$GLOBALS['Language']->getText('admin_usergroup','add_ug').':<br />';

        print '<input name="group_id" length="4" maxlength="5" type="text">';

        print '</p><p>';

        print '<input name="Submit" value="Submit" type="submit">';

        print '</p><p>';

        print '<a href="user_changepw.php?user_id=101">['.$GLOBALS['Language']->getText('admin_usergroup','change_passwd').']</a>';

        print '</p></form>';

    }

    /**
     * displayFooter()
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

        $this->displayCurrentGroups();

        $this->displayAddUser();

        $this->displayFooter();
    }
}
?>
