<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"About Foundries"));
?>

<P>
<h2>About SourceForge Foundries</h2>

SourceForge Foundries serve as places for developers to congregate, share
expertise and news, get and give advice, and generally help each other
develop  better software faster (this is Open Source, after all).<br>

&nbsp;<br>

If you're interested in volunteering to help support or start a foundry, or
have suggestions, ideas, or gripes please contact Jim Kingdon 
&lt;<a href="mailto:kingdon@sourceforge.net">kingdon@sourceforge.net</a>&gt;,
SourceForge content and community relations manager.

<h2>Foundries</h2>

<p>The following four foundries are fully functional.  We're
especially interested in hearing from people who want to contribute to
them or offer ideas about how they could be more helpful:</p>

<ul>

<li><a href="/foundry/java/" >Java</a> programming language
<li><a href="/foundry/printing/" >Printing</a> (postscript/ghostscript, libppd, gnome-print, Qt, CUPS, and more)
<li><a href="/foundry/3d/" >3D</a> graphics (Mesa, OpenGL, &amp;c)
<li><a href="/foundry/games/" >games</a> (from text classics to the latest 3D games)

</ul>

<h2>Foundries in testing</h2>

<p>Here is a list of all foundries on the system.  Other than the four
mentioned above, they should be considered alpha test:

<?php
	$query = "SELECT group_name,unix_group_name ".
		 "FROM groups WHERE status='A' AND is_public='1' ".
		 " AND type='2' ORDER BY group_name ";
	$result = db_query($query);
	$rows = db_numrows($result);
	if (!$result || $rows < 1) {
		echo "<H2>No matches found</H2>";
		echo db_error();
	} else {
		echo "<UL>";
		for ($i=0; $i<$rows; $i++) {
			echo "\n<li><A HREF=\"/foundry/".db_result($result, $i, 'unix_group_name')."/\">".
				db_result($result, $i, 'group_name')."</A></li>";
		}
		echo "\n</UL>";
	}
?>

<p>Alpha test foundries may not be completely working and all those
usual kinds of disclaimers.</p>

<?php
$HTML->footer(array());

?>
