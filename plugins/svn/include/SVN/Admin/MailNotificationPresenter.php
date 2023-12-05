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

use Tuleap\SVNCore\Repository;
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
        array $notifications,
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

        $this->notification_subtitle      = dgettext('tuleap-svn', 'Email notifications on commits');
        $this->comment                    = dgettext('tuleap-svn', 'Each commit event can also be notified via email to specific recipients or mailing lists. A specific subject header for the email message can also be specified. Please note that you can use the star operator: /folder/to/notify and /folder/*/notify will be notified the same way.');
        $this->label_subject_header       = dgettext('tuleap-svn', 'Subject header');
        $this->save_subject               = dgettext('tuleap-svn', 'Save the subject');
        $this->label_path                 = dgettext('tuleap-svn', 'Path');
        $this->label_mail_to              = dgettext('tuleap-svn', 'Mail to specific recipients or mailing lists (comma separated)');
        $this->label_notification_aviable = dgettext('tuleap-svn', 'Active notifications list');
        $this->monitored_path             = dgettext('tuleap-svn', 'Monitored path');
        $this->notified_mails             = dgettext('tuleap-svn', 'Notification list');
        $this->no_notifications_message   = dgettext('tuleap-svn', 'There is no notification');
        $this->add_notification           = dgettext('tuleap-svn', 'Add notification');

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
