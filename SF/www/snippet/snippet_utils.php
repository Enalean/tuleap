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

$SCRIPT_CATEGORY=array();
$SCRIPT_CATEGORY[0]='Choose One';
$SCRIPT_CATEGORY[1]='UNIX Admin';
$SCRIPT_CATEGORY[2]='HTML Manipulation';
$SCRIPT_CATEGORY[3]='Text Processing';
$SCRIPT_CATEGORY[4]='Print Processing';
$SCRIPT_CATEGORY[5]='Calendars';
$SCRIPT_CATEGORY[6]='Database';
$SCRIPT_CATEGORY[7]='Data Structure Manipulation';
$SCRIPT_CATEGORY[8]='File Management';
$SCRIPT_CATEGORY[9]='Scientific Computation';
$SCRIPT_CATEGORY[10]='Office Utilities';
$SCRIPT_CATEGORY[11]='User Interface';
$SCRIPT_CATEGORY[12]='Other';
$SCRIPT_CATEGORY[13]='Data Acquisition and Control';
$SCRIPT_CATEGORY[13]='Network';

$SCRIPT_TYPE[0]='Choose One';
$SCRIPT_TYPE[1]='Function';
$SCRIPT_TYPE[2]='Full Script';
$SCRIPT_TYPE[3]='Sample Code (HOWTO)';
$SCRIPT_TYPE[4]='README';
$SCRIPT_TYPE[5]='Class';
$SCRIPT_TYPE[6]='Macros';
$SCRIPT_TYPE[6]='Full Program';

$SCRIPT_LICENSE = array();
$SCRIPT_LICENSE[0] = 'Xerox Code eXchange Policy';
$SCRIPT_LICENSE[1] = 'Other';
$SCRIPT_LICENSE[2] = '--- For COMIP approved Open Source projects ---';
$SCRIPT_LICENSE[3] = 'GNU General Public License';
$SCRIPT_LICENSE[4] = 'GNU Library Public License';
$SCRIPT_LICENSE[5] = 'BSD License';
$SCRIPT_LICENSE[6] = 'MIT/X Consortium License';
$SCRIPT_LICENSE[7] = 'Artistic License';
$SCRIPT_LICENSE[8] = 'Mozilla Public License';
$SCRIPT_LICENSE[9] = 'Qt Public License';
$SCRIPT_LICENSE[10] = 'IBM Public License';
$SCRIPT_LICENSE[11] = 'Python License';
$SCRIPT_LICENSE[12] = 'Public Domain (Sure ?!)';

// **NEVER EVER** change the description of an item. the index of the 
// array is used as a language id in the DB. Add new languages at the end
$SCRIPT_LANGUAGE = array();
$SCRIPT_LANGUAGE[0] = 'Choose One';
$SCRIPT_LANGUAGE[1] = 'Awk';
$SCRIPT_LANGUAGE[2] = 'C';
$SCRIPT_LANGUAGE[3] = 'C++';
$SCRIPT_LANGUAGE[4] = 'Perl';
$SCRIPT_LANGUAGE[5] = 'PHP';
$SCRIPT_LANGUAGE[6] = 'Python';
$SCRIPT_LANGUAGE[7] = 'Unix Shell';
$SCRIPT_LANGUAGE[8] = 'Java';
$SCRIPT_LANGUAGE[9] = 'AppleScript';
$SCRIPT_LANGUAGE[10] = 'Visual Basic';
$SCRIPT_LANGUAGE[11] = 'TCL';
$SCRIPT_LANGUAGE[12] = 'Lisp';
$SCRIPT_LANGUAGE[13] = 'Mixed';
$SCRIPT_LANGUAGE[14] = 'JavaScript';
$SCRIPT_LANGUAGE[15] = 'SQL';
$SCRIPT_LANGUAGE[16] = 'MatLab';
$SCRIPT_LANGUAGE[17] = 'Other Language';
$SCRIPT_LANGUAGE[18] = 'LabView';
$SCRIPT_LANGUAGE[19] = 'C#';
$SCRIPT_LANGUAGE[20] = 'Postscript';

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
	$sql="SELECT snippet_package_item.snippet_version_id, snippet_version.version,snippet.name,user.user_name ".
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
			<TR class="'. util_get_alt_row_color($i) .'"><TD>'.db_result($result,$i,'snippet_version_id').
				'</TD><TD><A HREF="/snippet/download.php?type=snippet&id='.
				db_result($result,$i,'snippet_version_id').'"><b><center>'.
				db_result($result,$i,'version').'</center></b></A></TD><TD>'.
				db_result($result,$i,'name').'</TD><TD>'.
				db_result($result,$i,'user_name').'</TD></TR>';
		}
	}
	echo '</TABLE>';

}

function snippet_show_package_details($id) {
	global $SCRIPT_CATEGORY,$SCRIPT_LANGUAGE;

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
		'.$SCRIPT_CATEGORY[db_result($result,0,'category')].'
		</TD>

		<TD><B>Language:</B><BR>
		'.$SCRIPT_LANGUAGE[db_result($result,0,'language')].'
		</TD>
	</TR>

	<TR><TD COLSPAN="2">&nbsp;<BR><B>Description:</B><BR>
	'. util_make_links(nl2br(db_result($result,0,'description'))).'
	</TD></TR>

	</TABLE>';

}

function snippet_show_snippet_details($id) {
	global $SCRIPT_TYPE,$SCRIPT_CATEGORY,$SCRIPT_LICENSE,$SCRIPT_LANGUAGE;

	$sql="SELECT * FROM snippet WHERE snippet_id='$id'";
	$result=db_query($sql);

	echo '
	<P>
	<TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">

	<TR><TD COLSPAN="2">
	<H2>'. db_result($result,0,'name').'</H2>
	</TD></TR>

	<TR><TD><B>Type:</B><BR>
		'.$SCRIPT_TYPE[db_result($result,0,'type')].'</TD>
	<TD><B>Category:</B><BR>
		'.$SCRIPT_CATEGORY[db_result($result,0,'category')].'
	</TD></TR>

	<TR><TD><B>License:</B><BR>
		'.$SCRIPT_LICENSE[db_result($result,0,'license')].'</TD>
	<TD><B>Language:</B><BR>
		'.$SCRIPT_LANGUAGE[db_result($result,0,'language')].'
	</TD></TR>

	<TR><TD COLSPAN="2">&nbsp;<BR>
	<B>Description:</B><BR>
	'. util_make_links(nl2br(db_result($result,0,'description'))).'
	</TD></TR>

	</TABLE>';
}

?>
