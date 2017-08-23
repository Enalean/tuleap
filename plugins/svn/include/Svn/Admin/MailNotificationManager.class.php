<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Svn\Admin;

use ProjectHistoryDao;
use Tuleap\Svn\Notifications\CannotAddUgroupsNotificationException;
use Tuleap\Svn\Notifications\CannotAddUsersNotificationException;
use Tuleap\Svn\Notifications\NotificationsEmailsBuilder;
use Tuleap\Svn\Notifications\UgroupsToNotifyDao;
use Tuleap\Svn\Notifications\UsersToNotifyDao;
use Tuleap\Svn\Repository\Repository;

class MailNotificationManager {

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

    public function __construct(
        MailNotificationDao $dao,
        UsersToNotifyDao $user_to_notify_dao,
        UgroupsToNotifyDao $ugroup_to_notify_dao,
        ProjectHistoryDao $project_history_dao,
        NotificationsEmailsBuilder $notifications_emails_builder
    ) {
        $this->dao                          = $dao;
        $this->user_to_notify_dao           = $user_to_notify_dao;
        $this->ugroup_to_notify_dao         = $ugroup_to_notify_dao;
        $this->project_history_dao          = $project_history_dao;
        $this->notifications_emails_builder = $notifications_emails_builder;
    }

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

        $this->logCreateInProjectHistory($mail_notification);

        return $notification_id;
    }

    private function logCreateInProjectHistory(MailNotification $email_notification) {
        $this->project_history_dao->groupAddHistory(
            'svn_multi_repository_notification_create',
            "Repository: " . $email_notification->getRepository()->getName() . PHP_EOL .
            "Path: " . $email_notification->getPath() . PHP_EOL .
            "Emails: " . $email_notification->getNotifiedMailsAsString() . PHP_EOL .
            "Users: " . $email_notification->getNotifiedUsersAsString(),
            $email_notification->getRepository()->getProject()->getID()
        );
    }

    private function logUpdateInProjectHistory(MailNotification $email_notification) {
        $this->project_history_dao->groupAddHistory(
            'svn_multi_repository_notification_update',
            "Repository: " . $email_notification->getRepository()->getName() . PHP_EOL .
            "Path: " . $email_notification->getPath() . PHP_EOL .
            "Emails: " . $email_notification->getNotifiedMailsAsString() . PHP_EOL .
            "Users: " . $email_notification->getNotifiedUsersAsString(),
            $email_notification->getRepository()->getProject()->getID()
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

        $this->logUpdateInProjectHistory($email_notification);
    }

    /**
     * @param Repository         $repository
     * @param MailNotification[] $new_email_notification
     *
     * @throws CannotCreateMailHeaderException
     */
    public function updateFromREST(Repository $repository, array $new_email_notification)
    {
        if (! $this->updateGloballyForRepository($repository->getId(), $new_email_notification)) {
            throw new CannotCreateMailHeaderException();
        }

        foreach($new_email_notification as $notification) {
            $this->logCreateInProjectHistory($notification);
        }
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

        foreach($new_email_notification as $notification) {
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
        }

        return $this->dao->da->commit();
    }

    /**
     * @return MailNotification[]
     */
    public function getByRepository(Repository $repository) {
        $mail_notification = array();
        foreach ($this->dao->searchByRepositoryId($repository->getId()) as $row) {
            $mail_notification[] = $this->instantiateFromRow($row, $repository);
        }

        return $mail_notification;
    }

    /**
     * @param Repository $repository
     * @param $notification_id
     * @return MailNotification
     */
    public function getByIdAndRepository(Repository $repository, $notification_id)
    {
        $row = $this->dao->searchById($notification_id)->getRow();
        return $this->instantiateFromRow($row, $repository);
    }

    /**
     * @param Repository $repository
     * @param string $path
     *
     * @return MailNotification[]
     */
    public function getByPath(Repository $repository, $path) {
        $mail_notification = array();
        foreach ($this->dao->searchByPath($repository->getId(), $path) as $row) {
            $mail_notification[] = $this->instantiateFromRow($row, $repository);
        }

        return $mail_notification;
    }

    /**
     * @param Repository $repository
     * @param string $path
     * @return MailNotification[]
     */
    public function getByPathStrictlyEqual(Repository $repository, $path)
    {
        $mail_notification = array();
        foreach ($this->dao->searchByPathStrictlyEqual($repository->getId(), $path) as $row) {
            $mail_notification[] = $this->instantiateFromRow($row, $repository);
        }

        return $mail_notification;
    }

    public function instantiateFromRow(array $row, Repository $repository) {
        return new MailNotification(
            $row['id'],
            $repository,
            $row['svn_path'],
            $this->notifications_emails_builder->transformNotificationEmailsStringAsArray($row['mailing_list']),
            array(),
            array()
        );
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

    /**
     * @return bool
     *
     * @throws CannotAddUsersNotificationException
     * @return bool
     */
    private function notificationAddUsers(MailNotification $notification)
    {
        $users           = $notification->getNotifiedUsers();
        $users_not_added = array();
        foreach ($users as $user) {
            if (! $this->user_to_notify_dao->insert($notification->getId(), $user->getId())) {
                $users_not_added[] = $user->getName();
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
        $ugroups_not_added = array();
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
     * @param Repository $repository
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
