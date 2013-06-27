<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

//require_once('pre.php');
require_once('common/mail/Mail.class.php');
require_once('common/include/URL.class.php');


function send_new_project_email(Project $project) {
    $ugroup_manager = new UGroupManager();
    $admin_ugroup   = $ugroup_manager->getUGroup($project, UGroup::PROJECT_ADMIN);

    $mail_manager   = new MailManager();

    $hp = Codendi_HTMLPurifier::instance();

    foreach ($admin_ugroup->getMembers() as $user) {
        /* @var $user PFUser */
        $language = $user->getLanguage();
        $subject = $GLOBALS['sys_name'] . ' ' . $language->getText('include_proj_email', 'proj_approve', $project->getUnixName());
        $message = '';
        include($language->getContent('include/new_project_email', null, null, '.php'));

        $mail = $mail_manager->getMailByType('html');
        $mail->getLookAndFeelTemplate()->set('title', $hp->purify($subject, CODENDI_PURIFIER_CONVERT_HTML));
        $mail->setTo($user->getEmail());
        $mail->setSubject($subject);
        $mail->setBodyHtml($message);
        $mail->send();
    }
    return true;
}

//
// send mail notification to new registered user
//
function send_new_user_email($to,$confirm_hash, $username)
{
    global $Language;
    $base_url = get_server_url();

    // $message is defined in the content file
    
    include($Language->getContent('include/new_user_email'));
    
    $host_part = explode(':',$GLOBALS['sys_default_domain']);
    $host = $host_part[0];

    $mail = new Mail();
    $mail->setTo($to);
    $mail->setSubject($Language->getText('include_proj_email','account_register',$GLOBALS['sys_name']));
    $mail->setBody($message);
    $mail->setFrom($GLOBALS['sys_noreply']);
    return $mail->send();
}

// LJ To test the new e-mail message content and format
// LJ uncomment the code below and above and invoke 
// LJ http://codendi.example.com/include/proj_email.php
// LJ from your favorite browser
//LJ
//echo "<PRE>";
//send_new_project_email(4);
//send_new_project_email(102);
//send_new_user_email("nicolas.terray@xrce.xerox.com", "hash");
//echo "</PRE>";
?>
