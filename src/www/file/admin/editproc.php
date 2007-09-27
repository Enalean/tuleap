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

file_utils_admin_header(array('title'=>$Language->getText('file_admin_manageprocessors','update_proc'), 'help' => 'ManageProcessorsList.html'));

$sql = "SELECT name,rank FROM frs_processor WHERE group_id=".$group_id." AND processor_id=".$proc_id;
$result = db_query($sql);
$name = db_result($result,0,'name');
$rank = db_result($result,0,'rank');

if (db_numrows($result) < 1) {
    #invalid  processor  id  
    $feedback .= " ".$Language->getText('file_admin_manageprocessors','invalid_procid');
    file_utils_footer(array());
    exit;
}     	

?>

<P>
<H2><?php echo $Language->getText('file_admin_manageprocessors','update_proc'); ?></H2>

<?php
    
$return = '<TABLE><FORM ACTION="/file/admin/manageprocessors.php?group_id='.$group_id.'" METHOD="POST">    
    <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
    <INPUT TYPE="HIDDEN" NAME="proc_id" VALUE="'.$proc_id.'">
    <TR><TD>'.$Language->getText('file_file_utils','proc_name').': <font color=red>*</font> </TD>
    <TD><INPUT TYPE="TEXT" NAME="processname" VALUE="'.$name.'" SIZE=30></TD></TR>
    <TR><TD>'.$Language->getText('file_file_utils','proc_rank').': <font color=red>*</font> </TD>
    <TD><INPUT TYPE="TEXT" NAME="processrank" VALUE="'.$rank.'" SIZE=10></TD></TR></TABLE>    
    <p><INPUT TYPE="SUBMIT" NAME="update" VALUE="'.$Language->getText('file_file_utils','update_proc').'"></p></FORM>
    <p><font color="red">*</font>: '.$Language->getText('file_file_utils','required_fields').'</p>';
   
echo $return;
    

file_utils_footer(array());
 
?>