<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

/*
	Docmentation Manager
	by Quentin Cregan, SourceForge 06/2000
*/


require('doc_utils.php');
require('pre.php');

if($group_id) {

	if ($mode == "add"){

		if (!$doc_group || $doc_group ==100) {
			//cannot add a doc unless an appropriate group is provided
			exit_error('Error','No Valid Document Group Was Selected');
		}

		if (!$title || !$description) { 
			exit_missing_param();
		}

		if (!$upload_instead && !$data) {
			exit_missing_param();
		}

		if (!user_isloggedin()) {
			$user=100;
		} else {
			$user=user_getid();
		}

		if ($upload_instead) {
		        $data = addslashes(fread( fopen($uploaded_data, 'r'), filesize($uploaded_data)));
        		if ((strlen($data) > 20) && (strlen($data) < 512000)) {
                		//size is fine
                		$feedback .= ' Document Uploaded ';
        		} else {
                		//too big or small
                		$feedback .= ' ERROR - patch must be > 20 chars and < 512000 chars in length ';
                		exit_error('Missing Info',$feedback.' - Please click back and fix the error.');
        		}
		}

		

		docman_header('Documentation - Add Information - Processing','Documentation - New submission');
		
		$query = "insert into doc_data(stateid,title,data,createdate,updatedate,created_by,doc_group,description) "
		."values('3',"
		// state = 3 == pending
		."'".htmlspecialchars($title)."',"
		."'".htmlspecialchars($data)."',"
		."'".time()."',"
		."'".time()."',"
		."'".$user."',"
		."'".$doc_group."',"			
		."'".htmlspecialchars($description)."')";
	
		db_query($query); 
		//PROBLEM check the query

		print "<p><b>Thank You!  Your submission has been placed in the database for review before posting.</b> \n\n<p>\n <a href=\"/docman/index.php?group_id=".$group_id."\">Back</a>"; 
		docman_footer($params);
	} else {
		docman_header('Add documentation','Add documentation');
		if ($user == 100) {
  			print "<p>You are not logged in, and will not be given credit for this.<p>";
		}

		echo '
			<p>

			<b> Document Title: </b> Refers to the relatively brief title of the document (e.g. How to use the download server)
			<br>
			<b> Description: </b> A brief description to be placed just under the title.<br>

			<form name="adddata" action="new.php?mode=add&group_id='.$group_id.'" method="POST" enctype="multipart/form-data">

			<table border="0" width="75%">

			<tr>
			<th>Document Title:</th>
			<td><input type="text" name="title" size="40" maxlength="255"></td>

			</tr>
			<tr>
			<th>Description:</th> 
			<td><input type="text" name="description" size="50" maxlength="255"></td>
			</tr>

			<tr>
			<th> <input type="checkbox" name="upload_instead" value="1"> <B>Upload Text File:</B></th>
			<td> <input type="file" name="uploaded_data" size="30"></td>
			</tr>

			<tr>
			<th>OR Paste Document (in html format):</th>
			<td><textarea cols="60" rows="10" name="data"></textarea></td>
			</tr>

			<tr>
			<th>Group that document belongs in:</th>
			<td>';

		display_groups_option($group_id);

		echo '	</td> </tr> </table>

			<input type="submit" value="Submit Information">

			</form> '; 
	
		docman_footer($params);
	} // end else.

} else {
	exit_no_group();
}

?>
