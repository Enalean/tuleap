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

require_once __DIR__ . '/DatabaseInitialization.php';

use PluginManager;
use Project;
use REST_TestDataBuilder;

class GitLabDataBuilder extends REST_TestDataBuilder
{
    public const PROJECT_TEST_GITLAB_SHORTNAME = 'test-gitlab';

    public function setUp()
    {
        PluginManager::instance()->installAndActivate('gitlab');

        $gitlab_integration_project = $this->getGitlabIntegrationProject();
        $this->insertFakeGitlabRepository($gitlab_integration_project);
    }

    private function getGitlabIntegrationProject(): Project
    {
        return $this->project_manager->getProjectByUnixName(self::PROJECT_TEST_GITLAB_SHORTNAME);
    }

    private function insertFakeGitlabRepository(Project $gitlab_integration_project)
    {
        $initializer = new DatabaseInitialization();
        $initializer->setUp($gitlab_integration_project);
    }
}
