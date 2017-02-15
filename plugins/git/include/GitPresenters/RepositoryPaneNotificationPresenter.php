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

class RepositoryPaneNotificationPresenter
{

    private $repository;
    private $identifier;

    public function __construct(GitRepository $repository, $identifier)
    {
        $this->repository = $repository;
        $this->identifier = $identifier;
    }

    public function title()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_mail');
    }

    public function identifier()
    {
        return $this->identifier;
    }

    public function repository_project_id()
    {
        return $this->repository->getProjectId();
    }

    public function repository_id()
    {
        return $this->repository->getId();
    }

    public function mail_prefix_label()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'mail_prefix_label');
    }

    public function mail_prefix()
    {
        return $this->repository->getMailPrefix();
    }

    public function notified_mails_title()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'notified_mails_title');
    }

    public function list_of_mails()
    {
        $i             = 0;
        $list_of_mails = array();

        foreach ($this->repository->getNotifiedMails() as $mail) {
            $list_of_mails[] = array(
                'color' => html_get_alt_row_color(++$i),
                'mail'  => $mail
            );
        }

        return $list_of_mails;
    }

    public function has_mails()
    {
        return count($this->repository->getNotifiedMails()) > 0;
    }

    public function add_mail_title()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'add_mail_title');
    }

    public function add_mail_msg()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'add_mail_msg');
    }

    public function btn_submit()
    {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }

    public function notified_people()
    {
        return dgettext('tuleap-git', 'Notified people');
    }
}
