<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Test\Stubs;

use Project;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\Repository\CreateGitlabRepositories;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreatorConfiguration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class CreateGitlabRepositoriesStub implements CreateGitlabRepositories
{
    private function __construct(private GitlabRepositoryIntegration $integrations)
    {
    }


    public function createGitlabRepositoryIntegration(
        Credentials $credentials,
        GitlabProject $gitlab_project,
        Project $project,
        GitlabRepositoryCreatorConfiguration $configuration,
    ): GitlabRepositoryIntegration {
        return $this->integrations;
    }

    public static function buildWithDefault(): self
    {
        return new self(
            new GitlabRepositoryIntegration(
                1,
                2,
                "name",
                "desc",
                "repo_url",
                new \DateTimeImmutable('@0'),
                ProjectTestBuilder::aProject()->build(),
                false
            )
        );
    }
}
