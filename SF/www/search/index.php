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
		$title_arr[] = 'Project Name';
		$title_arr[] = 'Description';

		echo html_build_list_table_top($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"<TR class=\"". html_get_alt_row_color($i)."\"><TD><A HREF=\"/projects/".db_result($result, $i, 'unix_group_name')."/\">"
				. "<IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> ".db_result($result, $i, 'group_name')."</A></TD>"
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
			print	"<TR class=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/users/".db_result($result, $i, 'user_name')."/\">"
				. "<IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> ".db_result($result, $i, 'user_name')."</A></TD>"
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
			print	"<TR class=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/forum/message.php?msg_id="
				. db_result($result, $i, "msg_id")."\"><IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> "
				. db_result($result, $i, "subject")."</A></TD>"
				. "<TD>".db_result($result, $i, "user_name")."</TD>"
				. "<TD>".format_date($sys_datefmt,db_result($result,$i,"date"))."</TD></TR>\n";
		}
		echo "</TABLE>\n";
	}

} else if ($type_of_search == 'bugs') {

	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit bug.details LIKE '%");
	$words2=implode($array,"%' $crit bug.summary LIKE '%");
	$words3=implode($array,"%' $crit bug_history.old_value LIKE '%");

	$sql =	"SELECT bug.bug_id,bug.summary,bug.date,user.user_name "
		. "FROM bug "
		. "    INNER JOIN user ON user.user_id=bug.submitted_by "
		. "    LEFT JOIN bug_history ON bug_history.bug_id=bug.bug_id "
		. "WHERE "
		. "    bug.group_id='$group_id' "
		. "    AND ((bug.details LIKE '%$words1%') "
		. "      OR (bug.summary LIKE '%$words2%') "
		. "      OR (bug_history.field_name='details' "
		. "          AND (bug_history.old_value LIKE '%$words3%'))) "
		. "GROUP BY bug_id,summary,date,user_name LIMIT $offset,26";

	//	echo "DBG: $sql<br>";
	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if ( !$result || $rows < 1) {
		$no_rows = 1;
		echo "<H2>No matches found for $words</H2>";
		echo db_error();
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
			print	"\n<TR class=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/bugs/?group_id=$group_id&func=detailbug&bug_id="
				. db_result($result, $i, "bug_id")."\"><IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> "
				. db_result($result, $i, "summary")."</A></TD>"
				. "<TD>".db_result($result, $i, "user_name")."</TD>"
				. "<TD>".format_date($sys_datefmt,db_result($result,$i,"date"))."</TD></TR>";
		}
		echo "</TABLE>\n";
	}
} else if ($type_of_search == 'support') {

	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit support.summary LIKE '%");
	$words3=implode($array,"%' $crit support_messages.body LIKE '%");

	$sql =	"SELECT support.support_id,support.summary,support.open_date,user.user_name "
		. "FROM support "
		. "    INNER JOIN user ON user.user_id=support.submitted_by "
		. "    LEFT JOIN support_messages ON support_messages.support_id=support.support_id "
		. "WHERE "
		. "    support.group_id='$group_id' "
		. "    AND ((support.summary LIKE '%$words1%') "
		. "      OR (support_messages.body LIKE '%$words3%')) "
		. "GROUP BY support_id,summary,open_date,user_name LIMIT $offset,26";
	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit support.summary LIKE '%");

	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if ( !$result || $rows < 1) {
		$no_rows = 1;
		echo "<H2>No matches found for $words</H2>";
		echo db_error();
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo "<H3>Search results for $words</H3><P>\n";

		$title_arr = array();
		$title_arr[] = 'SR Summary';
		$title_arr[] = 'Submitted By';
		$title_arr[] = 'Date';

		echo html_build_list_table_top ($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"\n<TR class=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/support/?group_id=$group_id&func=detailsupport&support_id="
				. db_result($result, $i, "support_id")."\"><IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> "
				. db_result($result, $i, "summary")."</A></TD>"
				. "<TD>".db_result($result, $i, "user_name")."</TD>"
				. "<TD>".format_date($sys_datefmt,db_result($result,$i,"open_date"))."</TD></TR>";
		}
		echo "</TABLE>\n";
	}

} else if ($type_of_search == 'tasks') {

	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit project_task.details LIKE '%");
	$words2=implode($array,"%' $crit project_task.summary LIKE '%");

	$sql =	"SELECT project_task.project_task_id,project_task.group_project_id,project_task.summary,"
	    . "project_task.start_date,project_task.end_date,user.user_name "
		. "FROM project_group_list,project_task,user "
		. "WHERE user.user_id=project_task.created_by AND ((project_task.details LIKE '%$words1%') "
		. "OR (project_task.summary LIKE '%$words2%')) "
	        . "AND (project_task.group_project_id=project_group_list.group_project_id AND project_group_list.group_id='$group_id') "
		.  "GROUP BY project_task_id,summary,start_date,user_name LIMIT $offset,26";
	//echo "DBG: $sql<br>";

	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if ( !$result || $rows < 1) {
		$no_rows = 1;
		echo "<H2>No matches found for $words</H2>";
		echo db_error();
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo "<H3>Search results for $words</H3><P>\n";

		$title_arr = array();
		$title_arr[] = 'Task Summary';
		$title_arr[] = 'Created By';
		$title_arr[] = 'Start Date';
		$title_arr[] = 'End Date';

		echo html_build_list_table_top ($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"\n<TR class=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/pm/task.php?group_id=$group_id&func=detailtask&project_task_id="
				. db_result($result, $i, "project_task_id")
			    ."&group_project_id=".db_result($result, $i, "group_project_id")."\"><IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> "
				. db_result($result, $i, "summary")."</A></TD>"
				. "<TD>".db_result($result, $i, "user_name")."</TD>"
			        . "<TD>".format_date($sys_datefmt,db_result($result, $i, "start_date"))."</TD>"
				. "<TD>".format_date($sys_datefmt,db_result($result,$i,"end_date"))."</TD></TR>";
		}
		echo "</TABLE>\n";
	}

} else if ($type_of_search == 'snippets') {

	/*
		If multiple words, separate them and put LIKE in between
	*/
	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit name LIKE '%");
	$words2=implode($array,"%' $crit description LIKE '%");

	/*
		Query to find software
	*/
	$sql = "SELECT name,snippet_id,description ".
		"FROM snippet ".
		"WHERE ((name LIKE '%$words1%') OR (description LIKE '%$words2%')) LIMIT $offset,26";
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
		$title_arr[] = 'Snippet Name';
		$title_arr[] = 'Description';

		echo html_build_list_table_top($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"<TR class=\"". html_get_alt_row_color($i)."\"><TD><A HREF=\"/snippet/detail.php?type=snippet&id=".db_result($result, $i, 'snippet_id')."\">"
				. "<IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> ".db_result($result, $i, 'name')."</A></TD>"
				. "<TD>".db_result($result,$i,'description')."</TD></TR>\n";
		}
		echo "</TABLE>\n";
	}

} else {

	echo "<H1>Invalid Search - ERROR!!!!</H1>";

}

   // This code puts the nice next/prev.
