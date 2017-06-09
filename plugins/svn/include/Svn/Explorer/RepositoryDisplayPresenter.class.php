<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All rights reserved
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

use HTTPRequest;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\SvnPermissionManager;

class RepositoryDisplayPresenter
{
    private $repository;
    public $settings_button;
    public $is_user_admin;
    public $viewvc_html;
    public $repository_not_created;
    public $is_repository_created;
    public $help_command;
    public $help_message;

    public function __construct(
        Repository $repository,
        HTTPRequest $request,
        $viewvc_html,
        SvnPermissionManager $permissions_manager,
        $username
    ) {
        $this->repository            = $repository;
        $this->help_command          = "svn checkout --username " . $username . " " . $this->repository->getSvnUrl();
        $this->viewvc_html           = $viewvc_html;
        $this->is_user_admin         = $permissions_manager->isAdmin($request->getProject(), $request->getCurrentUser());
        $this->is_repository_created = $repository->isRepositoryCreated();

        $this->help_message           = $GLOBALS['Language']->getText('svn_intro', 'command_intro');
        $this->repository_not_created = $GLOBALS['Language']->getText('plugin_svn', 'repository_not_created');
        $this->settings_button        = dgettext('tuleap-svn', 'Settings');
    }

    public function repository_name()
    {
        return $this->repository->getName();
    }

    public function svn_url()
    {
        return $this->repository->getSvnUrl();
    }

    public function settings_url()
    {
        return $this->repository->getSettingUrl();
    }
}
