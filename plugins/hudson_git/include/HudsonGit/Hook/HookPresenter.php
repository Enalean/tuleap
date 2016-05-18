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

namespace Tuleap\HudsonGit\Hook;

use GitRepository;
use GitViews_RepoManagement_Pane_Hooks;

class HookPresenter
{
    public $jobs;
    public $jenkins_server_url;
    public $has_a_jenkins_hook;
    public $has_hooks;
    public $project_id;
    public $repository_id;
    public $save_label;
    public $jenkins_notification_label;
    public $jenkins_notification_desc;
    public $jenkins_documentation_link_label;
    public $label_push_date;
    public $label_triggered;
    public $empty_jobs;
    public $empty_hooks;
    public $jenkins_hook;

    public function __construct(GitRepository $repository, $jenkins_server_url, array $jobs)
    {
        $this->jenkins_server_url = $jenkins_server_url;
        $this->jobs               = $jobs;

        $this->has_a_jenkins_hook = $this->jenkins_server_url;
        $this->has_hooks          = $this->jenkins_server_url;

        $this->project_id    = $repository->getProjectId();
        $this->repository_id = $repository->getId();

        $this->save_label      = $GLOBALS['Language']->getText('plugin_git', 'admin_save_submit');
        $this->label_push_date = $GLOBALS['Language']->getText('plugin_hudson_git', 'label_push_date');
        $this->label_triggered = $GLOBALS['Language']->getText('plugin_hudson_git', 'label_triggered');
        $this->empty_jobs      = $GLOBALS['Language']->getText('plugin_hudson_git', 'empty_jobs');
        $this->empty_hooks     = $GLOBALS['Language']->getText('plugin_hudson_git', 'empty_hooks');
        $this->jenkins_hook    = $GLOBALS['Language']->getText('plugin_hudson_git', 'jenkins_hook');

        $this->jenkins_notification_label       = $GLOBALS['Language']->getText('plugin_hudson_git', 'settings_hooks_jenkins_notification_label');
        $this->jenkins_notification_desc        = $GLOBALS['Language']->getText('plugin_hudson_git', 'settings_hooks_jenkins_notification_desc');
        $this->jenkins_documentation_link_label = $GLOBALS['Language']->getText('plugin_hudson_git', 'settings_hooks_jenkins_link_label');
    }
}
