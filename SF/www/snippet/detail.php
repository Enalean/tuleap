<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('../snippet/snippet_utils.php');

/*

	Show a detail page for either a snippet or a package
	or a specific version of a package

*/

if ($type=='snippet') {
	/*


		View a snippet and show its versions
		Expand and show the code for the latest version


	*/

	snippet_header(array('title'=>'Snippet Library'));

	snippet_show_snippet_details($id);

	/*
		Get all the versions of this snippet
	*/
	$sql="SELECT user.user_name,snippet_version.snippet_version_id,snippet_version.version,snippet_version.date,snippet_version.changes ".
		"FROM snippet_version,user ".
		"WHERE user.user_id=snippet_version.submitted_by AND snippet_id='$id' ".
		"ORDER BY snippet_version.snippet_version_id DESC";

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '<H3>Error - no versions found</H3>';
	} else {
		echo '
		<H3>Versions Of This Snippet:</H3>
		<P>';
		$title_arr=array();
		$title_arr[]='ID';
		$title_arr[]='Snippet Version';
		$title_arr[]='Date Posted';
		$title_arr[]='Author';
		$title_arr[]='Delete';

		echo html_build_list_table_top ($title_arr);

		/*
			get the newest version of this snippet, so we can display its code
		*/
		$newest_version=db_result($result,0,'snippet_version_id');

		for ($i=0; $i<$rows; $i++) {
			echo '
				<TR BGCOLOR="'. html_get_alt_row_color($i) .'"><TD>'.db_result($result,$i,'snippet_version_id').
				'</TD><TD><A HREF="/snippet/download.php?type=snippet&id='.
				db_result($result,$i,'snippet_version_id').'"><B><center>'.
				db_result($result,$i,'version').'</center></B></A></TD><TD>'. 
				date($sys_datefmt,db_result($result,$i,'date')).'</TD><TD align="middle">'.
				'<a href="/users/'.db_result($result,$i,'user_name').'"><b>'.
				db_result($result,$i,'user_name').'</b></a></TD><TD ALIGN="MIDDLE"><A HREF="/snippet/delete.php?type=snippet&snippet_version_id='.
				db_result($result,$i,'snippet_version_id').
				'"><IMG SRC="/images/ic/trash.png" HEIGHT="16" WIDTH="16" BORDER="0"></A></TD></TR>';

				if ($i != ($rows - 1)) {
					echo '
					<TR'.$row_color.'><TD COLSPAN=5>Changes since last version:<BR>'.
					nl2br(db_result($result,$i,'changes')).'</TD></TR>';
				}
		}
		echo '</TABLE>';

		echo '
		<P>
		- Download a raw-text version of this code by clicking on the &quot;<B>Snippet Version</B>&quot; label</p>';
		echo '
                            <P>- You can <A HREF="/snippet/addversion.php?type=snippet&id='.$id.'"><b><u>submit a new version</u></b></A> of this snippet if you have modified it 
	and you feel it is appropriate to share with others.</p>';

	}
	/*
		show the latest version of this snippet's code
	*/
	$result=db_query("SELECT code,version FROM snippet_version WHERE snippet_version_id='$newest_version'");	

	echo '
		<P>
		<HR>
		<P>
		<H2>Latest Snippet Version: '.db_result($result,0,'version').'</H2>
		<P>
		<PRE><FONT SIZE="-1">
'. db_result($result,0,'code') .'
		</FONT></PRE>
		<P>';

	snippet_footer(array());

} else if ($type=='package') {
	/*


		View a package and show its versions
		Expand and show the snippets for the latest version


	*/

	snippet_header(array('title'=>'Snippet Library'));

	snippet_show_package_details($id);

	/*
		Get all the versions of this package
	*/
	$sql="SELECT user.user_name,snippet_package_version.snippet_package_version_id,".
		"snippet_package_version.version,snippet_package_version.date ".
		"FROM snippet_package_version,user ".
		"WHERE user.user_id=snippet_package_version.submitted_by AND snippet_package_id='$id' ".
		"ORDER BY snippet_package_version.snippet_package_version_id DESC";

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '<H3>Error - no versions found</H3>';
	} else {
		echo '
		<H3>Versions Of This Package:</H3>
		<P>';
		$title_arr=array();
		$title_arr[]='Package Version';
		$title_arr[]='Date Posted';
		$title_arr[]='Author';
		$title_arr[]='Edit/Delete';

		echo html_build_list_table_top ($title_arr);

		/*
			determine the newest version of this package, 
			so we can display the snippets that it contains
		*/
		$newest_version=db_result($result,0,'snippet_package_version_id');

		for ($i=0; $i<$rows; $i++) {
			echo '
			<TR BGCOLOR="'. html_get_alt_row_color($i) .'"><TD><A HREF="/snippet/detail.php?type=packagever&id='.
				db_result($result,$i,'snippet_package_version_id').'"><B><center>'.
				db_result($result,$i,'version').'</center></B></A></TD><TD>'.
				date($sys_datefmt,db_result($result,$i,'date')).'</TD><TD align="middle">'.
				'<a href="/users/'.db_result($result,$i,'user_name').'"><b>'.
				db_result($result,$i,'user_name').
				'</b></a></TD><TD ALIGN="MIDDLE"><A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.
				db_result($result,$i,'snippet_package_version_id').
				'"><IMG SRC="/images/ic/notes.png" BORDER="0"></A>
				&nbsp; &nbsp; &nbsp; <A HREF="/snippet/delete.php?type=package&snippet_package_version_id='.
				db_result($result,$i,'snippet_package_version_id').
				'"><IMG SRC="/images/ic/trash.png" BORDER="0"></A></TD></TR>';
		}
		echo '</TABLE>';

		
		echo '
                            <P>You can <A HREF="/snippet/addversion.php?type=package&id='.$id.'"><b><u>submit a new version</u></b></A> of this package if you have modified it 
	and you feel it is appropriate to share with others.</p>';
	}

	/*
		show the latest version of the package
		and its snippets
	*/

	echo '
		<P>
		<HR>
		<P>
		<H2>Latest Package Version: '.db_result($result,0,'version').'</H2>
		<P>
		<P>';
	snippet_show_package_snippets($newest_version);

	echo '
		<P>
		Download a raw-text version of this code by clicking on the &quot;<B>Snippet Version</B>&quot; label</p>';

	snippet_footer(array());

} else if ($type=='packagever') {
	/*
		Show a specific version of a package and its specific snippet versions
	*/
	
	snippet_header(array('title'=>'Snippet Library'));

	snippet_show_package_details($id);

	snippet_show_package_snippets($id);

	snippet_footer(array());

} else {

	exit_error('Error','Error - was the URL mangled?');

}

?>
