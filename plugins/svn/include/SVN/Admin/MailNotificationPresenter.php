<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use Tuleap\SVN\Repository\Repository;
use Project;
use CSRFSynchronizerToken;

class MailNotificationPresenter extends BaseAdminPresenter
{

    public $title;
    public $notification_subtitle;
    public $comment;
    public $project_id;
    public $label_subject_header;
    public $subject_header;
    public $disabled;
    public $save_subject;
    public $label_path;
    public $path;
    public $label_mail_to;
    public $mail_to;
    public $repo_id;
    public $csrf_input;
    public $csrf_mailing_list;
    public $csrf_input_delete;
    public $repository_name;
    public $repository_full_name;
    public $no_notifications_message;
    public $list_mails;
    public $has_notifications;
    public $notifications;

    public $remove_notification_title;
    public $remove_notification_desc;
    public $remove_notification_confirm;
    public $edit;
    public $save;
    public $cancel;
    public $delete;
    public $new_notification_placeholder;
    public $cannot_save_title;
    public $cannot_save_desc;

    public function __construct(
        Repository $repository,
        Project $project,
        CSRFSynchronizerToken $token,
        $title,
        $mail_header,
        array $notifications
    ) {
        parent::__construct();

        $this->project_id           = $project->getId();
        $this->repository_id        = $repository->getId();
        $this->csrf_input           = $token->fetchHTMLInput();
        $this->subject_header       = $mail_header->getHeader();
        $this->title                = $title;
        $this->repository_name      = $repository->getName();
        $this->repository_full_name = $repository->getFullName();
        $this->notification_active  = true;
        $this->notifications        = $notifications;
        $this->has_notifications    = (count($notifications) > 0);

        $this->notification_subtitle      = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'notification_subtitle');
        $this->comment                    = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'comment');
        $this->label_subject_header       = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'label_subject_header');
        $this->save_subject               = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'save_subject');
        $this->label_path                 = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'label_path');
        $this->label_mail_to              = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'label_mail_to');
        $this->label_notification_aviable = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'available_notifications');
        $this->monitored_path             = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'monitored_path');
        $this->notified_mails             = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'notified_mails');
        $this->no_notifications_message   = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'no_notifications_message');
        $this->add_notification           = $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'add_notification');

        $this->remove_notification_title    = dgettext('tuleap-svn', 'Wait a minute...');
        $this->remove_notification_desc     = dgettext('tuleap-svn', 'You are about to remove the notification. Please confirm your action.');
        $this->remove_notification_confirm  = dgettext('tuleap-svn', 'Confirm deletion');
        $this->edit                         = dgettext('tuleap-svn', 'Edit');
        $this->save                         = dgettext('tuleap-svn', 'Save');
        $this->cancel                       = dgettext('tuleap-svn', 'Cancel');
        $this->delete                       = dgettext('tuleap-svn', 'Delete');
        $this->new_notification_placeholder = dgettext('tuleap-svn', 'User, group, email');
        $this->cannot_save_title            = dgettext('tuleap-svn', 'Be careful');
        $this->cannot_save_desc             = dgettext(
            'tuleap-svn',
            'A notification already exists for this path. You cannot save it. Please change the path or update the existing notification.'
        );

        $this->sections = new SectionsPresenter($repository);
    }
}
