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

require_once 'pre.php';

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>1, 'admin_flags'=>'A'));

$request =& HTTPRequest::instance();

//define white lists for parameters
$destinationWhiteList = array('comm', 'sf', 'all', 'admin', 'sfadmin', 'devel');
$submitWhiteList      = array('Submit', 'Cancel');

//valid parameters

//valid destination
$validDestination = new Valid('destination');
$validDestination->addRule(new Rule_WhiteList($destinationWhiteList));

$destination = '';
if ($request->valid($validDestination)) {
    $destination = $request->get('destination');

} else {
     exit_error($Response->addFeedback('error', 'A destination is requied'), '');
}

//valid mail subject

$validMailSubject = new Valid_String('mail_subject');
$validMailSubject->required();
$mailSubject = '';
if ($request->valid($validMailSubject)) {
    $mailSubject = $request->get('mail_subject');
    $mailSubject = htmlentities($mailSubject);
 
} else {
     exit_error($Response->addFeedback('error', 'A subject is requied'), '');
}

//valid mail message
$validMailMessage = new Valid('mail_message');
$validMailMessage->required();
$mailMessage = '';
if ($request->valid($validMailMessage)) {
    $mailMessage = $request->get('mail_message');
    $mailMessage = htmlentities($mailMessage);
 
} else {
    exit_error($Response->addFeedback('error', 'A message is requied'), '');
}

//valid submit
$validSubmit = new Valid('Submit');
$validSubmit->addRule(new Rule_WhiteList($submitWhiteList));

if ($request->valid($validSubmit)) {
    $submit = $request->get('Submit');

} else {
    $Response->addFeedback('error', 'Your data are not valid');
}

switch ($destination) {
case 'comm': 
    $res_mail = db_query("SELECT email,user_name FROM user ".
                         "WHERE ( status='A' OR status='R' ) ".
                         "AND mail_va=1 GROUP BY lcase(email)");
    $to_name  = 'Additional Community Mailings Subcribers';
    break;
case 'sf':
    $res_mail = db_query("SELECT email,user_name FROM user ".
                         "WHERE ( status='A' OR status='R' ) ".
                         "AND mail_siteupdates=1 GROUP BY lcase(email)");
    $to_name  = 'Site Updates Subcribers';
    break;
case 'all':
    $res_mail = db_query("SELECT email,user_name FROM user ".
                         "WHERE ( status='A' OR status='R' ) ".
                         "GROUP BY lcase(email)");
    $to_name  = 'All Users';
    break;
case 'admin':
    $res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name ".
                         "FROM user,user_group ".
                         "WHERE user.user_id=user_group.user_id ".
                         "AND ( user.status='A' OR user.status='R' ) ".
                         "AND user_group.admin_flags='A' ".
                         "GROUP by lcase(email)");
    $to_name  = 'Project Administrators';
    break;
case 'sfadmin':
    $res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name ".
                         "FROM user,user_group ".
                         "WHERE user.user_id=user_group.user_id ".
                         "AND ( user.status='A' OR user.status='R' ) ".
                         "AND user_group.group_id=1 ".
                         "GROUP by lcase(email)");
    $to_name  = $GLOBALS['sys_name'].' Administrators';
    break;
case 'devel':
    $res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name ".
                         "FROM user,user_group ".
                         "WHERE user.user_id=user_group.user_id ".
                         "AND ( user.status='A' OR user.status='R' ) ".
                         "GROUP BY lcase(email)");
    $to_name  = 'Project Developers';
    break;
default:
    exit_error($Response->addFeedback('error', 'A destination is requied'), '');
}

if ($destination != '' && $mailSubject != '' && $mailMessage != '') {
 
    $HTML->header(array('title'=>$Language->getText('admin_massmail', 'title')));

    $nbemail = db_numrows($res_mail);

    echo 'You are about to send '.$nbemail.' emails';

    print '<form action="massmail_execute.php" method="post">';

    print '<input type="hidden" name="destination" value="'.$destination.'" />';
    print '<input type="hidden" name="mail_subject" value="'.$mailSubject.'" />';
    print '<input type="hidden" name="mail_message" value="'.$mailMessage.'" />';
    print '<input type="hidden" name="res_mail" value="'.$res_mail.'" />';
    print '<input type="hidden" name="to_name" value="'.$to_name.'"/>';

    print '<input type="submit" name="Submit" value="'.$Language->getText('global', 'btn_submit').'">';
    print '<input type="submit" name="Submit" value="Cancel">';

    print '</form>';
    $HTML->footer(array());
}

?>
