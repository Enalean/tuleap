<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('../snippet/snippet_utils.php');

if (user_isloggedin()) {

    // check if the code snippet is uploaded
    if ($uploaded_data) {
	$code = addslashes(fread( fopen($uploaded_data, 'r'), filesize($uploaded_data)));
	if ((strlen($code) > 20) && (strlen($code) < 512000)) {
	    //size is fine
	    $feedback .= ' Code Snippet Uploaded ';
	} else {
	    //too big or small
	    $feedback .= ' ERROR - patch must be > 20 bytes and < 512000 bytrs in length ';
	    $code='';
	}
    }

    if ($post_changes) {
	/*
	  Create a new snippet entry, then create a new snippet version entry
	*/
	if ($name && $description && $language != 0 && $category != 0 && $type != 0 && $version && $code) {

	    $sql="INSERT INTO snippet (category,created_by,name,description,type,language,license) ".
		"VALUES ('$category','". user_getid() ."','". htmlspecialchars($name)."','".
		htmlspecialchars($description)."','$type','$language','$license')";
	    $result=db_query($sql);
	    if (!$result) {
		$feedback .= ' ERROR DOING SNIPPET INSERT! ';
		echo db_error();
	    } else {
		$feedback .= ' Snippet Added Successfully. ';
		$snippet_id=db_insertid($result);
		/*
		  create the snippet version
		*/
		$sql="INSERT INTO snippet_version (snippet_id,changes,version,submitted_by,date,code,filename,filesize,filetype) ".
		    "VALUES ('$snippet_id','".htmlspecialchars($changes)."','".
		    htmlspecialchars($version)."','".user_getid()."','".
		    time()."','".
		    ($uploaded_data ? $code : htmlspecialchars($code))."',".
		    "'$uploaded_data_name','$uploaded_data_size','$uploaded_data_type')";
		$result=db_query($sql);
		if (!$result) {
		    $feedback .= ' ERROR DOING SNIPPET VERSION INSERT! ';
		    echo db_error();
		} else {
		    $feedback .= ' Snippet Version Added Successfully. ';
		}
	    }
	} else {
	    exit_error('Error','Error - Go back and fill in all the information');
	}
	
    }
	snippet_header(array('title'=>'Submit A New Snippet'));

	?>
	<H1>Post a New Code Snippet</H2>
	<P>
	You can post a new code snippet and share it with other people around the world. 
	Just fill in this information. <B>Give a good description</B> and <B>comment your code</B> 
	     so others can read and understand it. Preferably copy-paste the source code of the snippet so that it is directly visible in the Code Snippet Library. Upload it only if it is big or it is made of several files or the format is not human readable.
	<P>
	<FONT COLOR="RED"><B>Note:</B></FONT> You can submit a new version of an existing snippet by 
	browsing the library. You should only use this page if you are submitting an 
	entirely new script or function.
	<P>
	<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST" enctype="multipart/form-data">
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<INPUT TYPE="HIDDEN" NAME="changes" VALUE="First Posted Version">

	<TABLE>

	<TR><TD COLSPAN="2"><B>Title:</B>&nbsp;
		<INPUT TYPE="TEXT" NAME="name" SIZE="45" MAXLENGTH="60">
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Description:</B><BR>
		<TEXTAREA NAME="description" ROWS="5" COLS="45" WRAP="SOFT"></TEXTAREA>
	</TD></TR>

	<TR>
	<TD><B>Type:</B>&nbsp;
		<?php echo html_build_select_box_from_array($SCRIPT_TYPE,'type'); ?>
	</TD>

	<TD><B>License:</B>&nbsp;
		<?php echo html_build_select_box_from_array ($SCRIPT_LICENSE,'license'); ?>
	</TD>
	</TR>

	<TR>
	<TD><B>Language:</B>&nbsp;
		<?php echo html_build_select_box_from_array ($SCRIPT_LANGUAGE,'language'); ?>
		<BR>
		<A HREF="/support/?func=addsupport&group_id=1">Suggest a Language</A>
	</TD>

	<TD><B>Category:</B>&nbsp;
		<?php echo html_build_select_box_from_array ($SCRIPT_CATEGORY,'category'); ?>
                <BR>
                <A HREF="/support/?func=addsupport&group_id=1">Suggest a Category</A>
	</TD>
	</TR>
 
	<TR><TD COLSPAN="2"><B>Version:</B>&nbsp;
		<INPUT TYPE="TEXT" NAME="version" SIZE="10" MAXLENGTH="15">
	</TD></TR>
  
	<TR><TD COLSPAN="2">
	 <br><B>Upload the Snippet (binary or source code)</B>
		<P>
		<input type="file" name="uploaded_data"  size="40">
		<P>
                <B>OR paste the snippet source code here:</B><BR>
		<TEXTAREA NAME="code" ROWS="20" COLS="85" WRAP="SOFT"></TEXTAREA>
	</TD></TR>
 
	<TR><TD COLSPAN="2" ALIGN="MIDDLE">
		<B>Make sure all info is complete and accurate</B>
		<BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	</TD></TR>
	</FORM>
	</TABLE>
	<?php
	snippet_footer(array());

} else {

	exit_not_logged_in();

}

?>
