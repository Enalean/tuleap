<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

if (!$offset || $offset < 0) {
	$offset=0;
}


// get project name
$sql = "SELECT unix_group_name from groups where group_id=$group_id";

$result = db_query($sql);
$projectname = db_result($result, 0, 'unix_group_name');

if (!user_isloggedin()) {
  echo "Impossible to enter admin page without an admin user of the project";

} else {

  echo """ have to propose the following admin features :
<BL><LI>create(rebuild) commit database</LI>
<LI>update commit database</LI>
<LI>toggle automatic update of commits at checkin time</LI>
</BL>"""
