<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	Code Snippet System
	By Tim Perdue, Sourceforge, Jan 2000
*/


require ("$DOCUMENT_ROOT/snippet/snippet_data.php");



function snippet_header($params) {
	global $is_snippet_page,$DOCUMENT_ROOT,$HTML,$feedback;

	// LJ used so the search box will add the necessary element to the pop-up box
	// CodeX Specific
	$is_snippet_page=1;


	$HTML->header($params);
	/*
		Show horizontal links
	*/
	echo '<H2>' . $params['header'] . '</H2>';
	echo '<P><B>';
	echo '<A HREF="/snippet/">Browse</A>
		 | <A HREF="/snippet/submit.php">Create a New Snippet</A>
		 | <A HREF="/snippet/package.php">Create A New Package</A></B>';
	if ($params['help']) {
	    echo ' | '.help_button($params['help'],false,'Help');
	}
	echo '<P>';
	html_feedback_top($feedback);
}

function snippet_footer($params) {
	GLOBAL $HTML;
	global $feedback;
	html_feedback_bottom($feedback);
	$HTML->footer($params);
}

function snippet_show_package_snippets($version) {
	//show the latest version
	$sql="SELECT snippet.snippet_id, snippet_package_item.snippet_version_id, snippet_version.version,snippet.name,user.user_name ".
		"FROM snippet,snippet_version,snippet_package_item,user ".
		"WHERE snippet.snippet_id=snippet_version.snippet_id ".
		"AND user.user_id=snippet_version.submitted_by ".
		"AND snippet_version.snippet_version_id=snippet_package_item.snippet_version_id ".
		"AND snippet_package_item.snippet_package_version_id='$version'";

	$result=db_query($sql);
	$rows=db_numrows($result);
	echo '
	<P>
	<H3>Snippets In This Package:</H3>
	<P>';

	$title_arr=array();
	$title_arr[]='ID';
	$title_arr[]='Snippet Version';
	$title_arr[]='Title';
	$title_arr[]='Author';

	echo html_build_list_table_top ($title_arr,$links_arr);

	if (!$result || $rows < 1) {
		echo db_error();
		echo '
			<TR><TD COLSPAN="4"><H3>No Snippets Are In This Package Yet</H3></TD></TR>';
	} else {

		//get the newest version, so we can display it's code
		$newest_version=db_result($result,0,'snippet_version_id');

		for ($i=0; $i<$rows; $i++) {
			echo '
			<TR class="'. util_get_alt_row_color($i) .'">
                            <TD><A HREF="/snippet/detail.php?type=snippet&id='.db_result($result,$i,'snippet_id').'"><b><center>'.
				db_result($result,$i,'snippet_version_id').'</center></b></A></TD>
                            <TD><A HREF="/snippet/download.php?type=snippet&id='.
				db_result($result,$i,'snippet_version_id').'"><b><center>'.
				db_result($result,$i,'version').'</center></b></A></TD>
                             <TD>'.db_result($result,$i,'name').'</TD><TD>'.
				db_result($result,$i,'user_name').'</TD></TR>';
		}
	}
	echo '</TABLE>';

}

function snippet_show_package_details($id) {

	$sql="SELECT * FROM snippet_package WHERE snippet_package_id='$id'";
	$result=db_query($sql);

	echo '
	<P>
	<TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">

	<TR><TD COLSPAN="2">
	<H2>'. db_result($result,0,'name').'</H2>
	</TD></TR>

	<TR>
		<TD><B>Category:</B><BR>
		'.snippet_data_get_category_from_id(db_result($result,0,'category')).'
		</TD>

		<TD><B>Language:</B><BR>
		'.snippet_data_get_language_from_id(db_result($result,0,'language')).'
		</TD>
	</TR>

	<TR><TD COLSPAN="2">&nbsp;<BR><B>Description:</B><BR>
	'. util_make_links(nl2br(db_result($result,0,'description'))).'
	</TD></TR>

	</TABLE>';

}

