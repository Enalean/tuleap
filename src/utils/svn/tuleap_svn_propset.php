<?php

//Copyright (c) Enalean, 2013. All Rights Reserved.
//
//This file is a part of Tuleap.
//
//Tuleap is free software; you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation; either version 2 of the License, or
//(at your option) any later version.
//
//Tuleap is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
//

include_once("pre.php");

$repository = $argv[1];
$rev   = $argv[2];
// retrieve the group name from repository
$unix_group_name = substr($repository, strlen($GLOBALS['svn_prefix'])+1);
$group_id = group_getid_by_name($unix_group_name);
//svnlook log /svnroot/nap -r1
$logmsg = array();
exec("/usr/bin/svnlook log '$repository' -r '$rev'", $logmsg);
$logmsg = implode("\n", $logmsg);

$query = "UPDATE svn_commits SET description='$logmsg'".
         "WHERE group_id='$group_id' AND revision='$revision'";

?>