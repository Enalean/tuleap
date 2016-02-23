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
use Project;
use CSRFSynchronizerToken;

class MailNotificationPresenter {

    public $title;
    public $notification_subtitle;
    public $comment;
    public $group_id;
    public $label_subject_header;
    public $subject_header;
    public $disabled;
    public $submit;
    public $label_path;
    public $path;
    public $label_mail_to;
    public $mail_to;
    public $repo_id;
    public $csrf_input;
    public $csrf_mailing_list;
    public $csrf_input_delete;
    public $repository_name;

    public function __construct(
        Repository $repository,
        Project $project,
        CSRFSynchronizerToken $token,
        $title,
        $mail_header,
        $notifications_details
    ) {
        $this->group_id                   = $project->getId();
        $this->repo_id                    = $repository->getId();
        $this->csrf_input                 = $token->fetchHTMLInput();
        $this->subject_header             = $mail_header->getHeader();
        $this->list_mails                 = $notifications_details;
        $this->title                      = $title;
        $this->repository_name            = $repository->getName();

        $this->notification_subtitle      = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'notification_subtitle');
        $this->comment                    = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'comment');
        $this->label_subject_header       = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'label_subject_header');
        $this->submit                     = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'submit');
        $this->label_path                 = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'label_path');
        $this->label_mail_to              = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'label_mail_to');
        $this->label_notification_aviable = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'available_notifications');
        $this->monitored_path             = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'monitored_path');
        $this->notified_mails             = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'notified_mails');
        $this->delete                     = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'delete');

        $this->sections = new SectionsPresenter($repository);
    }

    public function hasNotification() {
        return count($this->list_mails) > 0;
    }
}
