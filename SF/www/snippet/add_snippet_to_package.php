<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('../snippet/snippet_utils.php');

function handle_add_exit() {
	global $suppress_nav;
        if ($suppress_nav) {
                echo '
                </BODY></HTML>';
        } else {
                snippet_footer(array());
        }
	exit;
}

if (user_isloggedin()) {

	if ($suppress_nav) {
		echo '
		<HTML>
		<LINK rel="stylesheet" href="/sourceforge.css" type="text/css">
		<BODY BGCOLOR="#FFFFFF">';
	} else {
		snippet_header(array('title'=>'Submit A New Snippet'));
	}

	if (!$snippet_package_version_id) {
		//make sure the package id was passed in
		echo '<H1>Error - snippet_package_version_id missing</H1>';
		handle_add_exit();
	}

	if ($post_changes) {
		/*
			Create a new snippet entry, then create a new snippet version entry
		*/
		if ($snippet_package_version_id && $snippet_version_id) {
			/*
				check to see if they are the creator of this version
			*/
			$result=db_query("SELECT * FROM snippet_package_version ".
				"WHERE submitted_by='".user_getid()."' AND ".
				"snippet_package_version_id='$snippet_package_version_id'");
			if (!$result || db_numrows($result) < 1) {
				echo '<H1>Error - Only the creator of a package version can add snippets to it.</H1>';
				handle_add_exit();
			}

			/*
				make sure the snippet_version_id exists
			*/
			$result=db_query("SELECT * FROM snippet_version WHERE snippet_version_id='$snippet_version_id'");
			if (!$result || db_numrows($result) < 1) {
				echo '<H1>Error - That snippet doesn\'t exist.</H1>';
				echo '<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.$snippet_package_version_id.'">Back To Add Page</A>';
				handle_add_exit();
			}

			/*
				make sure the snippet_version_id isn't already in this package
			*/
			$result=db_query("SELECT * FROM snippet_package_item ".
				"WHERE snippet_package_version_id='$snippet_package_version_id' ".
				"AND snippet_version_id='$snippet_version_id'");
			if ($result && db_numrows($result) > 0) {
				echo '<H1>Error - That snippet was already added to this package.</H1>';
				echo '<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.$snippet_package_version_id.'">Back To Add Page</A>';
				handle_add_exit();
			}

			/*
				create the snippet version
			*/
			$sql="INSERT INTO snippet_package_item (snippet_package_version_id,snippet_version_id) ".
				"VALUES ('$snippet_package_version_id','$snippet_version_id')";
			$result=db_query($sql);

			if (!$result) {
				$feedback .= ' ERROR DOING SNIPPET VERSION INSERT! ';
				echo db_error();
			} else {
				$feedback .= ' Snippet Version Added Successfully. ';
			}
		} else {
			echo '<H1>Error - Go back and fill in all the information</H1>';
			echo '<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.$snippet_package_version_id.'">Back To Add Page</A>';
			handle_add_exit();
		}

	}

	$result=db_query("SELECT snippet_package.name,snippet_package_version.version ".
			"FROM snippet_package,snippet_package_version ".
			"WHERE snippet_package.snippet_package_id=snippet_package_version.snippet_package_id ".
			"AND snippet_package_version.snippet_package_version_id='$snippet_package_version_id'");

	?>
	<H1>Add Snippet To Package</H2>
	<P>
	<B>Package:</B><BR>
	<?php echo db_result($result,0,'name') . ' -  ' . db_result($result,0,'version'); ?>
	<P>
	You can use this form repeatedly to keep adding snippets to your package.
	<P>
	The "Snippet Version ID" is the unique ID number that is shown next to a specific version of a snippet 
	on the browse pages.
	<P>
	<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<INPUT TYPE="HIDDEN" NAME="snippet_package_version_id" VALUE="<?php echo $snippet_package_version_id; ?>">
	<INPUT TYPE="HIDDEN" NAME="suppress_nav" VALUE="<?php echo $suppress_nav; ?>">

	<TABLE>
	<TR><TD COLSPAN="2" ALIGN="MIDDLE">
		<B>Add This Snippet Version ID:</B><BR>
		<INPUT TYPE="TEXT" NAME="snippet_version_id" SIZE="6" MAXLENGTH="7">
	</TD></TR>

	<TR><TD COLSPAN="2" ALIGN="MIDDLE">
		<B>Make sure all info is complete and accurate</B>
		<BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	</TD></TR>
	</FORM>
	</TABLE>
	<?php
	/*
		Show the snippets in this package
	*/
	$result=db_query("SELECT snippet_package_item.snippet_version_id, snippet_version.version, snippet.name ".
		"FROM snippet,snippet_version,snippet_package_item ".
		"WHERE snippet.snippet_id=snippet_version.snippet_id ".
		"AND snippet_version.snippet_version_id=snippet_package_item.snippet_version_id ".
		"AND snippet_package_item.snippet_package_version_id='$snippet_package_version_id'");
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo db_error();
		echo '
		<P>
		No Snippets Are In This Package Yet';
	} else {
		$HTML->box1_top('Snippets In This Package');
		for ($i=0; $i<$rows; $i++) {
			echo '
			<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD ALIGN="MIDDLE">
				<A HREF="/snippet/delete.php?type=frompackage&snippet_version_id='.
				db_result($result,$i,'snippet_version_id').
				'&snippet_package_version_id='.$snippet_package_version_id.
				'"><IMG SRC="/images/ic/trash.png" HEIGHT="16" WIDTH="16" BORDER="0"></A></TD><TD WIDTH="99%">'.
				db_result($result,$i,'name').' '.db_result($result,$i,'version')."</TD></TR>";

			$last_group=db_result($result,$i,'group_id');
		}
		$HTML->box1_bottom();
	}
	echo '
	<P>
	<H2><FONT COLOR="RED">'.$feedback.'</FONT></H2>';

	handle_add_exit();

} else {

	exit_not_logged_in();

}

?>
