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

use Tuleap\Svn\Notifications\CannotAddUgroupsNotificationException;
use Tuleap\Svn\Notifications\CannotAddUsersNotificationException;
use Tuleap\Svn\Notifications\UgroupsToNotifyDao;
use Tuleap\Svn\Notifications\UsersToNotifyDao;
use Tuleap\Svn\Repository\Repository;
use Tuleap\User\RequestFromAutocompleter;

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

    public function __construct(
        MailNotificationDao $dao,
        UsersToNotifyDao $user_to_notify_dao,
        UgroupsToNotifyDao $ugroup_to_notify_dao
    ) {
        $this->dao                  = $dao;
        $this->user_to_notify_dao   = $user_to_notify_dao;
        $this->ugroup_to_notify_dao = $ugroup_to_notify_dao;
    }

    public function create(MailNotification $mail_notification) {
        $notification_id = $this->dao->create($mail_notification);
        if (! $notification_id) {
            throw new CannotCreateMailHeaderException ();
        }
        return $notification_id;
    }

    public function update(MailNotification $email_notification, RequestFromAutocompleter $autocompleter)
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

        $this->notificationAddUsers($notification_id, $autocompleter);
        $this->notificationAddUgroups($notification_id, $autocompleter);
    }

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
    private function getByPathStrictlyEqual(Repository $repository, $path)
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
            $row['mailing_list'],
            $row['svn_path']
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
     * @param $notification_id
     * @param RequestFromAutocompleter $autocompleter
     * @return bool
     * @throws CannotAddUsersNotificationException
     */
    public function notificationAddUsers($notification_id, RequestFromAutocompleter $autocompleter)
    {
        $users           = $autocompleter->getUsers();
        $users_not_added = array();
        foreach ($users as $user) {
            if (! $this->user_to_notify_dao->insert($notification_id, $user->getId())) {
                $users_not_added[] = $user->getName();
            }
        }

        if (! empty($users_not_added)) {
            throw new CannotAddUsersNotificationException($users_not_added);
        }

        return empty($users_not_added);
    }

    /**
     * @param $notification_id
     * @param RequestFromAutocompleter $autocompleter
     * @return bool
     * @throws CannotAddUgroupsNotificationException
     */
    public function notificationAddUgroups($notification_id, RequestFromAutocompleter $autocompleter)
    {
        $ugroups           = $autocompleter->getUgroups();
        $ugroups_not_added = array();
        foreach ($ugroups as $ugroup) {
            if (! $this->ugroup_to_notify_dao->insert($notification_id, $ugroup->getId())) {
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
