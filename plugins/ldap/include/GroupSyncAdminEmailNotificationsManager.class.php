<?php
/**
 * Copyright Ericsson AB (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\LDAP;

use ForgeConfig;

/**
 * This class is responsible for sending emails of added/removed users to admins
 * */
class GroupSyncAdminEmailNotificationsManager implements GroupSyncNotificationsManager
{
    /**
     * @var \LDAP_UserManager
     * */
    private $ldap_user_manager;

    /**
     * @var \Codendi_Mail
     * */
    private $mail;

    /**
     * @var \TemplateRenderer
     * */
    private $renderer;

    /**
     * @var \UserManager
     * */
    private $user_manager;

    public function __construct(
        \LDAP_UserManager $ldap_user_manager,
        \Codendi_Mail $mail,
        \UserManager $user_manager
    ) {
        $this->ldap_user_manager = $ldap_user_manager;
        $this->mail              = $mail;
        $this->renderer          = \TemplateRendererFactory::build()->getRenderer(LDAP_TEMPLATE_DIR);
        $this->user_manager      = $user_manager;
    }

    /**
     * @param $project   Project subject to the sync
     * @param $to_add    an array of user IDs to be added
     * @param $to_remove an array of suer IDs to be removed
     * @return Void
     * */
    public function sendNotifications(\Project $project, array $to_add, array $to_remove)
    {
        if (count($to_add) == 0 && count($to_remove) == 0) {
            return;
        }

        $to_add = $this->getUsersFromIds($to_add);
        $to_remove = $this->getUsersFromIds($to_remove);

        $admins = $project->getAdmins();
        $project_name = $project->getPublicName();

        $this->sendMailToAdmins($admins, $project, $to_add, $to_remove);
    }

    /**
     * @param array $user_ids
     * @return \PFUser[]
     * */
    private function getUsersFromIds(array $user_ids)
    {
        $users = array();
        foreach ($user_ids as $id) {
            $user = $this->getUserFromId($id);
            if ($user !== null) {
                $users[] = $user;
            }
        }
        return $users;
    }

    private function getUserFromId($id): ?\PFUser
    {
        $user_lr = $this->ldap_user_manager->getLdapFromUserId($id);
        if ($user_lr && (($user = $this->ldap_user_manager->getUserFromLdap($user_lr)) !== false)) {
            return $user;
        }
        return null;
    }

    /**
     * @return string
     * */
    private function getEmailBody(array $to_add, array $to_remove)
    {
        return $this->renderer->renderToString(
            'ldap-group-sync-email',
            new GroupSyncEmailPresenter($to_add, $to_remove)
        );
    }

    /**
     * @return Void
     * */
    private function setLocalizedMailAttributes(\PFUser $receiver, \Project $project, array $to_add, array $to_remove)
    {
        $current_locale = $this->user_manager->getCurrentUser()->getLocale();
        $user_locale = $receiver->getLocale();

        setlocale(LC_CTYPE, "$user_locale.UTF-8");
        setlocale(LC_MESSAGES, "$user_locale.UTF-8");

        $this->mail->setBody($this->getEmailBody($to_add, $to_remove));

        $subject = dgettext('tuleap-ldap', 'LDAP Sync Results for %projectName%');
        $subject = str_replace('%projectName%', $project->getPublicName(), $subject);
        $this->mail->setSubject($subject);

        setlocale(LC_CTYPE, "$current_locale.UTF-8");
        setlocale(LC_MESSAGES, "$current_locale.UTF-8");
    }

    /**
     * @param $admins array of PFUser
     * @return Void
     * */
    private function sendMailToAdmins(array $admins, \Project $project, array $to_add, array $to_remove)
    {
        $mail = $this->mail;

        foreach ($admins as $admin) {
            $mail->setFrom(ForgeConfig::get('sys_noreply'));
            $mail->setTo($admin->getEmail());
            $this->setLocalizedMailAttributes($admin, $project, $to_add, $to_remove);
            $mail->send();
        }
    }
}
