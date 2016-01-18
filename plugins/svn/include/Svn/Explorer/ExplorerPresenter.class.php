<?php

/**
 * Copyright (c) Enalean, 2015-2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Svn\Explorer;

use CSRFSynchronizerToken;
use \Tuleap\Svn\Repository\RepositoryManager;

class ExplorerPresenter {

    public $group_id;
    public $csrf_input;
    public $repository_name;
    public $title_list_repositories;
    public $list_repositories;
    public $label_repository_name;
    public $no_repositories;
    public $svn_plugin;
    public $title;
    public $has_respositories;
    public $help_repository_name;
    public $table_head_list_repository;

    public function __construct(
        $project,
        CSRFSynchronizerToken $csrf,
        $repository_name,
        RepositoryManager $repository_manager
    ) {
        $this->group_id                   = $project->getID();
        $this->csrf_input                 = $csrf->fetchHTMLInput();
        $this->repository_name            = $repository_name;
        $this->list_repositories          = $repository_manager->getRepositoriesInProject($project);
        $this->svn_plugin                 = SVN_BASE_URL;
        $this->title_list_repositories    = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'title_list_repositories');
        $this->label_repository_name      = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'label_name');
        $this->no_repositories            = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'no_repositories');
        $this->title                      = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'title_add_repository');
        $this->help_repository_name       = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'name_repository_length');
        $this->table_head_list_repository = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'table_head_list_repository');
        $this->has_respositories          = count($this->list_repositories) > 0;
    }
}