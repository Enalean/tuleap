<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Arnaud Salvucci, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once('pre.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>1,'admin_flags'=>'A'));

$destination = '';
$validDestination = new Valid_String('destination');
if($request->valid($validDestination)) {
    $destination = $request->get('destination');
 }
 else {
     $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
 }

switch ($destination) {
	case 'comm': 
		$res_mail = db_query("SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) AND mail_va=1 GROUP BY lcase(email)");
		$to_name = 'Additional Community Mailings Subcribers';
		break;
	case 'sf':
		$res_mail = db_query("SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) AND mail_siteupdates=1 GROUP BY lcase(email)");
		$to_name = 'Site Updates Subcribers';
		break;
	case 'all':
		$res_mail = db_query("SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) GROUP BY lcase(email)");
		$to_name = 'All Users';
		break;
	case 'admin':
		$res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
		."FROM user,user_group WHERE "	
		."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.admin_flags='A' "
		."GROUP by lcase(email)");
		$to_name = 'Project Administrators';
		break;
	case 'sfadmin':
		$res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
		."FROM user,user_group WHERE "	
		."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.group_id=1 "
		."GROUP by lcase(email)");
		$to_name = $GLOBALS['sys_name'].' Administrators';
		break;
	case 'devel':
		$res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
		."FROM user,user_group WHERE "
		."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) GROUP BY lcase(email)");
		$to_name = 'Project Developers';
		break;
	default:
		exit_error('Unrecognized Post','cannot execute');
}

$nbemail = db_numrows($res_mail);

echo 'You are about to send '.$nbemail.' emails';

?>
