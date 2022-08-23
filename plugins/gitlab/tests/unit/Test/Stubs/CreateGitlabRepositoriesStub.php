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
use Throwable;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\Repository\CreateGitlabRepositories;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreatorConfiguration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;

final class CreateGitlabRepositoriesStub implements CreateGitlabRepositories
{
    private function __construct(private ?Throwable $exception, private array $integrations)
    {
    }

    /**
     * @throws Throwable
     */
    public function integrateGitlabRepositoriesInProject(Credentials $credentials, array $gitlab_projects, Project $project, GitlabRepositoryCreatorConfiguration $configuration,): array
    {
        if ($this->exception) {
            throw $this->exception;
        }

        return $this->integrations;
    }

    public static function buildWithException(Throwable $exception): self
    {
        return new self($exception, []);
    }

    public static function buildWithDefault(): self
    {
        return new self(null, []);
    }

    /**
     * @param GitlabRepositoryIntegration[] $integrations
     */
    public static function buildWithIntegrations(array $integrations): self
    {
        return new self(null, $integrations);
    }
}
