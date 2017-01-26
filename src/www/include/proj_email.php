<?php
// Copyright (c) Enalean, 2015. All Rights Reserved.
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

function send_new_project_email(Project $project) {
    $ugroup_manager = new UGroupManager();
    $admin_ugroup   = $ugroup_manager->getUGroup($project, ProjectUGroup::PROJECT_ADMIN);

    foreach ($admin_ugroup->getMembers() as $user) {
        /* @var $user PFUser */
        $language = $user->getLanguage();
        $subject = $GLOBALS['sys_name'] . ' ' . $language->getText('include_proj_email', 'proj_approve', $project->getUnixName());
        $presenter = new MailPresenterFactory();

        $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
        $mail = new TuleapRegisterMail($presenter, $renderer, "mail-project-register");
        $mail = $mail->getMailProject($subject, $GLOBALS['sys_noreply'], $user->getEmail(), $project);
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
    $mail = $mail->getMail($login, $confirm_hash, $base_url, $GLOBALS['sys_noreply'], $to, "user");
    return $mail->send();
}

function send_admin_new_user_email($to, $login)
{
    //needed by new_user_email.txt
    $base_url  = get_server_url();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail-admin");
    $mail = $mail->getMail($login, '', $base_url, $GLOBALS['sys_noreply'], $to, "admin");
    return $mail->send();
}

function send_new_user_email_notification($to, $login) {
    //needed by new_user_email.txt
    $base_url  = get_server_url();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail-notification");
    $mail = $mail->getMail($login, '', $base_url, $GLOBALS['sys_noreply'], $to, "admin-notification");
    return $mail->send();
}

function send_approval_new_user_email($to, $login) {
    //needed by new_user_email.txt
    $base_url  = get_server_url();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail-admin-approval");
    $mail = $mail->getMail($login, '', $base_url, $GLOBALS['sys_noreply'], $to, "admin-approval");
    return $mail->send();
}
