<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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


class LDAP_SyncMail
{

    private $projectManager;
    private $logger;

    public function __construct(ProjectManager $projectManager)
    {
        $this->logger         = new \Tuleap\LDAP\LdapLogger();
        $this->projectManager = $projectManager;
    }

    /**
     * Retrieve a collection of active projects the non valid user is member of.
     *
     * @param PFUser $user Suspended user after LDAP daily synchro
     *
     * @return Array
     */
    private function getProjectsForUser(PFUser $user)
    {
        return $this->projectManager->getActiveProjectsForUser($user);
    }

    /**
     * Retrieve the emails of administrators by project the user is member of.
     *
     * @param PFUser $user Suspended user after LDAP daily synchro
     *
     * @return Array
     */
    public function getNotificationRecipients(PFUser $user)
    {
        $projectList = $this->getProjectsForUser($user);
        $recipient   = array();
        foreach ($projectList as $project) {
            $projectRecipient = array();
            $projectAdmins    = $project->getAdmins();
            $unixProjectName      = $project->getUnixName();
            foreach ($projectAdmins as $admin) {
                $projectRecipient[$admin->getId()] = $admin->getEmail();
            }
            $recipient[$unixProjectName] = $projectRecipient;
        }
        return $recipient;
    }

    /**
     * Send mail to project administrators after daliy user sync.
     *
     * @param String  $recipients       List of project administrators emails we want to notify
     * @param String  $unixProjectName  Unix name of the project we want to notify its administrators
     * @param PFUser  $user             Suspended user after LDAP daily synchro
     * @param String  $subject          The subject of the notification mail
     * @param String  $body             The content of the notification mail
     *
     * @return bool
     */
    public function notifyProjectsAdmins($recipients, $unixProjectName, $user, $subject, $body)
    {
        $notificationStatus = true;
        try {
            $mail = $this->prepareMail($recipients, $unixProjectName, $subject, $body);
            if (! $mail->send()) {
                $this->logger->error("LDAP daily synchro job has suspended this user " . $user->getRealName() . " (" . $user->getEmail() . ", but failed to notify administrators of <$unixProjectName> project (unix name)");
                $notificationStatus = false;
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->warning("LDAP daily synchro job has suspended this user " . $user->getRealName() . " (" . $user->getEmail() . ":" . $e->getMessage());
            $notificationStatus = false;
        }
        return $notificationStatus;
    }

    /**
     * Prepare the mail to be sent after daily user sync
     *
     * @param String  $recipients       List of project administrators emails we want to notify
     * @param int $unixProjectName Unix name of the project we want to notify its administrators
     * @param String  $subject          The subject of the notification mail
     * @param String  $body             The content of the notification mail
     *
     * @return Codendi_Mail
     */
    protected function prepareMail($recipients, $unixProjectName, $subject, $body)
    {
        $mail = new Codendi_Mail();
        $mail->setFrom($GLOBALS['sys_noreply']);
        if (empty($recipients)) {
            throw new InvalidArgumentException('Cannot send notification without any valid receiver, Perhaps the project <' . $unixProjectName . '> (unix name) has no administrators.');
        }
        $mail->setSubject($subject);
        $mail->setTo($recipients);
        $mail->setBody($body);
        return $mail;
    }
}
