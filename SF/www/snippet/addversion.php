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
	if ($type=='snippet') {
		/*
			See if the snippet exists first
		*/
		$result=db_query("SELECT * FROM snippet WHERE snippet_id='$id'");
		if (!$result || db_numrows($result) < 1) {
			exit_error('Error','Error - snippet doesn\'t exist');
		}

		/*
			handle inserting a new version of a snippet
		*/
		if ($post_changes) {

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

		    /*
		      Create a new snippet entry, then create a new snippet version entry
		    */
		    if ($changes && $version && $code) {
			
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
		    } else {
			exit_error('Error','Error - Go back and fill in all the information');
		    }
		    
		}
		snippet_header(array('title'=>'Submit A New Snippet Version'));

		?>
		<H1>Post a New Code Snippet Version</H2>
		<P>
		If you have modified a version of a snippet and you feel it 
		is significant enough to share with others, please do so.
                Preferably copy-paste the
                source code of the snippet so that it is directly visible in the
                Code Snippet Library. Upload it only if it is big or it is made of
                several files or the format is not human readable.
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST" enctype="multipart/form-data">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="type" VALUE="snippet">
		<INPUT TYPE="HIDDEN" NAME="snippet_id" VALUE="<?php echo $id; ?>">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="<?php echo $id; ?>">

		<TABLE>
		<TR><TD COLSPAN="2"><B>Version:</B>&nbsp;
			<INPUT TYPE="TEXT" NAME="version" SIZE="10" MAXLENGTH="15">
		</TD></TR>

		<TR><TD COLSPAN="2"><B>Changes:</B><BR>
			<TEXTAREA NAME="changes" ROWS="5" COLS="45"></TEXTAREA>
		</TD></TR>
  
		<TR><TD COLSPAN="2">
	         <br><B>Upload the Snippet (binary or source code)</B>
		<P>
		<input type="file" name="uploaded_data"  size="40">
		<P>
		 <B>Or paste snippet source code here:</B><BR>
			<TEXTAREA NAME="code" ROWS="30" COLS="85" WRAP="SOFT"></TEXTAREA>
		</TD></TR>
 
		<TR><TD COLSPAN="2" ALIGN="center">
			<B>Make sure all info is complete and accurate</B>
			<BR>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</TD></TR>
		</FORM>
		</TABLE>
		<?php

		snippet_footer(array());

	} else if ($type=='package') {
		/*
			Handle insertion of a new package version
		*/

		/*
			See if the package exists first
		*/
		$result=db_query("SELECT * FROM snippet_package WHERE snippet_package_id='$id'");
		if (!$result || db_numrows($result) < 1) {
			exit_error('Error','Error - snippet_package doesn\'t exist');
		}

		if ($post_changes) {
			/*
				Create a new snippet entry, then create a new snippet version entry
			*/
			if ($changes && $snippet_package_id) {
				/*
					create the snippet package version
				*/
				$sql="INSERT INTO snippet_package_version ".
					"(snippet_package_id,changes,version,submitted_by,date) ".
					"VALUES ('$snippet_package_id','".htmlspecialchars($changes)."','".
						htmlspecialchars($version)."','".user_getid()."','".time()."')";
				$result=db_query($sql);
				if (!$result) {
					//error in database
					$feedback .= ' ERROR DOING SNIPPET PACKAGE VERSION INSERT! ';
					snippet_header(array('title'=>'Submit A New Snippet Package'));
					echo db_error();
					snippet_footer(array());
					exit;
				} else {
					//so far so good - now add snippets to the package
					$feedback .= ' Snippet Pacakge Version Added Successfully. ';

					//id for this snippet_package_version
					$snippet_package_version_id=db_insertid($result);
					snippet_header(array('title'=>'Add Snippets to Package'));

/*
	This raw HTML allows the user to add snippets to the package
*/
					?>

<SCRIPT LANGUAGE="JavaScript">
<!--
function show_add_snippet_box() {
	newWindow = open("","occursDialog","height=500,width=300,scrollbars=yes,resizable=yes");
	newWindow.location=('/snippet/add_snippet_to_package.php?snippet_package_version_id=<?php
			echo $snippet_package_version_id; ?>');
}
// -->
</script>
<BODY onLoad="show_add_snippet_box()">

<H2>Now add snippets to your package</H2>
<P>
<span class="highlight"><B>IMPORTANT!</B></span>
<P>
If a new window opened, use it to add snippets to your package.
If a new window did not open, use the following link to add to your package BEFORE you leave this page.
<P>
<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id=<?php echo $snippet_package_version_id; ?>" TARGET="_blank">Add Snippets To Package</A>
<P>
<B>Browse the library</B> to find the snippets you want to add,
then add them using the new window link shown above.
<P>

					<?php

					snippet_footer(array());
					exit;
				}

			} else {
				exit_error('Error','Error - Go back and fill in all the information');
			}

		}
		snippet_header(array('title'=>'Submit A New Snippet Version'));

		?>
		<H1>Post a New Package Version</H2>
		<P>
		If you have modified a version of a package and you feel it
		is significant enough to share with others, please do so.
		<P>
		<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="type" VALUE="package">
		<INPUT TYPE="HIDDEN" NAME="snippet_package_id" VALUE="<?php echo $id; ?>">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="<?php echo $id; ?>">

		<TABLE>
		<TR><TD COLSPAN="2"><B>Version:</B><BR>
			<INPUT TYPE="TEXT" NAME="version" SIZE="10" MAXLENGTH="15">
		</TD></TR>

		<TR><TD COLSPAN="2"><B>Changes:</B><BR>
			<TEXTAREA NAME="changes" ROWS="5" COLS="45"></TEXTAREA>
		</TD></TR>

		<TR><TD COLSPAN="2" ALIGN="center">
			<B>Make sure all info is complete and accurate</B>
			<BR>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</TD></TR>
		</FORM>
		</TABLE>
		<?php

		snippet_footer(array());


	} else {
		exit_error('Error','Error - was the URL or form mangled??');
	}

} else {

	exit_not_logged_in();

}

?>
