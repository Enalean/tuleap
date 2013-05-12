<?php

/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('www/project/admin/project_admin_utils.php');
require_once('common/user/UserImport.class.php');


class UserImportHtml extends UserImport {

    /**
     * Constructor.
     *
     * @return boolean success.
     */
    function UserImportHtml($group) {
        return $this->UserImport($group);
    }


    /**
     * Show the parse report
     *
     *
     */
    function displayParse($user_filename) {
        if (!file_exists($user_filename) || !is_readable($user_filename)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_userimport','missing_file'));
            $this->displayInput();
            return;
        }
        
        $errors       = array();
        $parsed_users = array();
        $ok = $this->parse($user_filename,$errors,$parsed_users);
        $this->showErrors($errors);
        
        if (!$ok) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_userimport','err_no_user_to_import'));
            $this->displayInput();
        } else {
            project_admin_header(array('title'=>$GLOBALS['Language']->getText('project_admin_userimport','import_members'),
                 'help' => 'AddingRemovingUsers.html'));
            echo '<h2>'.$GLOBALS['Language']->getText('project_admin_userimport','parse_report').'</h2>';
            $this->showParseResults($parsed_users);
            project_admin_footer(array());
        }
    }
  

    function showErrors($errors) {
        foreach($errors as $error) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('project_admin_userimport','invalid_mail_or_username',$error));
        }
    }


    /**
     * create the html output to visualize what has been parsed
     * @param $users: array containing all the users (User Object) that are in the import file
     */
    function showParseResults($parsed_users) {
        global $Language;

        echo $Language->getText('project_admin_userimport','ready')."<br><br>\n";
    
        //Display table containing the list of users to be imported
        $title_arr = array($Language->getText('project_admin_userimport','username'),$Language->getText('project_admin_userimport','mail_addr'));
        echo html_build_list_table_top ($title_arr);   
        $i = 0;
        foreach ($parsed_users as $current_user) {
            echo '<TR class="'.util_get_alt_row_color($i++).'">'."\n";
            echo '<TD>'.$current_user->getName().'</TD>'."\n";
            echo '<TD>'.$current_user->getEmail().'</TD></TR>'."\n";
        }
        echo "</TABLE>\n";
    
        // Add 'import'  button to confirm import
        echo '<FORM NAME="acceptimportdata" action="?" method="POST" enctype="multipart/form-data">
            <p align="left"><INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('project_admin_userimport','import').'"></p>
            <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$this->group_id.'">
            <INPUT TYPE="HIDDEN" NAME="func" VALUE="import">
            <INPUT TYPE="HIDDEN" NAME="mode" VALUE="import">';
        
        foreach ($parsed_users as $current_user) {
            echo '<INPUT TYPE="HIDDEN" NAME="parsed_users[]" VALUE="'.$current_user->getId().'">';
        }
    
        echo '</FORM><A href="/project/admin/userimport.php?group_id='.$this->group_id.'"> ['.$Language->getText('global','back').']</A>';
    }
  
    /**
     * Import users that has been accepted from the parse report and update DB.
     *
     * @param array $parsed_users array of users. The array has the form of $parsed_users[] -> username
     */
    function displayImport($parsed_users) {
        //use Codendi logins to add project members in DB, 
        //mail addresses are not used because it will fail in case plugin ldap is disabled/unplugged
        $this->updateDB($parsed_users);
        $GLOBALS['Response']->redirect('/project/admin/index.php?group_id='.$this->group_id);
    }
  
  
    /**
     * Display screen showing the allowed input format of the users file
     *
     *
     */
    function displayShowFormat() {
        global $Language;
    
        $this->displayInput();
        echo '<hr><h2>'.$Language->getText('project_admin_userimport','format_hdr').'</h2>';
        echo $Language->getText('project_admin_userimport','import_format',array(user_getemail(user_getid())));
    }
  
  
    /**
     * Display screen accepting the user file to be parsed  
     *     
     *     
     */
    function displayInput() {
        global $Language;
    
        project_admin_header(array('title'=>$Language->getText('project_admin_userimport','import_members'),
                 'help' => 'AddingRemovingUsers.html'));
        
        echo '<h2>'.$Language->getText('project_admin_userimport','import_members', array(help_button('AddingRemovingUsers.html'))).'</h2>';
        echo $Language->getText('project_admin_userimport','import_welcome',array('/project/admin/userimport.php?group_id='.$this->group_id.'&mode=showformat&func=import'));

        echo '<FORM NAME="importdata" action="?" method="POST" enctype="multipart/form-data">
            <INPUT TYPE="hidden" name="group_id" value="'.$this->group_id.'">                
            <INPUT TYPE="hidden" name="func" value="import">
            <INPUT TYPE="hidden" name="mode" value="parse"> 
            <TABLE border="0" width="75%">
            <TR><B>'.$Language->getText('project_admin_userimport','upload_file').'</B></TR>
            <TR><TD> <INPUT TYPE="file" name="user_filename" size="50"></TD></TR>
            </TABLE><P>
            <INPUT TYPE="submit" name="submit" value="'.$Language->getText('project_admin_userimport','submit').'">
            </FORM> ';     
        project_admin_footer(array());
    }

}

?>