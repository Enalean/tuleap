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

function send_new_user_email($to, $login, $password, $confirm_hash, $template, $isAdminPresenter) {
    //needed by new_user_email.txt
    $base_url = get_server_url();

    $defaultTheme = $GLOBALS['sys_themedefault'];
    $color_logo   = "#0000";
    $color_button = "#347DBA";

    if (themeIsFlamingParrot($defaultTheme)) {
        $defaultThemeVariant = $GLOBALS['sys_default_theme_variant'];
        $color_logo          = FlamingParrot_Theme::getColorOfCurrentTheme($defaultThemeVariant);
        $color_button        = $color_logo;
    }

    $logo_url  = $base_url."/themes/".$defaultTheme."/images/organization_logo.png";
    $has_logo  = file_exists(dirname(__FILE__) . '/../themes/'.$defaultTheme.'/images/organization_logo.png');

    if($isAdminPresenter) {
        $subject = $GLOBALS['Language']->getText('account_register', 'welcome_email_title', $GLOBALS['sys_name']);
        include($GLOBALS['Language']->getContent('account/new_account_email'));
        $presenter = new MailRegisterByAdminPresenter(
            $has_logo,
            $logo_url,
            $title,
            $section_one,
            $section_two,
            $section_after_login,
            $thanks,
            $signature,
            $help,
            $color_logo,
            $login,
            $section_three,
            $section_after_password,
            $password
        );
    } else {
        $subject = $GLOBALS['Language']->getText('include_proj_email', 'account_register', $GLOBALS['sys_name']);
        include($GLOBALS['Language']->getContent('include/new_user_email'));
        $redirect_url = $base_url ."/account/verify.php?confirm_hash=$confirm_hash";

        $presenter = new MailRegisterByUserPresenter(
            $has_logo,
            $logo_url,
            $title,
            $section_one,
            $section_two,
            $section_after_login,
            $thanks,
            $signature,
            $help,
            $color_logo,
            $login,
            $redirect_url,
            $redirect_button,
            $color_button
        );
    }

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
    $mail = initializeMail($subject, $GLOBALS['sys_noreply'], $to, $renderer->renderToString($template, $presenter), $message);
    return $mail->send();
}

function initializeMail($subject, $from, $to, $html, $text) {
    $mail = new Codendi_Mail();
    $mail->setSubject($subject);
    $mail->setTo($to);
    $mail->setBodyHtml($html);
    $mail->setBodyText($text);
    $mail->setFrom($from);

    return $mail;
}

function themeIsFlamingParrot($theme) {
    return $theme === 'FlamingParrot';
}
