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

declare(strict_types=1);

namespace Tuleap\Gitlab\REST;

use ForgeConfig;
use Project;

require_once __DIR__ . '/../../../../tests/lib/DatabaseInitialisation.class.php';

final class DatabaseInitialization extends \DatabaseInitialization
{
    public function setUp(Project $gitlab_integration_project): void
    {
        $this->mysqli->select_db(ForgeConfig::get('sys_dbname'));

        echo "Setup GitLab REST tests configuration\n";

        $this->insertGitlabRepository($gitlab_integration_project);
        $this->insertGitlabGroup($gitlab_integration_project);
    }

    private function insertGitlabRepository(Project $gitlab_integration_project): void
    {
        $project_id = (int) $gitlab_integration_project->getID();

        $sql = <<<EOSQL
        INSERT INTO plugin_gitlab_repository_integration (gitlab_repository_id, name, description, gitlab_repository_url, last_push_date, project_id, allow_artifact_closure)
        VALUES (15412, 'path/repo01', 'desc', 'https://example.com/path/repo01', 1603371803, $project_id, 0)
        EOSQL;

        $this->mysqli->real_query($sql);
    }

    private function insertGitlabGroup(Project $gitlab_integration_project): void
    {
        $project_id = (int) $gitlab_integration_project->getID();

        $sql = <<<EOSQL
        INSERT INTO plugin_gitlab_group (gitlab_group_id, project_id, name, full_path, web_url, last_synchronization_date, allow_artifact_closure, create_branch_prefix)
        VALUES (965, $project_id, 'myGroup01', 'path/myGroup01', 'https://example.com/path/myGroup01', 1663662113, 1, 'dev/')
        EOSQL;

        $this->mysqli->real_query($sql);
    }
}
