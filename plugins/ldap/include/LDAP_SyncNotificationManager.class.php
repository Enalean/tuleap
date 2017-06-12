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


class LDAP_SyncNotificationManager {

    private $retentionPeriod;

    function __construct(ProjectManager $projectManager, $retentionPeriod){
        $this->ldapSyncMail    = new LDAP_SyncMail($projectManager);
        $this->retentionPeriod = $retentionPeriod;
    }

    /**
     * Process admin notification while traversing the list of project the suspended user belong to.
     *
     * @param PFUser $user Suspended user after LDAP daily synchro.
     *
     * @return void
     */
    public function processNotification(PFUser $user) {
        $recipients   = '';
        $adminsEmails = $this->ldapSyncMail->getNotificationRecipients($user);
        foreach ($adminsEmails as $projectName => $emailList) {
            $subject    = $this->getSubject($projectName, $user);
            $body       = $this->getBody($projectName, $user);
            $recipients = implode(";", $emailList);
            $this->ldapSyncMail->notifyProjectsAdmins($recipients, $projectName, $user, $subject, $body);
        }
    }

    /**
     * Prepare the body of the notification mail
     *
     * @param Integer $projectName Public name of the project we want to notify its administrators
     * @param PFUser  $user        Suspended user after LDAP daily synchro
     *
     * @return String
     */
    private function getBody($projectName, $user) {
        $server_url       = HTTPRequest::instance()->getServerUrl();
        $project_url      = $server_url.'/projects/'.urlencode($projectName);
        return $GLOBALS['Language']->getText('plugin_ldap','ldap_sync_mail_notification_body', array($user->getRealName(),$user->getEmail(),$project_url, $projectName,$this->retentionPeriod));
    }

    /**
     * Prepare the subject of the notification mail
     *
     * @param Integer $projectName Public name of the project we want to notify its administrators
     * @param PFUser  $user        Suspended user after LDAP daily synchro
     *
     * @return String
     */
    private function getSubject($projectName, $user) {
        return  $GLOBALS['Language']->getText('plugin_ldap','ldap_sync_mail_notification_subject', array($user->getRealName(), $projectName));
    }
}
?>
