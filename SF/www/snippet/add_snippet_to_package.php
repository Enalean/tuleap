<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../snippet/snippet_utils.php');

$LANG->loadLanguageMsg('snippet/snippet');

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
		<BODY>';
	} else {
		snippet_header(array('title'=>$LANG->getText('snippet_add_snippet_to_package','submit_snippet')));
	}

	if (!$snippet_package_version_id) {
		//make sure the package id was passed in
		echo '<H1>'.$LANG->getText('snippet_add_snippet_to_package','error_v_id_missed').'</H1>';
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
				echo '<H1>'.$LANG->getText('snippet_add_snippet_to_package','error_only_creator').'</H1>';
				handle_add_exit();
			}

			/*
				make sure the snippet_version_id exists
			*/
			$result=db_query("SELECT * FROM snippet_version WHERE snippet_version_id='$snippet_version_id'");
			if (!$result || db_numrows($result) < 1) {
				echo '<H1>'.$LANG->getText('snippet_add_snippet_to_package','error_s_not_exist').'</H1>';
				echo '<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.$snippet_package_version_id.'">'.$LANG->getText('snippet_add_snippet_to_package','back').'</A>';
				handle_add_exit();
			}

			/*
				make sure the snippet_version_id isn't already in this package
			*/
			$result=db_query("SELECT * FROM snippet_package_item ".
				"WHERE snippet_package_version_id='$snippet_package_version_id' ".
				"AND snippet_version_id='$snippet_version_id'");
			if ($result && db_numrows($result) > 0) {
				echo '<H1>'.$LANG->getText('snippet_add_snippet_to_package','already_added').'</H1>';
				echo '<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.$snippet_package_version_id.'">'.$LANG->getText('snippet_add_snippet_to_package','back').'</A>';
				handle_add_exit();
			}

			/*
				create the snippet version
			*/
			$sql="INSERT INTO snippet_package_item (snippet_package_version_id,snippet_version_id) ".
				"VALUES ('$snippet_package_version_id','$snippet_version_id')";
			$result=db_query($sql);

			if (!$result) {
				$feedback .= ' '.$LANG->getText('snippet_add_snippet_to_package','error_insert').' ';
				echo db_error();
			} else {
				$feedback .= ' '.$LANG->getText('snippet_add_snippet_to_package','add_success').' ';
			}
		} else {
			echo '<H1>'.$LANG->getText('snippet_add_snippet_to_package','error_fill_all_info').'</H1>';
			echo '<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.$snippet_package_version_id.'">'.$LANG->getText('snippet_add_snippet_to_package','back').'</A>';
			handle_add_exit();
		}

	}

	$result=db_query("SELECT snippet_package.name,snippet_package_version.version ".
			"FROM snippet_package,snippet_package_version ".
			"WHERE snippet_package.snippet_package_id=snippet_package_version.snippet_package_id ".
			"AND snippet_package_version.snippet_package_version_id='$snippet_package_version_id'");

	echo '
	<H1>'.$LANG->getText('snippet_add_snippet_to_package','add_s').'</H2>
	<P>
	<B>'.$LANG->getText('snippet_add_snippet_to_package','package',array(db_result($result,0,'name'),db_result($result,0,'version'))).'
	<P>
	'.$LANG->getText('snippet_add_snippet_to_package','use_add_form').'
	<P>
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<INPUT TYPE="HIDDEN" NAME="snippet_package_version_id" VALUE="'.$snippet_package_version_id.'">
	<INPUT TYPE="HIDDEN" NAME="suppress_nav" VALUE="'.$suppress_nav.'">

	<TABLE>
	<TR><TD COLSPAN="2" ALIGN="center">
		<B>'.$LANG->getText('snippet_add_snippet_to_package','add_v_id').'</B><BR>
		<INPUT TYPE="TEXT" NAME="snippet_version_id" SIZE="6" MAXLENGTH="7">
	</TD></TR>

	<TR><TD COLSPAN="2" ALIGN="center">
		<B>'.$LANG->getText('snippet_add_snippet_to_package','all_info_complete').'</B>
		<BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$LANG->getText('global','btn_submit').'">
	</TD></TR>
	</FORM>
	</TABLE>';
	
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
		'.$LANG->getText('snippet_add_snippet_to_package','no_s_in_p');
	} else {
		$HTML->box1_top($LANG->getText('snippet_add_snippet_to_package','s_in_p'));
		for ($i=0; $i<$rows; $i++) {
			echo '
			<TR class="'. util_get_alt_row_color($i) .'"><TD ALIGN="center">
				<A HREF="/snippet/delete.php?type=frompackage&snippet_version_id='.
				db_result($result,$i,'snippet_version_id').
				'&snippet_package_version_id='.$snippet_package_version_id.
				'"><IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></A></TD><TD WIDTH="99%">'.
				db_result($result,$i,'name').' '.db_result($result,$i,'version')."</TD></TR>";

			$last_group=db_result($result,$i,'group_id');
		}
		$HTML->box1_bottom();
	}
	echo '
	<P>
	<H2><span class="feedback">'.$feedback.'</span></H2>';

	handle_add_exit();

} else {

	exit_not_logged_in();

}

?>
