<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');

$HTML->header(array('title'=>'Search'));

echo "<P><CENTER>";

menu_show_search_box();

/*
	Force them to enter at least three characters
*/
if ($words && (strlen($words) < 3)) {
	echo "<H2>Search must be at least three characters</H2>";
	$HTML->footer(array());
	exit;
}

if (!$words) {
	echo "<BR>Enter Your Search Words Above</CENTER><P>";
	$HTML->footer(array());
	exit;
}

$words = trim($words);
$no_rows = 0;

if ($exact) {
	$crit='AND';
} else {
	$crit='OR';
}

if (!$offset || $offset < 0) {
	$offset = 0;
}

if ($type_of_search == "soft") {
	/*
		If multiple words, separate them and put LIKE in between
	*/
	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit group_name LIKE '%");
	$words2=implode($array,"%' $crit short_description LIKE '%");
	$words3=implode($array,"%' $crit unix_group_name LIKE '%");

	/*
		Query to find software
	*/
	$sql = "SELECT group_name,unix_group_name,group_id,short_description ".
		"FROM groups ".
		"WHERE status='A' AND is_public='1' AND ((group_name LIKE '%$words1%') OR (short_description LIKE '%$words2%') OR (unix_group_name LIKE '%$words3%')) LIMIT $offset,26";
	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if (!$result || $rows < 1) {
		$no_rows = 1;
		echo "<H2>No matches found for $words</H2>";
		echo db_error();
//		echo $sql;
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo "<H3>Search results for $words</H3><P>\n\n";

		$title_arr = array();
		$title_arr[] = 'Group Name';
		$title_arr[] = 'Description';

		echo html_build_list_table_top($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"<TR BGCOLOR=\"". html_get_alt_row_color($i)."\"><TD><A HREF=\"/projects/".db_result($result, $i, 'unix_group_name')."/\">"
				. "<IMG SRC=\"/images/msg.gif\" BORDER=0 HEIGHT=12 WIDTH=10> ".db_result($result, $i, 'group_name')."</A></TD>"
				. "<TD>".db_result($result,$i,'short_description')."</TD></TR>\n";
		}
		echo "</TABLE>\n";
	}

} else if ($type_of_search == "people") {

	/*
		If multiple words, separate them and put LIKE in between
	*/
	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit user_name LIKE '%");
	$words2=implode($array,"%' $crit realname LIKE '%");

	/*
		Query to find users
	*/
	$sql =	"SELECT user_name,user_id,realname "
		. "FROM user "
		. "WHERE ((user_name LIKE '%$words1%') OR (realname LIKE '%$words2%')) AND (status='A') ORDER BY user_name LIMIT $offset,26";
	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if (!$result || $rows < 1) {
		$no_rows = 1;
		echo "<H2>No matches found for $words</H2>";
		echo db_error();
//		echo $sql;
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo "<H3>Search results for $words</H3><P>\n\n";

		$title_arr = array();
		$title_arr[] = 'User Name';
		$title_arr[] = 'Real Name';

		echo html_build_list_table_top ($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"<TR BGCOLOR=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/users/".db_result($result, $i, 'user_name')."/\">"
				. "<IMG SRC=\"/images/msg.gif\" BORDER=0 HEIGHT=12 WIDTH=10> ".db_result($result, $i, 'user_name')."</A></TD>"
				. "<TD>".db_result($result,$i,'realname')."</TD></TR>\n";
		}
		echo "</TABLE>\n";
	}

} else if ($type_of_search == 'forums') {

	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit forum.body LIKE '%");
	$words2=implode($array,"%' $crit forum.subject LIKE '%");

	$sql =	"SELECT forum.msg_id,forum.subject,forum.date,user.user_name "
		. "FROM forum,user "
		. "WHERE user.user_id=forum.posted_by AND ((forum.body LIKE '%$words1%') "
		. "OR (forum.subject LIKE '%$words2%')) AND forum.group_forum_id='$forum_id' "
		. "GROUP BY msg_id,subject,date,user_name LIMIT $offset,26";
	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if (!$result || $rows < 1) {
		$no_rows = 1;
		echo "<H2>No matches found for $words</H2>";
		echo db_error();
//		echo $sql;
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo "<H3>Search results for $words</H3><P>\n\n";

		$title_arr = array();
		$title_arr[] = 'Thread';
		$title_arr[] = 'Author';
		$title_arr[] = 'Date';

		echo html_build_list_table_top ($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"<TR BGCOLOR=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/forum/message.php?msg_id="
				. db_result($result, $i, "msg_id")."\"><IMG SRC=\"/images/msg.gif\" BORDER=0 HEIGHT=12 WIDTH=10> "
				. db_result($result, $i, "subject")."</A></TD>"
				. "<TD>".db_result($result, $i, "user_name")."</TD>"
				. "<TD>".date($sys_datefmt,db_result($result,$i,"date"))."</TD></TR>\n";
		}
		echo "</TABLE>\n";
	}

} else if ($type_of_search == 'bugs') {

	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit bug.details LIKE '%");
	$words2=implode($array,"%' $crit bug.summary LIKE '%");

	$sql =	"SELECT bug.bug_id,bug.summary,bug.date,user.user_name "
		. "FROM bug,user "
		. "WHERE user.user_id=bug.submitted_by AND ((bug.details LIKE '%$words1%') "
		. "OR (bug.summary LIKE '%$words2%')) AND bug.group_id='$group_id' "
		.  "GROUP BY bug_id,summary,date,user_name LIMIT $offset,26";
	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if ( !$result || $rows < 1) {
		$no_rows = 1;
		echo "<H2>No matches found for $words</H2>";
		echo db_error();
//		echo $sql;
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo "<H3>Search results for $words</H3><P>\n";

		$title_arr = array();
		$title_arr[] = 'Bug Summary';
		$title_arr[] = 'Submitted By';
		$title_arr[] = 'Date';

		echo html_build_list_table_top ($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"\n<TR BGCOLOR=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/bugs/?group_id=$group_id&func=detailbug&bug_id="
				. db_result($result, $i, "bug_id")."\"><IMG SRC=\"/images/msg.gif\" BORDER=0 HEIGHT=12 WIDTH=10> "
				. db_result($result, $i, "summary")."</A></TD>"
				. "<TD>".db_result($result, $i, "user_name")."</TD>"
				. "<TD>".date($sys_datefmt,db_result($result,$i,"date"))."</TD></TR>";
		}
		echo "</TABLE>\n";
	}
} else {

	echo "<H1>Invalid Search - ERROR!!!!</H1>";

}

   // This code puts the nice next/prev.
