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


if (user_isloggedin()) {

  $id           = (int)$request->get('id');
  $type         = $request->get('type');
  $post_changes = $request->get('post_changes');
  $changes      = $request->get('changes');

  if ($type=='snippet') {
    /*
			See if the snippet exists first
    */
    $result=db_query("SELECT * FROM snippet WHERE snippet_id='$id'");
    if (!$result || db_numrows($result) < 1) {
      exit_error($Language->getText('global','error'),$Language->getText('snippet_add_snippet_to_package','error_s_not_exist'));
    }
    
    /*
			handle inserting a new version of a snippet
    */
    if ($post_changes) {
      
      // check if the code snippet is uploaded

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
      
      /*
		      Create a new snippet entry, then create a new snippet version entry
      */

        $version = $request->get('version');
        $code    = isset($code) ? $code : $request->get('code');

		if ($changes && $version && $code) {
			$csrf->check();
			$snippet_id = (int)$request->get('snippet_id');
			/*
                      create the snippet version
            */
			$sql = "INSERT INTO snippet_version (snippet_id,changes,version,submitted_by,date,code,filename,filesize,filetype) " .
				"VALUES ('" . db_ei($snippet_id) . "','" . db_es(htmlspecialchars($changes)) . "','" .
				db_es(htmlspecialchars($version)) . "','" . db_ei(user_getid()) . "','" .
				time() . "','" .
				($uploaded_data ? db_es($code) : db_es(htmlspecialchars($code))) . "'," .
				"'" . db_es($uploaded_data_name) . "','" . db_es($uploaded_data_size) . "','" . db_es($uploaded_data_type) . "')";

			$result = db_query($sql);

			if (!$result) {
				$feedback .= ' ' . $Language->getText('snippet_add_snippet_to_package', 'error_insert') . ' ';
				echo db_error();
			} else {
				$feedback .= ' ' . $Language->getText('snippet_add_snippet_to_package', 'add_success') . ' ';
			}
		} else {
	exit_error($Language->getText('global','error'),$Language->getText('snippet_add_snippet_to_package','error_fill_all_info'));
      }
      
    }
    snippet_header(array('title'=>$Language->getText('snippet_addversion','submit_s')));
    
    echo $Language->getText('snippet_addversion','post_s').'
		<P>
		<FORM ACTION="?" METHOD="POST" enctype="multipart/form-data">'.
                $csrf->fetchHTMLInput() .'
        <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_upload.'">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="type" VALUE="snippet">
		<INPUT TYPE="HIDDEN" NAME="snippet_id" VALUE="'.$id.'">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="'.$id.'">

		<TABLE>
		<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_addversion','version').'</B>&nbsp;
			<INPUT TYPE="TEXT" NAME="version" SIZE="10" MAXLENGTH="15">
		</TD></TR>

		<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_addversion','changes').'</B><BR>
			<TEXTAREA NAME="changes" ROWS="5" COLS="45"></TEXTAREA>
		</TD></TR>
  
		<TR><TD COLSPAN="2">
                <br><B>'.$Language->getText('snippet_addversion','upload_s').'</B> 
		<P>
	        <input type="file" name="uploaded_data"  size="40">
        <br><span class="smaller"><i>'.$Language->getText('snippet_addversion','max_size',formatByteToMb($sys_max_size_upload)).'</i></span>
	        <P>
		 <B>'.$Language->getText('snippet_addversion','paste_code').'</B><BR>
	        	<TEXTAREA NAME="code" ROWS="30" COLS="85" WRAP="SOFT"></TEXTAREA>
	        </TD></TR>
 
	        <TR><TD COLSPAN="2">
			<B>'.$Language->getText('snippet_add_snippet_to_package','all_info_complete').'</B>
			<BR>
			<INPUT CLASS="btn btn-primary" TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
	        </TD></TR>
	        </FORM>
	        </TABLE>';
						      
						      
    snippet_footer(array());
    
  } else if ($type=='package') {
    /*
			Handle insertion of a new package version
    */
    
    /*
			See if the package exists first
    */
    $result=db_query("SELECT * FROM snippet_package WHERE snippet_package_id='". db_ei($id) ."'");
    if (!$result || db_numrows($result) < 1) {
      exit_error($Language->getText('global','error'),$Language->getText('snippet_addversion','s_p_not_exist'));
    }

    if ($post_changes) {
        $snippet_package_id = (int)$snippet_package_id;
      /*
				Create a new snippet entry, then create a new snippet version entry
      */
      if ($changes && $snippet_package_id) {
	/*
					create the snippet package version
	*/
	$sql="INSERT INTO snippet_package_version ".
	  "(snippet_package_id,changes,version,submitted_by,date) ".
	  "VALUES ('". db_ei($snippet_package_id) ."','". db_es(htmlspecialchars($changes)) ."','".
	  db_es(htmlspecialchars($version)) ."','". db_ei(user_getid()) ."','".time()."')";
	$result=db_query($sql);
	if (!$result) {
	  //error in database
	  $feedback .= ' '.$Language->getText('snippet_addversion','error_insert').' ';
	  snippet_header(array('title'=>$Language->getText('snippet_addversion','submit_p')));
	  echo db_error();
	  snippet_footer(array());
	  exit;
	} else {
	  //so far so good - now add snippets to the package
	  $feedback .= ' '.$Language->getText('snippet_addversion','p_add_success').' ';
	  
	  //id for this snippet_package_version
	  $snippet_package_version_id=db_insertid($result);
	  snippet_header(array('title'=>$Language->getText('snippet_addversion','add')));
	  
	  /*
	This allows the user to add snippets to the package
	  */
	  
	  
	  echo '
<SCRIPT LANGUAGE="JavaScript">
<!--
function show_add_snippet_box() {
	newWindow = open("","occursDialog","height=500,width=300,scrollbars=yes,resizable=yes");
	newWindow.location=(\'/snippet/add_snippet_to_package.php?snippet_package_version_id='.$snippet_package_version_id.'\');
}
// -->
</script>
<BODY onLoad="show_add_snippet_box()">

<H2>'.$Language->getText('snippet_addversion','now_add').'</H2>
<P>
<span class="highlight"><B>'.$Language->getText('snippet_addversion','important').'</B></span>
<P>
'.$Language->getText('snippet_addversion','important_comm').'
<P>
<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.$snippet_package_version_id.'" TARGET="_blank">'.$Language->getText('snippet_addversion','add').'</A>
<P>
'.$Language->getText('snippet_addversion','browse_lib').'
<P>';
 
 
	  snippet_footer(array());
	  exit;
	}
	
      } else {
	exit_error($Language->getText('global','error'),$Language->getText('snippet_add_snippet_to_package','error_fill_all_info'));
      }
      
    }
    snippet_header(array('title'=>$Language->getText('snippet_addversion','submit_s')));
    
    echo 
      $Language->getText('snippet_addversion','post_p').'
		<P>
		<FORM ACTION="?" METHOD="POST">'.
                $csrf->fetchHTMLInput() .'
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="type" VALUE="package">
		<INPUT TYPE="HIDDEN" NAME="snippet_package_id" VALUE="'.$id.'">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="'.$id.'">

		<TABLE>
		<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_addversion','version').'</B><BR>
			<INPUT TYPE="TEXT" NAME="version" SIZE="10" MAXLENGTH="15">
		</TD></TR>

		<TR><TD COLSPAN="2"><B>'.$Language->getText('snippet_addversion','changes').'</B><BR>
			<TEXTAREA NAME="changes" ROWS="5" COLS="45"></TEXTAREA>
		</TD></TR>

		<TR><TD COLSPAN="2">
			<B>'.$Language->getText('snippet_add_snippet_to_package','all_info_complete').'</B>
			<BR><BR>
			<INPUT CLASS="btn btn-primary" TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
		</TD></TR>
	        </FORM>
	        </TABLE>
		';
    
    snippet_footer(array());
    
    
  } else {
    exit_error($Language->getText('global','error'),$Language->getText('snippet_addversion','form_mangled'));
  }
  
} else {
    exit_not_logged_in();
}
