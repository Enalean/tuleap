<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Svn\Repository\Repository;
use ProjectManager;
use Project;
use Rule_Email;

class MailNotificationManager {

    private $dao;

    public function __construct(MailNotificationDao $dao) {
        $this->dao = $dao;
    }

    public function create(MailNotification $mail_notification) {
        if (! $this->dao->create($mail_notification)) {
            throw new CannotCreateMailHeaderException ($GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_header_fail'));
        }
    }

    public function update($old_path, MailNotification $email_notification)
    {
        if (! $this->dao->updateByRepositoryIdAndPath($old_path, $email_notification)) {
            throw new CannotCreateMailHeaderException ($GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_header_fail'));
        }
    }

    public function getByRepository(Repository $repository) {
        $mail_notification = array();
        foreach ($this->dao->searchByRepositoryId($repository->getId()) as $row) {
            $mail_notification[] = $this->instantiateFromRow($row, $repository);
        }

        return $mail_notification;
    }

    public function getByPath(Repository $repository, $path) {
        $mail_notification = array();
        foreach ($this->dao->searchByPath($repository->getId(), $path) as $row) {
            $mail_notification[] = $this->instantiateFromRow($row, $repository);
        }

        return $mail_notification;
    }

    public function instantiateFromRow(array $row, Repository $repository) {
        return new MailNotification(
            $repository,
            $row['mailing_list'],
            $row['svn_path']
        );
    }

    public function removeSvnNotification(Repository $repository, $selected_paths) {
        if (is_array($selected_paths) && !empty($selected_paths)) {
            foreach ($selected_paths as $path_to_delete) {
                if (! $this->dao->deleteSvnMailingList($repository->getId(), $path_to_delete)) {
                    throw new CannotDeleteMailNotificationException ($GLOBALS['Language']->getText('plugin_svn_admin_notification','delete_error'));
                }
            }
        } else {
            throw new CannotDeleteMailNotificationException ($GLOBALS['Language']->getText('plugin_svn_admin_notification','delete_error'));
        }
    }
}
