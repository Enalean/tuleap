<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "vote_function.php";
$HTML->header(array("title"=>"New File Releases"));


function build_new_release_query ($start_time, $offset) {
	$query	= "SELECT groups.group_name AS group_name,"
	. "groups.group_id AS group_id,"
	. "groups.unix_group_name AS unix_group_name,"
	. "groups.short_description AS short_description,"
	. "groups.license AS license,"
	. "user.user_name AS user_name,"
	. "user.user_id AS user_id,"
	. "frs_release.release_id AS release_id,"
	. "frs_release.name AS release_version,"
	. "frs_release.release_date AS release_date,"
	. "frs_release.released_by AS released_by,"
	. "frs_package.name AS module_name, "
	. "frs_dlstats_grouptotal_agg.downloads AS downloads "
	. "FROM groups,user,frs_package,frs_release,frs_dlstats_grouptotal_agg "
	. "WHERE ( frs_release.release_date > $start_time "
	. "AND frs_release.package_id = frs_package.package_id "
	. "AND frs_package.group_id = groups.group_id "
	. "AND frs_release.released_by = user.user_id "
	. "AND frs_package.group_id = frs_dlstats_grouptotal_agg.group_id "
	. "AND frs_release.status_id=1 ) "
	. "GROUP BY frs_release.release_id "
	. "ORDER BY frs_release.release_date DESC LIMIT $offset,21";

	return($query);
}

if ( !$offset || $offset < 0 ) {
	$offset = 0;
}

// For expediancy, list only the filereleases in the past three days.
//LJ $start_time = time() - (7 * 86400);
$start_time = time() - (14 * 86400);

$query = build_new_release_query($start_time, 0);
$res_new = db_query( $query );


// LJ In case there is less than 4 releases in the last N days
// LJ then display the last ones regardless of how old they are
// LJ We don't want an empty list when CodeX started and there
// is little activity.
if (!$res_new || db_numrows($res_new) < 4) {
	$start_time = 0;
	$query = build_new_release_query(0, 0);
	$res_new = db_query( $query );
}

//LJ Modified by LJ. If there is exactly 0 no new
//LJ release then it's not an error
//LJ
echo '<h2>New Releases '.help_button('TheCodeXMainMenu.html#NewReleases').'</h2>';
if (!$res_new || db_numrows($res_new) < 1) {
	if (!$res_new) {
		echo $query . "<BR><BR>"	;
		echo db_error();
		echo "<H2>No new releases found. DB error.</H2>";
	} else {
		echo "<H2>No new releases found. </H2>";
	}
} else {

	if ( db_numrows($res_new) > 20 ) {
		$rows = 20;
	} else {
		$rows = db_numrows($res_new);
	}

	print "\t<TABLE width=100% cellpadding=0 cellspacing=0 border=0>";
	for ($i=0; $i<$rows; $i++) {
		$row_new = db_fetch_array($res_new);
		// avoid dupulicates of different file types
		if (!($G_RELEASE["$row_new[group_id]"])) {
			print "<TR valign=top>";
			print "<TD colspan=2>";
			print "<A href=\"/projects/$row_new[unix_group_name]/\"><B>$row_new[group_name]</B></A>"
				. "\n</TD><TD nowrap><I>Released by: <A href=\"/users/$row_new[user_name]/\">"
				. "$row_new[user_name]</A></I></TD></TR>\n";	

			print "<TR><TD>Module: $row_new[module_name]</TD>\n";
			print "<TD>Version: $row_new[release_version]</TD>\n";
			print "<TD>" . date("M d, h:iA",$row_new[release_date]) . "</TD>\n";
			print "</TR>";

			print "<TR valign=top>";
			print "<TD colspan=2>&nbsp;<BR>";
			if ($row_new[short_description]) {
				print "<I>$row_new[short_description]</I>";
			} else {
				print "<I>This project has not submitted a description.</I>";
			}
			// print "<P>Release rating: ";
			// print vote_show_thumbs($row_new[filerelease_id],2);
			print "</TD>";
			print '<TD align=center nowrap border=1>';
			// print '&nbsp;<BR>Rate this Release!<BR>';
			// print vote_show_release_radios($row_new[filerelease_id],2);
			print "&nbsp;</TD>";
			print "</TR>";

			print '<TR><TD colspan=3>';
			// link to whole file list for downloads
			print "&nbsp;<BR><A href=\"/project/showfiles.php?group_id=$row_new[group_id]&release_id=$row_new[release_id]\">";
			print "Download</A> ";
			print '(Project Total: '.$row_new[downloads].') | ';
			// notes for this release
			print "<A href=\"/project/shownotes.php?release_id=".$row_new[release_id]."\">";
			print "Notes & Changes</A>";
			print '<HR></TD></TR>';

			$G_RELEASE["$row_new[group_id]"] = 1;
		}
	}

	echo "<TR class=\"newproject\"><TD>";
        if ($offset != 0) {
		echo "<B>";
        	echo "<A HREF=\"/new/?offset=".($offset-20)."\"><B><IMG SRC=\"".util_get_image_theme("t2.png")."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE> Newer Releases</A></B>";
        } else {
        	echo "&nbsp;";
        }

	echo "</TD><TD COLSPAN=\"2\" ALIGN=\"RIGHT\">";
	if (db_numrows($res_new)>$rows) {
		echo "<B>";
		echo "<A HREF=\"/new/?offset=".($offset+20)."\"><B>Older Releases <IMG SRC=\"".util_get_image_theme("t.png")."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE></A></B>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD></TR></TABLE>";

}

$HTML->footer(array());

?>
