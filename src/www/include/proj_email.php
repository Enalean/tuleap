<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

//require_once('pre.php');
require_once('common/mail/Mail.class.php');
require_once('common/include/URL.class.php');

$Language->loadLanguageMsg('include/include');

function send_new_project_email($group_id) {
  global $Language;

	$res_grp = db_query("SELECT * FROM groups WHERE group_id='$group_id'");

	if (db_numrows($res_grp) < 1) {
	  echo $Language->getText('include_proj_email','g_not_exist',$group_id);
	}

	$row_grp = db_fetch_array($res_grp);

	$res_admins = db_query("SELECT user.user_name,user.email FROM user,user_group WHERE "
		. "user.user_id=user_group.user_id AND user_group.group_id='$group_id' AND "
		. "user_group.admin_flags='A'");

	$nb_recipients = db_numrows($res_admins);
    if ($nb_recipients < 1) {
		echo $Language->getText('include_proj_email','no_admin',$group_id);;
	}

	// send one email per admin
    $nb_mail_failed = 0;
	while ($row_admins = db_fetch_array($res_admins)) {

        $server = get_server_url();
        $p =& project_get_object($group_id);
        $host = $GLOBALS['sys_default_domain'];
        if ($p && $p->usesService('svn')) {
           $sf =& new ServerFactory();
           if ($server =& $sf->getServerById($p->services['svn']->getServerId())) {
               $host = URL::getHost($server->getUrl(session_issecure()));
           }
        }
        if ($GLOBALS['sys_force_ssl']) {
           $svn_url = 'https://'. $host;
        } else {
           $svn_url = 'http://svn.'. $row_grp['unix_group_name'] .'.'. $host;
        }
        $svn_url .= '/svnroot/'. $row_grp['unix_group_name'];
        // $message is defined in the content file
        include($Language->getContent('include/new_project_email'));
    
        // LJ Uncomment to test
        //echo $message; return
    
        $mail =& new Mail();
        $mail->setTo($row_admins['email']);
        $mail->setSubject($GLOBALS['sys_name'].' '.$Language->getText('include_proj_email','proj_approve',$row_grp['unix_group_name']));
        $mail->setBody($message);
        $mail->setFrom($GLOBALS['sys_email_admin']);
        if (!$mail->send()) {
            $nb_mail_failed++;
        }
    }
    return ($nb_mail_failed < $nb_recipients);
}

//
// send mail notification to new registered user
//
function send_new_user_email($to,$confirm_hash)
{
    global $Language;
    $base_url = get_server_url();

    // $message is defined in the content file
    include($Language->getContent('include/new_user_email'));
    
    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
    $mail =& new Mail();
    $mail->setTo($to);
    $mail->setSubject($Language->getText('include_proj_email','account_register',$GLOBALS['sys_name']));
    $mail->setBody($message);
    $mail->setFrom($GLOBALS['sys_noreply']);
    return $mail->send();
}

// LJ To test the new e-mail message content and format
// LJ uncomment the code below and above and invoke 
// LJ http://codex.xerox.com/include/proj_email.php
// LJ from your favorite browser
//LJ
//echo "<PRE>";
//send_new_project_email(4);
//send_new_project_email(102);
//send_new_user_email("nicolas.terray@xrce.xerox.com", "hash");
//echo "</PRE>";
?>
