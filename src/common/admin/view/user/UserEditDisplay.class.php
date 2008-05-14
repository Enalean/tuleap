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

    function __construct() {

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
    }


    /**
     * displayUnixAccountInformation()
     *
     */
    function displayUnixAccountInformation() {

        print $GLOBALS['Language']->getText('admin_usergroup','account_info');

        print '<form method="post" name="update_user" action="index.php">';
        
        print '<p>Shell:';

        print '<select name="form_shell">';
        print '<option value="/bin/sh">/bin/sh</option>';
        print '<option selected="selected" value="/bin/bash">/bin/bash</option>';
        print '<option value="/sbin/nologin">/sbin/nologin</option>';
        print '<option value="/bin/bash2">/bin/bash2</option>';
        print '<option value="/bin/ash">/bin/ash</option>';
        print '<option value="/bin/bsh">/bin/bsh</option>';
        print '<option value="/bin/ksh">/bin/ksh</option>';
        print '<option value="/bin/tcsh">/bin/tcsh</option>';
        print '<option value="/bin/csh">/bin/csh</option>';
        print '<option value="/bin/zsh">/bin/zsh</option>';
        print '</select>';

        print '</p><p>Unix Account Status:';
        print '<select name="form_unixstatus">';
        print '<option value="N">No Unix Account</option>';
        print '<option selected="selected" value="A">Active</option>';
        print '<option value="S">Suspended</option>';
        print '<option value="D">Deleted</option>';
        print '</select>';

        print '</p><p>Email:';
        print '<input name="email" value="codex-admin@_DOMAIN_NAME_" size="35" maxlength="55" type="text">';

        print '</p><p>Expiry Date:';

        print '<input id="expiry_date" name="expiry_date" value="" size="15" maxlength="10" type="text">';
        print '<a href="javascript:show_calendar(\'document.update_user.expiry_date\', $(\'expiry_date\').value,\'/themes/CodeXTab/css/CodeXTab_normal.css\',\'/themes/CodeXTab/images/\');"><img src="/themes/CodeXTab/images/calendar/cal.png" alt="Click Here to Pick up a date " border="0" height="16" width="16"></a>';

        print '</p><p>';

        print '<input name="Update_Unix" value="Update" type="submit">';

        print '</p><hr>';

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

        $this->displayFooter();
    }
}
?>
