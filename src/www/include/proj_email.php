<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 


function send_new_project_email(Project $project) {
    $ugroup_manager = new UGroupManager();
    $admin_ugroup   = $ugroup_manager->getUGroup($project, ProjectUGroup::PROJECT_ADMIN);

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
    //needed by new_user_email.txt
    $base_url = get_server_url();

    include($GLOBALS['Language']->getContent('include/new_user_email'));

    $mail = new Mail();
    $mail->setTo($to);
    $mail->setSubject($GLOBALS['Language']->getText('include_proj_email','account_register',$GLOBALS['sys_name']));
    $mail->setBody($message);
    $mail->setFrom($GLOBALS['sys_noreply']);

    return $mail->send();
}