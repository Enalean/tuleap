<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All rights reserved
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

namespace Tuleap\SVN\Explorer;

use CSRFSynchronizerToken;
use Project;
use Tuleap\SVN\Repository\RuleName;

class ExplorerPresenter
{
    public $group_id;
    public $csrf_token;
    public $repository_name;
    public $title_list_repositories;
    public $label_repository_name;
    public $no_repositories;
    public $help_repository_name;
    public $table_head_list_repository;
    public $create_repository;
    public $last_commit;
    public $validate_name;
    public $is_admin;
    public $settings_label;

    /**
     * @var array
     */
    public $repository_list;
    public $settings_button;

    public function __construct(
        Project $project,
        CSRFSynchronizerToken $csrf,
        $repository_name,
        array $repository_list,
        $is_admin
    ) {
        $this->group_id                   = $project->getID();
        $this->csrf_token                 = $csrf;
        $this->repository_name            = $repository_name;
        $this->title_list_repositories    = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'title_list_repositories');
        $this->label_repository_name      = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'label_name');
        $this->no_repositories            = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'no_repositories');
        $this->help_repository_name       = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'name_repository_length');
        $this->table_head_list_repository = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'table_head_list_repository');
        $this->create_repository          = $GLOBALS['Language']->getText('plugin_svn_manage_repository', 'create_repository');
        $this->last_commit                = dgettext('tuleap-svn', 'Last commit');

        $this->validate_name = RuleName::PATTERN_REPOSITORY_NAME;

        $this->is_admin        = $is_admin;
        $this->repository_list = $repository_list;
        $this->settings_label  = dgettext('tuleap-svn', 'Access to settings');
        $this->settings_button = dgettext('tuleap-svn', 'Settings');
    }
}
