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
include_once("common/dao/SvnCommitsDao.class.php");

$repository = $argv[1];
$revision   = $argv[2];

// retrieve the group name from repository
$unix_group_name = substr($repository, strlen($GLOBALS['svn_prefix'])+1);
$group_id = group_getid_by_name($unix_group_name);

$logmsg = array();
//svnlook log /svnroot/nap -r1
exec("/usr/bin/svnlook log '$repository' -r '$revision'", $logmsg);
$logmsg = implode("\n", $logmsg);
$dao = new SvnCommitsDao();
$dao->updateCommitMessage($group_id, $revision, $logmsg);

?>