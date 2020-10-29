<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All rights reserved
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

namespace Tuleap\Gitlab\REST;

use ForgeConfig;
use Project;

require_once __DIR__ . '/../../../../tests/lib/DatabaseInitialisation.class.php';

class DatabaseInitialization extends \DatabaseInitialization
{
    public function setUp(Project $gitlab_integration_project)
    {
        $this->mysqli->select_db(ForgeConfig::get('sys_dbname'));
        $this->insertGitlabRepository($gitlab_integration_project);
    }

    private function insertGitlabRepository(Project $gitlab_integration_project)
    {
        echo "Adding fake Gitlab repository \n";

        $sql = "INSERT INTO plugin_gitlab_repository (gitlab_id, name, path, description, full_url, last_push_date)
                VALUES (15412, 'repo01', 'path/repo01', 'desc', 'https://example.com/path/repo01', 1603371803)";

        $this->mysqli->real_query($sql);

        $project_id = (int) $gitlab_integration_project->getID();
        $sql = "INSERT INTO plugin_gitlab_repository_project (id, project_id)
                VALUES (1, $project_id)";

        $this->mysqli->real_query($sql);
    }
}