if ( !$no_rows && ( ($rows_returned > $rows) || ($offset != 0) ) ) {

	echo "<BR>\n";

	echo "<TABLE BGCOLOR=\"#EEEEEE\" WIDTH=\"100%\" CELLPADDING=\"5\" CELLSPACING=\"0\">\n";
	echo "<TR>\n";
	echo "\t<TD ALIGN=\"left\">";
	if ($offset != 0) {
		echo "<FONT face=\"Arial, Helvetica\" SIZE=3 STYLE=\"text-decoration: none\"><B>";
		echo "<A HREF=\"javascript:history.back()\"><B><IMG SRC=\"/images/t2.gif\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE> Previous Results </A></B></FONT>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD>\n\t<TD ALIGN=\"right\">";
	if ( $rows_returned > $rows) {
		echo "<FONT face=\"Arial, Helvetica\" SIZE=3 STYLE=\"text-decoration: none\"><B>";
		echo "<A HREF=\"/search/?type_of_search=$type_of_search&words=".urlencode($words)."&offset=".($offset+25);
		if ( $type_of_search == 'bugs' ) {
			echo "&group_id=$group_id&is_bug_page=1";
		} 
		if ( $type_of_search == 'forums' ) {
			echo "&forum_id=$forum_id&is_forum_page=1";
		}
		echo "\"><B>Next Results <IMG SRC=\"/images/t.gif\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE></A></B></FONT>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD>\n</TR>\n";
	echo "</TABLE>\n";
}



$HTML->footer(array());
?>
