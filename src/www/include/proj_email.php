<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) The SourceForge Crew, 1999-2000. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

function send_new_project_email(Project $project)
{
    $ugroup_manager = new UGroupManager();
    $admin_ugroup   = $ugroup_manager->getProjectAdminsUGroup($project);

    foreach ($admin_ugroup->getMembers() as $user) {
        /** @var PFUser $user */
        $language = $user->getLanguage();
        $subject = $GLOBALS['sys_name'] . ' ' . $language->getText('include_proj_email', 'proj_approve', $project->getUnixName());
        $presenter = new MailPresenterFactory();

        $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/mail/');
        $mail = new TuleapRegisterMail($presenter, $renderer, "mail-project-register");
        $mail = $mail->getMailProject($subject, $GLOBALS['sys_noreply'], $user->getEmail(), $project);
        $mail->send();
    }
    return true;
}

function send_new_user_email($to, $login, $confirm_hash)
{
    //needed by new_user_email.txt
    $base_url  = HTTPRequest::instance()->getServerUrl();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail");
    $mail = $mail->getMail($login, $confirm_hash, $base_url, $GLOBALS['sys_noreply'], $to, "user");
    return $mail->send();
}

function send_admin_new_user_email($to, $login)
{
    //needed by new_user_email.txt
    $base_url  = HTTPRequest::instance()->getServerUrl();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail-admin");
    $mail = $mail->getMail($login, '', $base_url, $GLOBALS['sys_noreply'], $to, "admin");
    return $mail->send();
}

function send_new_user_email_notification($to, $login)
{
    //needed by new_user_email.txt
    $base_url  = HTTPRequest::instance()->getServerUrl();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail-notification");
    $mail = $mail->getMail($login, '', $base_url, $GLOBALS['sys_noreply'], $to, "admin-notification");
    return $mail->send();
}

function send_approval_new_user_email($to, $login)
{
    //needed by new_user_email.txt
    $base_url  = HTTPRequest::instance()->getServerUrl();
    $presenter = new MailPresenterFactory();

    $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/mail/');
    $mail = new TuleapRegisterMail($presenter, $renderer, "mail-admin-approval");
    $mail = $mail->getMail($login, '', $base_url, $GLOBALS['sys_noreply'], $to, "admin-approval");
    return $mail->send();
}
