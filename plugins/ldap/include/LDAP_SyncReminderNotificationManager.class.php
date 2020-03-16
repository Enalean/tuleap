<?php
/**
 * Copyright (c) STMicroelectronics, 2014. All Rights Reserved.
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


class LDAP_SyncReminderNotificationManager
{

    private $userManager;
    private $projectManager;

    public function __construct(ProjectManager $projectManager, UserManager $userManager)
    {
        $this->userManager  = $userManager;
        $this->ldapSyncMail = new LDAP_SyncMail($projectManager);
        $this->projectManager  = $projectManager;
    }

    /**
     * Retrieve the list of users being deleted tomorrow.
     *
     * @return Array
     */
    private function getUsersToBeDeleted()
    {
        $suspendedUsers     = array();
        $suspendedUsersList = $this->getLDAPDirectoryCleanUpDao()->getUsersDeletedTomorrow();
        foreach ($suspendedUsersList as $suspendedUser) {
            $user = $this->userManager->getUserById($suspendedUser["user_id"]);
            if (!$user->isDeleted()) {
                $suspendedUsers[] = $user;
            }
        }
        return $suspendedUsers;
    }

    /**
     * Process admin notification while traversing the list of project the suspended user belong to.
     *
     * @param PFUser $user Suspended user after LDAP daily synchro.
     *
     * @return void
     */
    public function processReminder(PFUser $user)
    {
        $to = '';
        if ($user->getStatus() == 'S') {
            $adminsEmails = $this->ldapSyncMail->getNotificationRecipients($user);
            foreach ($adminsEmails as $unixProjectName => $emailList) {
                $subject = $this->getSubject($unixProjectName, $user);
                $body    = $this->getBody($unixProjectName, $user);
                $to      = implode(";", $emailList);
                $this->ldapSyncMail->notifyProjectsAdmins($to, $unixProjectName, $user, $subject, $body);
            }
        }
    }

    /**
     * Process clean up reminders for all suspended users being deleted at a given forecast date.
     *
     * @return void
     */
    public function processReminders()
    {
        $suspendedUsersList = $this->getUsersToBeDeleted();
        foreach ($suspendedUsersList as $suspendedUser) {
            $this->processReminder($suspendedUser);
        }
    }

    /**
     * Wrapper for LDAP_DirectoryCleanUpDao object
     *
     * @return LDAP_DirectoryCleanUpDao
     */
    private function getLDAPDirectoryCleanUpDao()
    {
        return new LDAP_DirectoryCleanUpDao(CodendiDataAccess::instance());
    }


    /**
     * Prepare the body of the notification mail
     *
     * @param String  $unixProjectName  Unix name of the project we want to notify its administrators
     * @param PFUser  $user             Suspended user after LDAP daily synchro
     *
     * @return String
     */
    private function getBody($unixProjectName, $user)
    {
        $server_url       = HTTPRequest::instance()->getServerUrl();
        $project_url      = $server_url . '/projects/' . urlencode($unixProjectName);
        $project = $this->projectManager->getProjectByUnixName($unixProjectName);
        $publicProjectName = $project->getPublicName();
        $purifiedPublicProjectName = Codendi_HTMLPurifier::instance()->purify($publicProjectName, CODENDI_PURIFIER_LIGHT);
        return $GLOBALS['Language']->getText('plugin_ldap', 'ldap_sync_reminder_mail_notification_body', array($user->getRealName(), $user->getEmail(), $project_url, $purifiedPublicProjectName, ForgeConfig::get('sys_name')));
    }

    /**
     * Prepare the subject of the notification mail
     *
     * @param String  $projectName Public name of the project we want to notify its administrators
     * @param PFUser  $user        Suspended user after LDAP daily synchro
     *
     * @return String
     */
    private function getSubject($projectName, $user)
    {
        return  $GLOBALS['Language']->getText('plugin_ldap', 'ldap_sync_reminder_mail_notification_subject', array($user->getRealName(), $projectName));
    }
}
