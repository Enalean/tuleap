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

require('./doc_utils.php');
require($DOCUMENT_ROOT.'/include/pre.php');

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
                    // Check if there is a link in the title
                    if (!strstr($title,"href")) {
                        exit_missing_param();
                    }
		}

		if (!user_isloggedin()) {
			$user=100;
		} else {
			$user=user_getid();
		}

		if ($upload_instead) {
	        $data = addslashes(fread( fopen($uploaded_data, 'r'), filesize($uploaded_data)));
    		if ((strlen($data) <= 0 ) || (strlen($data) >= $sys_max_size_upload)) {
        		//too big or small
        		$feedback .= ' ERROR - document must be non null and < '.$sys_max_size_upload.' chars in length ';
        		exit_error('Missing Info',$feedback.' - Please click back and fix the error.');
    		}
		}

		docman_header(array('title'=>'New Document Submitted',
				    'help'=>'DocumentSubmission.html'));
		
		if ($upload_instead) {
		    // Upload file
    		$query = "insert into doc_data(stateid,title,data,createdate,updatedate,created_by,doc_group,description,restricted_access,filename,filesize,filetype) "
    		."values('3',"
    		// state = 3 == pending
    		."'".htmlspecialchars($title)."',"
    		."'".$data."',"
    		."'".time()."',"
    		."'".time()."',"
    		."'".$user."',"
    		."'".$doc_group."',"			
    		."'".htmlspecialchars($description)."',"		
    		."'0',"
    		."'".$uploaded_data_name."',"
    		."'".$uploaded_data_size."',"
    		."'".$uploaded_data_type."')";
		} else {
		    // Copy/paste data
    		$query = "insert into doc_data(stateid,title,data,createdate,updatedate,created_by,doc_group,description,restricted_access,filename,filesize,filetype) "
    		."values('3',"
    		// state = 3 == pending
    		."'".htmlspecialchars($title)."',"
    		."'".htmlspecialchars($data)."',"
    		."'".time()."',"
    		."'".time()."',"
    		."'".$user."',"
    		."'".$doc_group."',"			
    		."'".htmlspecialchars($description)."',"		
    		."'0','',0,'text/html')";
    	}
	
		$res_insert = db_query($query); 
	    if (db_affected_rows($res_insert) < 1) {
           echo '<p>An error occurs:</p><h3><span class="feedback">'. db_error() .'</span></h3>';
	    } else {
    	   print "<p><b>Thank You!  Your submission has been placed in the database for review before posting.</b> \n\n<p>\n <a href=\"/docman/index.php?group_id=".$group_id."\">Back</a>"; 
        }
        
		docman_footer($params);

	} else {
		docman_header(array('title'=>'Add document',
				    'help'=>'DocumentSubmission.html'));
		echo '<h2>Submit New Document</h2>';
		if ($user == 100) {
  			print "<p>You are not logged in, and will not be given credit for this.<p>";
		}

		echo '
			<form name="adddata" action="new.php?mode=add&group_id='.$group_id.'" method="POST" enctype="multipart/form-data">
            <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_upload.'">

			<table border="0" width="75%">

			<tr>
			<th>Document Title:</th>
			<td><input type="text" name="title" size="60" maxlength="255"></td>

			</tr>
			<tr>
			<th>Description: <br> (HTML tag ok)</th> 
			<!-- LJ td><input type="text" name="description" size="50" maxlength="255"></td -->
			<td><textarea cols="60" rows="4"  wrap="virtual" name="description"></textarea></td>			</tr>

			<tr>
			<th> <input type="checkbox" name="upload_instead" value="1"> <B>Upload File:</B></th>
			<td> <input type="file" name="uploaded_data" size="50">
                 <br><span class="smaller"><i>(The maximum upload file size is '.formatByteToMb($sys_max_size_upload).' Mb)</i></span>
			</td>
			</tr>

			<tr>
			<th>OR Paste Document (in HTML format):</th>
			<td><textarea cols="60" rows="10" name="data"></textarea></td>
			</tr>

			<tr>
			<th>Group that document belongs in:</th>
			<td>';

		display_groups_option($group_id);

		echo '	</td> </tr>
		
    	    </table>

			<input type="submit" value="Submit Information">

			</form> '; 
	
		docman_footer($params);
	} // end else.

} else {
	exit_no_group();
}

?>
