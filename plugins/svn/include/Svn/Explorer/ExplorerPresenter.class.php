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
use \Tuleap\Svn\ServiceSvn;

class ExplorerPresenter {

    public $group_id;
    public $csrf_input;
    public $repository_name;
    public $title_list_repositories;
    public $list_repositories;
    public $label_repository_name;
    public $no_repositories;

    public function __construct(
        $project,
        CSRFSynchronizerToken $csrf,
        $repository_name,
        RepositoryManager $repository_manager
    ) {
        $this->group_id                = $project->getID();
        $this->csrf_input              = $csrf->fetchHTMLInput();
        $this->repository_name         = $repository_name;
        $this->list_repositories       = $repository_manager->getRepositoriesInProject($project);
    }

    public function title() {
        return $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'title_add_repository');
    }

    public function label_repository_name() {
        return $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'label_name');
    }

    public function no_repositories() {
        return $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'no_repositories');
    }

    public function title_list_repositories() {
        return $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'title_list_repositories');
    }

    public function has_respositories() {
        return count($this->list_repositories) > 0;
    }

    public function help_repository_name() {
        return $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'name_repository_length');
    }

    public function table_head_list_repository() {
        return $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'table_head_list_repository');
    }
}
