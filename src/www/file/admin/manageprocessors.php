<?php
 
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
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
 */

require_once('pre.php');
require_once('www/file/file_utils.php');

$Language->loadLanguageMsg('file/file');

if (!user_isloggedin() || !user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

if (isset($mode) && $mode == "delete") {
    # delete a processor from db
    file_utils_delete_proc($proc_id);
}

file_utils_admin_header(array('title'=>$Language->getText('file_admin_manageprocessors','manage_proclist'), 'help' => 'ManageProcessorsList.html'));

if (array_key_exists('add', $_POST) && isset($_POST['add'])) {
    # add a new processor to the database
    if ($_POST['procrank'] == "")
        $feedback .= " ".$Language->getText('file_admin_manageprocessors','proc_fill',$Language->getText('file_file_utils','proc_rank'));    	  
    else if ($_POST['procname'] == "")
        $feedback .= " ".$Language->getText('file_admin_manageprocessors','proc_fill',$Language->getText('file_file_utils','proc_name'));    	      
    else if (!is_numeric($_POST['procrank']))     
        $feedback .= " ".$Language->getText('file_admin_manageprocessors','rank_error');    	
    else
        file_utils_add_proc($procname,$procrank);	
}

if (array_key_exists('update', $_POST) && isset($_POST['update'])) {
    # update a processor  
    if ($_POST['processrank'] == "")
        $feedback .= " ".$Language->getText('file_admin_manageprocessors','proc_fill',$Language->getText('file_file_utils','proc_rank'));    	  
    else if ($_POST['processname'] == "")
        $feedback .= " ".$Language->getText('file_admin_manageprocessors','proc_fill',$Language->getText('file_file_utils','proc_name'));    	      
    else if (!is_numeric($_POST['processrank']))     
        $feedback .= " ".$Language->getText('file_admin_manageprocessors','rank_error');    	
    else
        file_utils_update_proc($proc_id,$processname,$processrank);
}

$sql = "SELECT * FROM frs_processor WHERE group_id=".$group_id." OR group_id=100 ORDER BY rank";
$result = db_query($sql);

?>

<P>
<H2><?php echo $Language->getText('file_admin_manageprocessors','manage_proclist'); ?></H2>
<?php echo $Language->getText('file_admin_manageprocessors','edit_proc'); ?>
<P>
<?php

file_utils_show_processors($result);

?>

<HR>
<H3><?php echo $Language->getText('file_admin_manageprocessors','add_proc'); ?></H3>

<?php
    
$return = '<TABLE><FORM ACTION="/file/admin/manageprocessors.php?group_id='.$group_id.'" METHOD="POST">    
    <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
    <TR><TD>'.$Language->getText('file_file_utils','proc_name').': <font color=red>*</font> </TD>
    <TD><INPUT TYPE="TEXT" NAME="procname" VALUE="" SIZE=30></TD></TR>
    <TR><TD>'.$Language->getText('file_file_utils','proc_rank').': <font color=red>*</font> </TD>
    <TD><INPUT TYPE="TEXT" NAME="procrank" VALUE="" SIZE=10></TD></TR></TABLE>    
    <p><INPUT TYPE="SUBMIT" NAME="add" VALUE="'. $Language->getText('file_file_utils','add_proc').'"></p></FORM>
    <p><font color="red">*</font>: '.$Language->getText('file_file_utils','required_fields').'</p>';
    
echo $return;

file_utils_footer(array());
 
 ?>