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


    /**
     * @var GitRepository
     */
    private $repository;

    private $jenkins_server_url;
    public $jobs;

    public function __construct(GitRepository $repository, $jenkins_server_url, array $jobs)
    {
        $this->repository         = $repository;
        $this->jenkins_server_url = $jenkins_server_url;
        $this->jobs               = $jobs;
    }

    public function project_id()
    {
        return $this->repository->getProjectId();
    }

    public function pane_identifier()
    {
        return GitViews_RepoManagement_Pane_Hooks::ID;
    }

    public function repository_id()
    {
        return $this->repository->getId();
    }

    public function save_label() {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_save_submit');
    }

    public function jenkins_notification_label()
    {
        return $GLOBALS['Language']->getText('plugin_hudson_git', 'settings_hooks_jenkins_notification_label');
    }

    public function jenkins_server()
    {
        return $this->jenkins_server_url;
    }

    public function jenkins_notification_desc()
    {
        return $GLOBALS['Language']->getText('plugin_hudson_git', 'settings_hooks_jenkins_notification_desc');
    }

    public function jenkins_documentation_link_label()
    {
        return $GLOBALS['Language']->getText('plugin_hudson_git', 'settings_hooks_jenkins_link_label');
    }

    public function label_push_date()
    {
        return $GLOBALS['Language']->getText('plugin_hudson_git', 'label_push_date');
    }

    public function label_triggered()
    {
        return $GLOBALS['Language']->getText('plugin_hudson_git', 'label_triggered');
    }

    public function empty_jobs() {
        return $GLOBALS['Language']->getText('plugin_hudson_git', 'empty_jobs');
    }
}
