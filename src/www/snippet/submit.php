<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// Copyright (c) Enalean, 2015. All Rights Reserved.
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../snippet/snippet_utils.php');

$request = HTTPRequest::instance();

if (user_isloggedin()) {
    $uploaded_data = $_FILES['uploaded_data'];

    $temporary_uploaded_file_name = $uploaded_data['tmp_name'];
    $uploaded_data_name           = $uploaded_data['name'];
    $uploaded_data_size           = $uploaded_data['size'];
    $uploaded_data_type           = $uploaded_data['type'];
    // check if the code snippet is uploaded
    if ($temporary_uploaded_file_name) {
	$code = addslashes(file_get_contents($temporary_uploaded_file_name));
	if ((strlen($code) > 0) && (strlen($code) < $sys_max_size_upload)) {
	    //size is fine
	    $feedback .= ' '.$Language->getText('snippet_addversion','s_uploaded').' ';
	} else {
	    //too big or small
	    $feedback .= ' '.$Language->getText('snippet_addversion','min_max_length',$sys_max_size_upload).' ';
	    $code='';
	}
    }

    $post_changes = $request->get('post_changes');
    if ($post_changes) {
	/*
	  Create a new snippet entry, then create a new snippet version entry
	*/
        $csrf->check();

        $name               = $request->get('name');
        $description        = $request->get('description');
        $language           = $request->get('language');
        $category           = $request->get('category');
        $type               = $request->get('type');
        $version            = $request->get('version');
        $code               = isset($code) ? $code : $request->get('code');
        $license            = $request->get('license');
        $changes            = $request->get('changes');

        if ($name && $description && $language != 0 && $category != 0 && $type != 0 && 
            $language != 100 && $category != 100 && $type != 100 && $license != 100 && $version && $code) {
                $category = (int)$category;
                $type     = (int)$type;
                $language = (int)$language;
                $license  = (int)$license;
                $sql="INSERT INTO snippet (category,created_by,name,description,type,language,license) ".
                    "VALUES ('". db_ei($category) ."','". db_ei(user_getid()) ."','". db_es(htmlspecialchars($name)) ."','".
                    htmlspecialchars($description)."','$type','$language','$license')";
                $result=db_query($sql);
                if (!$result) {
                    $feedback .= ' '.$Language->getText('snippet_submit','s_insert_fail').' ';
                    echo db_error();
                } else {
                    $feedback .= ' '.$Language->getText('snippet_submit','s_add_success').' ';
                    $snippet_id=db_insertid($result);
                    /*
		     create the snippet version
                    */
                    $sql="INSERT INTO snippet_version (snippet_id,changes,version,submitted_by,date,code,filename,filesize,filetype) ".
		    "VALUES ('". db_ei($snippet_id) ."','". db_es(htmlspecialchars($changes)) ."','".
                        db_es(htmlspecialchars($version)) ."','". db_ei(user_getid()) ."','".
                        time()."','".
                        ($uploaded_data ? db_es($code) : db_es(htmlspecialchars($code)))."',".
                        "'". db_es($uploaded_data_name) ."','". db_es($uploaded_data_size) ."','". db_es($uploaded_data_type) ."')";
                    $result=db_query($sql);
                    if (!$result) {
                        $feedback .= ' '.$Language->getText('snippet_add_snippet_to_package','error_insert').' ';
                        echo db_error();
                    } else {
                        $feedback .= ' '.$Language->getText('snippet_add_snippet_to_package','add_success').' ';
                    }
                }
	} else {
            if ($license==100) {
                // No license!
		$feedback .= ' '.$Language->getText('snippet_details','select_license').' ';
            } else if ($category==100) {
		$feedback .= ' '.$Language->getText('snippet_details','select_category').' ';
            } else if ($type==100) {
		$feedback .= ' '.$Language->getText('snippet_details','select_type').' ';
            } else if ($language==100) {
		$feedback .= ' '.$Language->getText('snippet_details','select_lang').' ';
            }
	    exit_error($Language->getText('global','error'),$Language->getText('snippet_add_snippet_to_package','error_fill_all_info'));
	}
	
    }
	snippet_header(array('title'=>$Language->getText('snippet_add_snippet_to_package','submit_snippet'),
			     'header'=>$Language->getText('snippet_submit','submit_s'),
			     'help' => 'overview.html#code-snippet-submission'));

	echo '
	<P>
	'.$Language->getText('snippet_submit','post_s').'
	<P>
	<span class="highlight"><B>'.$Language->getText('snippet_submit','note').'</B></span>'.$Language->getText('snippet_submit','submit_s_v').'
	<P>
	<FORM ACTION="?" METHOD="POST" enctype="multipart/form-data" class="add-snippet">'.
        $csrf->fetchHTMLInput() .'
    <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_upload.'">
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<INPUT TYPE="HIDDEN" NAME="changes" VALUE="'.$Language->getText('snippet_package','first_posted_v').'">

	<TABLE>

	<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_browse','title').'</B>&nbsp;
		<INPUT TYPE="TEXT" NAME="name" SIZE="45" MAXLENGTH="60">
	</TD></TR>

	<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_package','description').'</B><BR>
		<TEXTAREA NAME="description" ROWS="5" COLS="45" WRAP="SOFT"></TEXTAREA>
	</TD></TR>

	<TR>
	<TD><B>'.$Language->getText('snippet_utils','type').'</B>&nbsp;
		'.html_build_select_box(snippet_data_get_all_types() ,'type').'
	</TD>

	<TD><B>'.$Language->getText('snippet_utils','license').'</B>&nbsp;
		'.html_build_select_box(snippet_data_get_all_licenses() ,'license',"1",false).'
	</TD>
	</TR>

	<TR>
	<TD><B>'.$Language->getText('snippet_package','language').'</B>&nbsp;
		'.html_build_select_box (snippet_data_get_all_languages(),'language').'
	</TD>

	<TD><B>'.$Language->getText('snippet_package','category').'</B>&nbsp;
		'.html_build_select_box (snippet_data_get_all_categories(),'category').'
	</TD>
	</TR>
 
	<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_addversion','version').'</B>&nbsp;
		<INPUT TYPE="TEXT" NAME="version" SIZE="10" MAXLENGTH="15">
	</TD></TR>
  
	<TR><TD COLSPAN="2">
	 <B>'.$Language->getText('snippet_addversion','upload_s').'</B><br>
		<input type="file" name="uploaded_data" size="40">
        <br><span class="smaller"><i>'.$Language->getText('snippet_addversion','max_size',formatByteToMb($sys_max_size_upload)).'</i></span>
		<br><br><P>
                <B>'.$Language->getText('snippet_addversion','paste_code').'</B><BR>
		<TEXTAREA NAME="code" ROWS="20" COLS="85" WRAP="SOFT"></TEXTAREA>
	</TD></TR>
 
	<TR><TD COLSPAN="2">
		<B>'.$Language->getText('snippet_add_snippet_to_package','all_info_complete').'</B>
		<BR><BR>
		<INPUT CLASS="btn btn-primary" TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
	</TD></TR>
	</FORM>
	</TABLE>';
	
	snippet_footer(array());

} else {
    exit_not_logged_in();
}