function snippet_show_snippet_details($id) {

	$sql="SELECT * FROM snippet WHERE snippet_id='$id'";
	$result=db_query($sql);

	echo '
	<P>
	<TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">

	<TR><TD COLSPAN="2">
	<H2>'. db_result($result,0,'name').'</H2>
	</TD></TR>

	<TR><TD><B>Type:</B><BR>
		'.snippet_data_get_type_from_id(db_result($result,0,'type')).'</TD>
	<TD><B>Category:</B><BR>
		'.snippet_data_get_category_from_id(db_result($result,0,'category')).'
	</TD></TR>

	<TR><TD><B>License:</B><BR>
		'.snippet_data_get_license_from_id(db_result($result,0,'license')).'</TD>
	<TD><B>Language:</B><BR>
		'.snippet_data_get_language_from_id(db_result($result,0,'language')).'
	</TD></TR>

	<TR><TD COLSPAN="2">&nbsp;<BR>
	<B>Description:</B><BR>
	'. util_make_links(nl2br(db_result($result,0,'description'))).'
	</TD></TR>

	</TABLE>';
}

function snippet_edit_package_details($id) {

	$sql="SELECT * FROM snippet_package WHERE snippet_package_id='$id'";
	$result=db_query($sql);

	echo '
	<FORM ACTION="" METHOD="POST" enctype="multipart/form-data">
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<P>
	<TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">

	<TR><TD COLSPAN="2">
	<B>Title:</B><BR>
        <INPUT TYPE="TEXT" NAME="snippet_name" SIZE="45" MAXLENGTH="60" VALUE="'.db_result($result,0,'name').'">
	</TD></TR>

	<TR>
		<TD><B>Category:</B><BR>
		'.html_build_select_box(snippet_data_get_all_categories() ,'snippet_category',db_result($result,0,'category'),false).'
		</TD>

		<TD><B>Language:</B><BR>
		'.html_build_select_box(snippet_data_get_all_languages() ,"snippet_language",db_result($result,0,'language'),false).'
		</TD>
	</TR>

	<TR><TD COLSPAN="2">&nbsp;<BR><B>Description:</B><BR>
	    <TEXTAREA NAME="snippet_description" ROWS="5" COLS="45" WRAP="SOFT">'.db_result($result,0,'description').'</TEXTAREA>
	</TD></TR>
	<TR><TD COLSPAN="2" ALIGN="center">
		<B>Make sure all info is complete and accurate</B>
		<BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	</TD></TR>
	</TABLE>
	</FORM>
        <HR>
';

}


function snippet_edit_snippet_details($id) {

	$sql="SELECT * FROM snippet WHERE snippet_id='$id'";
	$result=db_query($sql);

	echo '
	<FORM ACTION="" METHOD="POST" enctype="multipart/form-data">
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<P>
	<TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">

	<TR><TD COLSPAN="2">
        <B>Title:</B>&nbsp;
	<INPUT TYPE="TEXT" NAME="snippet_name" SIZE="45" MAXLENGTH="60" VALUE="'.db_result($result,0,'name').'">
	</TD></TR>

	<TR><TD><B>Type:</B><BR>
		'.html_build_select_box(snippet_data_get_all_types() ,'snippet_type',db_result($result,0,'type'),false).'
        </TD><TD><B>Category:</B><BR>
		'.html_build_select_box(snippet_data_get_all_categories() ,'snippet_category',db_result($result,0,'category'),false).'
	</TD></TR>

	<TR><TD><B>License:</B><BR>
		'.html_build_select_box(snippet_data_get_all_licenses() ,'snippet_license',db_result($result,0,'license'),false).'
        </TD><TD><B>Language:</B><BR>
		'.html_build_select_box(snippet_data_get_all_languages() ,"snippet_language",db_result($result,0,'language'),false).'
	</TD></TR>

	<TR><TD COLSPAN="2">&nbsp;<BR>
	<B>Description:</B><BR>
	    <TEXTAREA NAME="snippet_description" ROWS="5" COLS="45" WRAP="SOFT">'.db_result($result,0,'description').'</TEXTAREA>
	</TD></TR>

	<TR><TD COLSPAN="2" ALIGN="center">
		<B>Make sure all info is complete and accurate</B>
		<BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	</TD></TR>
	</TABLE>
	</FORM>
        <HR>
';
}

?>
