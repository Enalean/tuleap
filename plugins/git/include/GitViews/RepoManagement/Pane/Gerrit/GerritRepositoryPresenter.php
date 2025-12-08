<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit;

use Git_Driver_Gerrit;
use Git_RemoteServer_GerritServer;
use GitRepository;

final readonly class GerritRepositoryPresenter
{
    public string $repository_name;
    public string $gerrit_project_name;
    public string $gerrit_project_admin_url;
    public string $gerrit_server_url;
    public bool $is_delete_plugin_enabled;

    public function __construct(
        GitRepository $repository,
        Git_Driver_Gerrit $driver,
        Git_RemoteServer_GerritServer $gerrit_server,
    ) {
        $this->repository_name          = $repository->getName();
        $this->gerrit_project_name      = $driver->getGerritProjectName($repository);
        $this->gerrit_project_admin_url = $gerrit_server->getProjectAdminUrl($this->gerrit_project_name);
        $this->gerrit_server_url        = $gerrit_server->getBaseUrl();
        $this->is_delete_plugin_enabled = $driver->isDeletePluginEnabled($gerrit_server);
    }
}