if ( !$no_rows && ( ($rows_returned > $rows) || ($offset != 0) ) ) {

	echo "<BR>\n";

	echo "<TABLE class=\"boxitem\" WIDTH=\"100%\" CELLPADDING=\"5\" CELLSPACING=\"0\">\n";
	echo "<TR>\n";
	echo "\t<TD ALIGN=\"left\">";
	if ($offset != 0) {
		echo "<span class=\"normal\"><B>";
		echo "<A HREF=\"javascript:history.back()\"><B><IMG SRC=\"".util_get_image_theme('t2.png')."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE> Previous Results </A></B></span>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD>\n\t<TD ALIGN=\"right\">";
	if ( $rows_returned > $rows) {
		echo "<span class=\"normal\"><B>";
		echo "<A HREF=\"/search/?type_of_search=$type_of_search&words=".urlencode($words)."&offset=".($offset+25);
		if ( $type_of_search == 'bugs' ) {
			echo "&group_id=$group_id&is_bug_page=1";
		} 
		if ( $type_of_search == 'forums' ) {
			echo "&forum_id=$forum_id&is_forum_page=1";
		}
		echo "\"><B>Next Results <IMG SRC=\"".util_get_image_theme('t.png')."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE></A></B></span>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD>\n</TR>\n";
	echo "</TABLE>\n";
}



$HTML->footer(array());
?>
