<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($submit) {
	if ($add_image) {
		//see if they have too many images in the system
		$res=db_query("SELECT sum(filesize) WHERE group_id='$group_id'");
		if (db_result($res,0,'sum') < 1048576) {
			$dimensions = @getimagesize($input_file);
			$size = @filesize($input_file);
			$data = addslashes(fread( fopen($input_file, 'r'), filesize($input_file)));
			$width=$dimensions[0];
			$height=$dimensions[1];
			//$input_file_name
			//$input_file_type
			if (($size > 0) && ($size < $sys_max_size_upload)) {
				//size is fine
				$feedback .= ' Document Uploaded ';
				$res=db_query("INSERT INTO db_images (group_id,description,bin_data,filename,filesize,filetype,width,height) VALUES ".
				"('$group_id','$description','$data','$input_file_name','$size','$input_file_type','$width','$height')");
				echo db_error();
			} else {
				//too big or small
				$feedback .= ' ERROR - image must be > 0 bytes and < '.$sys_max_size_upload'. bytes in length ';
			}
		} else {
			$feedback .= ' Sorry - you are over your 1MB image quota ';
		}
	} else if ($remove_image) {
		$res=db_query("DELETE FROM db_images WHERE id='$id' AND group_id='$group_id'");
		echo db_error();
		$feedback .= ' Image Deleted ';
	}
}

project_admin_header(array('title'=>'Edit Your Images'));

echo '<H3>Edit Your Project\'s Images</H3>
	<P>
	You can store up to 1MB of images in our database. Use this page to add/delete your project images.
	<P>
	<H4>Add Image</H4>
	<P>
	<FORM ACTION="'. $PHP_SELF .'" METHOD="POST" enctype="multipart/form-data">
    <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_upload.'">
	<input type="hidden" name="group_id" VALUE="'.$group_id.'">
	<input type="file" name="input_file" size="30">
    <br><span class="smaller"><i>(The maximum upload file size is '.formatByteToMb($sys_max_size_upload).' Mb)</i></span>
	<P>
	<B>Description:</B><BR>
	<input type="text" name="description" size="40" maxlength="255"><P>
	<input type="hidden" name="add_image" VALUE="1">
	<input type="submit" value="Add Image" NAME="submit"><BR>
	</form>
';

$result=db_query("SELECT * FROM db_images WHERE group_id='$group_id'");

$arr=array();
$arr[]='Delete';
$arr[]='ID';
$arr[]='Name';

echo html_build_list_table_top($arr);

$rows=db_numrows($result);
for ($i=0; $i<$rows; $i++) {
	echo '	  
	<TR class="'. html_get_alt_row_color($i) .'"><TD ALIGN="center">'.
	'<A HREF="'. $PHP_SELF .'?submit=1&group_id='.$group_id.'&remove_image=1&id='.
	db_result($result,$i,'id').
	'"><IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" '.
	'BORDER=0"></A></TD><TD>'.
	db_result($result,$i,'id').'</TD><TD>'.
	stripslashes(db_result($result,$i,'filename')).'</TD></TR>';
}
echo '</TABLE>';

project_admin_footer(array());

?>
