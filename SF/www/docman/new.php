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

require_once('pre.php');
require('./doc_utils.php');

$Language->loadLanguageMsg('docman/docman');

if (!(user_ismember($group_id,"D1"))) {
    $feedback .= $Language->getText('docman_admin_index','error_perm');
    exit_permission_denied();
}

if($group_id) {

    if ($mode == "add"){

        if (!$doc_group || $doc_group ==100) {
            //cannot add a doc unless an appropriate group is provided
            exit_error($Language->getText('global','error'),
                       $Language->getText('docman_new','error_noproj'));
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
                exit_error($Language->getText('global','error'),
                           $Language->getText('docman_new','error_size',array($sys_max_size_upload)));
            }
        }
        		
        if ($upload_instead) {
            // Upload file
            $query = "insert into doc_data(title,data,createdate,updatedate,created_by,doc_group,description,filename,filesize,filetype) "
    		."values("
    		."'".htmlspecialchars($title)."',"
    		."'".$data."',"
    		."'".time()."',"
    		."'".time()."',"
    		."'".$user."',"
    		."'".$doc_group."',"
    		."'".htmlspecialchars($description)."',"
    		."'".$uploaded_data_name."',"
    		."'".$uploaded_data_size."',"
    		."'".$uploaded_data_type."')";
        } else {
            // Copy/paste data
            $query = "insert into doc_data(title,data,createdate,updatedate,created_by,doc_group,description,filename,filesize,filetype) "
    		."values("
    		."'".htmlspecialchars($title)."',"
    		."'".htmlspecialchars($data)."',"
    		."'".time()."',"
    		."'".time()."',"
    		."'".$user."',"
    		."'".$doc_group."',"
    		."'".htmlspecialchars($description)."',"
    		."'',0,'text/html')";
        }
	
        $res_insert = db_query($query); 
        if (db_affected_rows($res_insert) < 1) {
            docman_header(array('title'=>$Language->getText('docman_new','title_new'),
                                'help'=>'DocumentSubmission.html'));
            echo '<p>'.$Language->getText('docman_new','error_dbinsert').':</p><h3><span class="feedback">'. db_error() .'</span></h3>';
            docman_footer($params);
        } else {
            $feedback .= $Language->getText('docman_new','insert_ok');
            session_redirect("/docman/?group_id=$group_id&feedback=$feedback");
        }
                
    } else {
        docman_header(array('title'=>$Language->getText('docman_new','title_add'),
                            'help'=>'DocumentSubmission.html'));

        echo '<h2>'.$Language->getText('docman_new','header_add').'</h2>';
        if ($user == 100) {
            print "<p>".$Language->getText('docman_new','not_logged')."<p>";
        }
        if (!groups_defined($group_id)) {
            echo "<p>".$Language->getText('docman_new','no_docgroup',array("/docman/admin/index.php?group_id=".$group_id))."<p>";
        }
        
        $star = '&nbsp;<span class="highlight"><big>*</big></span>';
        echo '
			<form name="adddata" action="new.php?mode=add&group_id='.$group_id.'" method="POST" enctype="multipart/form-data">
            <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_upload.'">

			<table border="0" width="75%">

			<tr>
			<th>'.$Language->getText('docman_new','doc_title').':'.$star.'</th>
			<td><input type="text" name="title" size="60" maxlength="255"></td>

			</tr>
			<tr>
			<th>'.$Language->getText('docman_new','doc_desc').$star.'</th> 
			<td><textarea cols="60" rows="4"  wrap="virtual" name="description"></textarea></td>			</tr>

			<tr>
			<th> <input type="checkbox" name="upload_instead" value="1"> <B>'.$Language->getText('docman_new','doc_upload').':</B></th>
			<td> <input type="file" name="uploaded_data" size="50">
                 <br><span class="smaller"><i>'.$Language->getText('docman_new','max_size_msg',array(formatByteToMb($sys_max_size_upload))).'</i></span>
			</td>
			</tr>

			<tr>
			<th>'.$Language->getText('docman_new','doc_paste').':</th>
			<td><textarea cols="60" rows="10" name="data"></textarea></td>
			</tr>

			<tr>
			<th>'.$Language->getText('docman_new','doc_group').':'.$star.'</th>
			<td>';

        display_groups_option($group_id);

        echo '	</td> </tr>
		
    	    </table>
<p>';
        print $Language->getText("docman_new", "mandatory", array($star));
        echo '</p>
			<input type="submit" value="'.$Language->getText('global','btn_submit').'">
	    </form> '; 
	
        docman_footer($params);
    }

} else {
	exit_no_group();
}

?>
