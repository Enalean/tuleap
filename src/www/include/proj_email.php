<?php
// Copyright (c) Enalean, 2015. All Rights Reserved.
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 
require_once 'www/themes/FlamingParrot/FlamingParrot_Theme.class.php';

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

function send_new_user_email($to, $login, $confirm_hash) {
    //needed by new_user_email.txt
    $base_url  = get_server_url();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail");
    $mail = $mail->getMail($login, '', $confirm_hash, $base_url, $GLOBALS['sys_noreply'], $to, "user");
    return $mail->send();
}

function send_admin_new_user_email($to, $login, $password) {
    //needed by new_user_email.txt
    $base_url  = get_server_url();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail-admin");
    $mail = $mail->getMail($login, $password, '', $base_url, $GLOBALS['sys_noreply'], $to, "admin");
    return $mail->send();
}

function send_new_user_email_notification($to, $login) {
    //needed by new_user_email.txt
    $base_url  = get_server_url();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail-notification");
    $mail = $mail->getMail($login, '', '', $base_url, $GLOBALS['sys_noreply'], $to, "admin-notification");
    return $mail->send();
}

function send_approval_new_user_email($to, $login) {
    //needed by new_user_email.txt
    $base_url  = get_server_url();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail-admin-approval");
    $mail = $mail->getMail($login, '', '', $base_url, $GLOBALS['sys_noreply'], $to, "admin-approval");
    return $mail->send();
}
