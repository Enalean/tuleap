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

namespace Tuleap\Git\GitPresenters;

use GitRepository;

class RepositoryPaneNotificationPresenter
{
    public $identifier;
    public $users_to_be_notified;
    public $groups_to_be_notified;

    public function __construct(
        GitRepository $repository,
        $identifier,
        array $users_to_be_notified,
        array $groups_to_be_notified
    ) {
        $this->identifier            = $identifier;
        $this->users_to_be_notified  = $users_to_be_notified;
        $this->groups_to_be_notified = $groups_to_be_notified;
        $this->list_of_mails         = $this->buildListOfMailsPresenter($repository);
        $this->has_notifications     = count($this->list_of_mails) > 0
            || count($users_to_be_notified) > 0
            || count($groups_to_be_notified) > 0;

        $this->repository_project_id = $repository->getProjectId();
        $this->repository_id         = $repository->getId();
        $this->mail_prefix           = $repository->getMailPrefix();

        $this->title                = dgettext('tuleap-git', 'Notifications');
        $this->mail_prefix_label    = dgettext('tuleap-git', 'Notification Prefix');
        $this->notified_mails_title = dgettext('tuleap-git', 'List of notified mails');
        $this->btn_submit           = $GLOBALS['Language']->getText('global', 'btn_submit');
        $this->notified_people      = dgettext('tuleap-git', 'Notified people');
        $this->empty_notification   = dgettext('tuleap-git', 'No notifications set');
        $this->placeholder          = dgettext('tuleap-git', 'User, group, email');
    }

    private function buildListOfMailsPresenter(GitRepository $repository)
    {
        $list_of_mails = [];

        foreach ($repository->getNotifiedMails() as $mail) {
            $list_of_mails[] = [
                'mail' => $mail
            ];
        }

        return $list_of_mails;
    }
}
