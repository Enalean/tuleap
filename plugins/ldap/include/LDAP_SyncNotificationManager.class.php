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

    private $projectManager;
    private $retentionPeriod;
    private $logger;

    function __construct(ProjectManager $projectManager, $retentionPeriod){
        $this->projectManager  = $projectManager;
        $this->retentionPeriod = $retentionPeriod;
        $this->logger          = new BackendLogger();
    }

    /**
     * Retrieve a collection of active projects the non valid user is member of.
     *
     * @param PFUser $user Suspended user after LDAP daily synchro
     *
     * @return Array
     */
    protected function getProjectsForUser(PFUser $user) {
        return $this->projectManager->getActiveProjectsForUser($user);
    }

    /**
     * Retrieve the emails of administrators by project the user is member of.
     *
     * @param PFUser $user Suspended user after LDAP daily synchro
     *
     * @return Array
     */
    protected function getNotificationRecipients(PFUser $user) {
        $projectList = $this->getProjectsForUser($user);
        $recipient   = array();
        foreach ($projectList as $project) {
            $projectRecipient = array();
            $projectAdmins    = $project->getAdmins();
            $projectName      = $project->getPublicName();
            foreach($projectAdmins as $admin) {
                $projectRecipient[$admin->getId()] = $admin->getEmail();
            }
            $recipient[$projectName] = $projectRecipient;
        }
        return $recipient;
    }

    /**
     * Process admin notification while traversing the list of project the suspended user belong to.
     *
     * @param PFUser $user Suspended user after LDAP daily synchro.
     *
     * @return void
     */
    public function processNotification(PFUser $user) {
        $to = '';
        $adminsEmails = $this->getNotificationRecipients($user);
        foreach ($adminsEmails as $projectName => $emailList) {
            $to = implode(";", $emailList);
            $this->notifyProjectsAdmins($to, $projectName, $user);
        }
    }

    /**
     * Send mail to project administrators after daliy user sync.
     *
     * @param String $to          List of project administrators emails we want to notify
     * @param String $projectName Public name of the project we want to notify its administrators
     * @param PFUser  $user       Suspended user after LDAP daily synchro
     *
     * @return boolean
     */
    private function notifyProjectsAdmins($to, $projectName, $user) {
        $notificationStatus = true;
        try {
            $mail = $this->prepareMail($to, $projectName, $user);
            if (!$mail->send()) {
                $this->logger->error("LDAP daily synchro job has suspended this user ".$user->getRealName()." (".$user->getEmail().", but failed to notify administrators of <$projectName> project :".$e->getMessage());
                $notificationStatus = false;
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->warn("LDAP daily synchro job has suspended this user ".$user->getRealName()." (".$user->getEmail().":".$e->getMessage());
            $notificationStatus = false;
        } catch (Zend_Mail_Exception $e) {
            $this->logger->error("LDAP daily synchro job has suspended this user ".$user->getRealName()." (".$user->getEmail()."), but faced an issue during project administrators notification :".$e->getMessage());
        }
        return $notificationStatus;
    }

    /**
     * Prepare the mail to be sent after daily user sync
     *
     * @param String  $to          List of project administrators emails we want to notify
     * @param Integer $projectName Public name of the project we want to notify its administrators
     * @param PFUser  $user        Suspended user after LDAP daily synchro
     *
     * @return Codendi_Mail
     */
    private function prepareMail($to, $projectName, $user) {
        $mail = new Codendi_Mail();
        $mail->setFrom($GLOBALS['sys_noreply']);
        if (empty($to)) {
            throw new InvalidArgumentException('Cannot send notification without any valid receiver, Perhaps the project <'.$projectName.'> has no administrators.');
        }
        $mail->setSubject($GLOBALS['Language']->getText('plugin_ldap','ldap_sync_mail_notification_subject', array($user->getRealName(), $projectName)));
        $mail->setTo($to);
        $body = $GLOBALS['Language']->getText('plugin_ldap','ldap_sync_mail_notification_body', array($user->getRealName(),$user->getEmail(),$projectName, $this->retentionPeriod));
        $mail->setBody($body);
        return $mail;
    }
}
?>
