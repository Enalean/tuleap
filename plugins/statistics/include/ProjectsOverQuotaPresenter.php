<?php
/**
 * Copyright (c) STMicroelectronics 2014. All rights reserved
 * Copyright (c) Enalean 2017. All rights reserved
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

namespace Tuleap\Statistics;

class ProjectsOverQuotaPresenter
{
    /**
     * @var AdminHeaderPresenter
     */
    public $header;
    public $table_content;
    public $title;
    public $exceeding_projects;
    public $project_name;
    public $current_size;
    public $quota;
    public $exceeding_size;
    public $warn_administrators;
    public $no_projects_warning;
    public $modal_title;
    public $action_url;
    public $submit_button;
    public $close_button;
    public $subject_label;
    public $body_label;

    public function __construct(AdminHeaderPresenter $header, array $exceeding_projects)
    {
        $this->title                = dgettext('tuleap-statistics', 'Projects exceeding their disk quota');
        $this->project_name         = dgettext('tuleap-statistics', 'Project name');
        $this->current_size         = dgettext('tuleap-statistics', 'Current project size');
        $this->quota                = dgettext('tuleap-statistics', 'Quota');
        $this->exceeding_size       = dgettext('tuleap-statistics', 'Exceeding size');
        $this->warn_administrators  = dgettext('tuleap-statistics', 'Warn administrators');
        $this->no_projects_warning  = dgettext('tuleap-statistics', 'There isn\'t any matching projects.');
        $this->filter_project_quota = dgettext('tuleap-statistics', 'Filter');

        $this->submit_button = $GLOBALS['Language']->getText('global', 'btn_submit');
        $this->close_button  = $GLOBALS['Language']->getText('global', 'btn_cancel');
        $this->subject_label = $GLOBALS['Language']->getText('my_index', 'subject_label');
        $this->body_label    = $GLOBALS['Language']->getText('my_index', 'body_label');
        $this->modal_title   = $GLOBALS['Language']->getText('my_index', 'mass_mail_label');

        $this->action_url = '/include/massmail_to_project_admins.php';

        $this->exceeding_projects = $exceeding_projects;
        $this->header             = $header;
    }
}
