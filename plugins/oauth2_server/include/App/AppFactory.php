<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\App;

use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2ServerCore\App\OAuth2App;

class AppFactory
{
    public const string PLUGIN_APP = 'plugin_oauth2';

    /**
     * @var AppDao
     */
    private $app_dao;
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    public function __construct(AppDao $app_dao, \ProjectManager $project_manager)
    {
        $this->app_dao         = $app_dao;
        $this->project_manager = $project_manager;
    }

    /**
     * @return OAuth2App[]
     */
    public function getAppsForProject(\Project $project): array
    {
        $apps = [];
        $rows = $this->app_dao->searchByProject($project, self::PLUGIN_APP);
        foreach ($rows as $row) {
            $apps[] = new OAuth2App(
                $row['id'],
                $row['name'],
                $row['redirect_endpoint'],
                (bool) $row['use_pkce'],
                $project
            );
        }
        return $apps;
    }

    /**
     * @return OAuth2App[]
     */
    public function getSiteLevelApps(): array
    {
        $apps = [];
        $rows = $this->app_dao->searchSiteLevelApps(self::PLUGIN_APP);
        foreach ($rows as $row) {
            $apps[] = new OAuth2App(
                $row['id'],
                $row['name'],
                $row['redirect_endpoint'],
                (bool) $row['use_pkce'],
                null,
            );
        }
        return $apps;
    }

    /**
     * @return OAuth2App[]
     */
    public function getAppsAuthorizedByUser(\PFUser $user): array
    {
        $apps = [];
        $rows = $this->app_dao->searchAuthorizedAppsByUser($user, self::PLUGIN_APP);
        foreach ($rows as $row) {
            $project = null;
            if ($row['project_id'] !== null) {
                try {
                    $project = $this->project_manager->getValidProject($row['project_id']);
                } catch (\Project_NotFoundException $e) {
                    // Skip apps with invalid projects. Apps from those projects are not listed
                    continue;
                }
            }
            $apps[] = new OAuth2App($row['id'], $row['name'], $row['redirect_endpoint'], (bool) $row['use_pkce'], $project);
        }

        return $apps;
    }
}
