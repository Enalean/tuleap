<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Admin;

use PFUser;
use Project;
use ProjectHistoryDao;
use Tuleap\SVN\Notifications\CannotAddUgroupsNotificationException;
use Tuleap\SVN\Notifications\CannotAddUsersNotificationException;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Notifications\UgroupsToNotifyDao;
use Tuleap\SVN\Notifications\UsersToNotifyDao;
use Tuleap\SVNCore\Repository;
use UGroupManager;

class MailNotificationManager
{
    private $dao;
    /**
     * @var UsersToNotifyDao
     */
    private $user_to_notify_dao;
    /**
     * @var UgroupsToNotifyDao
     */
    private $ugroup_to_notify_dao;
    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var NotificationsEmailsBuilder
     */
    private $notifications_emails_builder;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        MailNotificationDao $dao,
        UsersToNotifyDao $user_to_notify_dao,
        UgroupsToNotifyDao $ugroup_to_notify_dao,
        ProjectHistoryDao $project_history_dao,
        NotificationsEmailsBuilder $notifications_emails_builder,
        UGroupManager $ugroup_manager,
    ) {
        $this->dao                          = $dao;
        $this->user_to_notify_dao           = $user_to_notify_dao;
        $this->ugroup_to_notify_dao         = $ugroup_to_notify_dao;
        $this->project_history_dao          = $project_history_dao;
        $this->notifications_emails_builder = $notifications_emails_builder;
        $this->ugroup_manager               = $ugroup_manager;
    }

    /**
     * @throws CannotAddUgroupsNotificationException
     * @throws CannotAddUsersNotificationException
     * @throws CannotCreateMailHeaderException
     */
    public function create(MailNotification $mail_notification)
    {
        $notification_id = $this->dao->create($mail_notification);
        if (! $notification_id) {
            throw new CannotCreateMailHeaderException();
        }

        $mail_notification->setId($notification_id);

        $this->notificationAddUsers($mail_notification);
        $this->notificationAddUgroups($mail_notification);
    }

    public function createWithHistory(MailNotification $mail_notification)
    {
        $notification_id = $this->create($mail_notification);

        $this->logCreateInProjectHistory($mail_notification->getRepository());

        return $notification_id;
    }

    private function logCreateInProjectHistory(Repository $repository)
    {
        $this->logActionWithSnapshot($repository, 'svn_multi_repository_notification_create');
    }

    private function logUpdateInProjectHistory(Repository $repository)
    {
        $this->logActionWithSnapshot($repository, 'svn_multi_repository_notification_update');
    }

    private function logDeleteInProjectHistory(Repository $repository)
    {
        $this->logActionWithSnapshot($repository, 'svn_multi_repository_notification_delete');
    }

    private function logActionWithSnapshot(Repository $repository, $action_name)
    {
        $message = "Repository: " . $repository->getName() . PHP_EOL;

        foreach ($this->getByRepository($repository) as $email_notification) {
            $message .= "Path: " . $email_notification->getPath() . PHP_EOL .
                "Emails: " . $email_notification->getNotifiedMailsAsString() . PHP_EOL .
                "Users: " . $email_notification->getNotifiedUsersAsString() . PHP_EOL .
                "User groups: " . $email_notification->getNotifiedUserGroupsAsString() . PHP_EOL;
        }

        $this->project_history_dao->groupAddHistory(
            $action_name,
            $message,
            $repository->getProject()->getID()
        );
    }

    public function update(MailNotification $email_notification)
    {
        $notification_id = $email_notification->getId();

        if (! $this->user_to_notify_dao->deleteByNotificationId($notification_id)) {
            throw new CannotCreateMailHeaderException();
        }
        if (! $this->ugroup_to_notify_dao->deleteByNotificationId($notification_id)) {
            throw new CannotCreateMailHeaderException();
        }
        if (! $this->dao->updateByNotificationId($email_notification)) {
            throw new CannotCreateMailHeaderException();
        }

        $this->notificationAddUsers($email_notification);
        $this->notificationAddUgroups($email_notification);

        $this->logUpdateInProjectHistory($email_notification->getRepository());
    }

    /**
     * @param MailNotification[] $new_email_notification
     *
     * @throws CannotCreateMailHeaderException
     */
    public function updateFromREST(Repository $repository, array $new_email_notification)
    {
        if (! $this->updateGloballyForRepository($repository->getId(), $new_email_notification)) {
            throw new CannotCreateMailHeaderException();
        }

        $this->logCreateInProjectHistory($repository);
    }

    /**
     * @param                    $repository_id
     * @param MailNotification[] $new_email_notification
     *
     * @return bool|object
     */
    private function updateGloballyForRepository($repository_id, array $new_email_notification)
    {
        $this->dao->da->startTransaction();

        if (! $this->dao->deleteByRepositoryId($repository_id)) {
            $this->dao->da->rollback();
            return false;
        }

        foreach ($new_email_notification as $notification) {
            $notification_id = $this->dao->create($notification);
            if (! $notification_id) {
                $this->dao->da->rollback();
                return false;
            }

            $notification->setId($notification_id);

            try {
                $this->notificationAddUsers($notification);
            } catch (CannotAddUsersNotificationException $e) {
                $this->dao->da->rollback();
                return false;
            }

            try {
                $this->notificationAddUgroups($notification);
            } catch (CannotAddUgroupsNotificationException $e) {
                $this->dao->da->rollback();
                return false;
            }
        }

        return $this->dao->da->commit();
    }

    /**
     * @return MailNotification[]
     */
    public function getByRepository(Repository $repository)
    {
        $mail_notification = [];
        foreach ($this->dao->searchByRepositoryId($repository->getId()) as $row) {
            $mail_notification[] = $this->instantiateFromRow($row, $repository);
        }

        return $mail_notification;
    }

    /**
     * @param $notification_id
     * @return MailNotification
     */
    public function getByIdAndRepository(Repository $repository, $notification_id)
    {
        $row = $this->dao->searchById($notification_id)->getRow();
        return $this->instantiateFromRow($row, $repository);
    }

    /**
     * @param string $path
     *
     * @return MailNotification[]
     */
    public function getByPath(Repository $repository, $path)
    {
        $mail_notification = [];
        foreach ($this->dao->searchByPath($repository->getId(), $path) as $row) {
            $mail_notification[] = $this->instantiateFromRow($row, $repository);
        }

        return $mail_notification;
    }

    /**
     * @param string $path
     * @return MailNotification[]
     */
    public function getByPathStrictlyEqual(Repository $repository, $path)
    {
        $mail_notification = [];
        foreach ($this->dao->searchByPathStrictlyEqual($repository->getId(), $path) as $row) {
            $mail_notification[] = $this->instantiateFromRow($row, $repository);
        }

        return $mail_notification;
    }

    private function instantiateFromRow(array $row, Repository $repository)
    {
        $notification_id = $row['id'];

        return new MailNotification(
            $notification_id,
            $repository,
            $row['svn_path'],
            $this->notifications_emails_builder->transformNotificationEmailsStringAsArray($row['mailing_list']),
            $this->getUsersForNotification($notification_id),
            $this->getUgroupsForNotification($repository->getProject(), $notification_id)
        );
    }

    private function getUsersForNotification($notification_id)
    {
        $users_to_be_notified = [];
        foreach ($this->user_to_notify_dao->searchUsersByNotificationId($notification_id) as $user_row) {
            $users_to_be_notified[] = new PFUser($user_row);
        }

        return $users_to_be_notified;
    }

    private function getUgroupsForNotification(Project $project, $notification_id)
    {
        $ugroups_to_be_notified = [];
        foreach ($this->ugroup_to_notify_dao->searchUgroupsByNotificationId($notification_id) as $ugroup_row) {
            $ugroups_to_be_notified[] = $this->ugroup_manager->instanciateGroupForProject($project, $ugroup_row);
        }

        return $ugroups_to_be_notified;
    }

    public function removeByNotificationId($notification_id)
    {
        if (! $this->user_to_notify_dao->deleteByNotificationId($notification_id)) {
            throw new CannotDeleteMailNotificationException();
        }
        if (! $this->ugroup_to_notify_dao->deleteByNotificationId($notification_id)) {
            throw new CannotDeleteMailNotificationException();
        }
        if (! $this->dao->deleteByNotificationId($notification_id)) {
            throw new CannotDeleteMailNotificationException();
        }
    }

    public function removeByPathWithHistory(MailNotification $email_notification)
    {
        $this->removeByNotificationId($email_notification->getId());
        $this->logDeleteInProjectHistory($email_notification->getRepository());
    }

    /**
     * @throws CannotAddUsersNotificationException
     */
    private function notificationAddUsers(MailNotification $notification): bool
    {
        $users           = $notification->getNotifiedUsers();
        $users_not_added = [];
        foreach ($users as $user) {
            if (! $this->user_to_notify_dao->insert($notification->getId(), $user->getId())) {
                $users_not_added[] = $user->getUserName();
            }
        }

        if (! empty($users_not_added)) {
            throw new CannotAddUsersNotificationException(implode(',', $users_not_added));
        }

        return empty($users_not_added);
    }

    /**
     * @return bool
     *
     * @throws CannotAddUgroupsNotificationException
     */
    private function notificationAddUgroups(MailNotification $notification)
    {
        $ugroups           = $notification->getNotifiedUgroups();
        $ugroups_not_added = [];
        foreach ($ugroups as $ugroup) {
            if (! $this->ugroup_to_notify_dao->insert($notification->getId(), $ugroup->getId())) {
                $ugroups_not_added[] = $ugroup->getTranslatedName();
            }
        }

        if (! empty($ugroups_not_added)) {
            throw new CannotAddUgroupsNotificationException($ugroups_not_added);
        }

        return empty($ugroups_not_added);
    }

    /**
     * @param $notification_id
     * @param $form_path
     * @return bool
     */
    public function isAnExistingPath(Repository $repository, $notification_id, $form_path)
    {
        foreach ($this->getByPathStrictlyEqual($repository, $form_path) as $notification) {
            if ($notification->getId() !== $notification_id) {
                return true;
            }
        }
        return false;
    }
}
