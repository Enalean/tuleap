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

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\SVN\Repository;
use Project;

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
    public $path;
    public $repo_id;
    public $repository_name;
    public $repository_full_name;
    public $no_notifications_message;
    public $has_notifications;
    public $notifications;

    public $remove_notification_confirm;
    public $edit;
    public $save;
    public $cancel;
    public $delete;
    public string $label_notification_aviable;
    public string $monitored_path;
    public string $notified_mails;
    public int $repository_id;
    public SectionsPresenter $sections;

    /**
     * @param list<array{id: int, name: string, selected: bool}> $ugroups
     */
    public function __construct(
        Repository $repository,
        Project $project,
        public CSRFSynchronizerTokenPresenter $csrf_token,
        $title,
        $mail_header,
        public array $ugroups,
        array $notifications,
    ) {
        parent::__construct();

        $this->project_id           = $project->getId();
        $this->repository_id        = $repository->getId();
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
        $this->label_notification_aviable = dgettext('tuleap-svn', 'Active notifications list');
        $this->monitored_path             = dgettext('tuleap-svn', 'Monitored path');
        $this->notified_mails             = dgettext('tuleap-svn', 'Notification list');
        $this->no_notifications_message   = dgettext('tuleap-svn', 'There is no notification');

        $this->remove_notification_confirm = dgettext('tuleap-svn', 'Confirm deletion');
        $this->edit                        = dgettext('tuleap-svn', 'Edit');
        $this->save                        = dgettext('tuleap-svn', 'Save');

        $this->sections = new SectionsPresenter($repository);
    }
}
