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
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\CreateGitlabRepositories;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreatorConfiguration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;

final class CreateGitlabRepositoriesStub implements CreateGitlabRepositories
{
    /**
     * @param GitlabRepositoryIntegration[] $return_values
     */
    private function __construct(private array $return_values, private ?Throwable $exception)
    {
    }

    /**
     * @throws Throwable
     */
    #[\Override]
    public function createGitlabRepositoryIntegration(
        Credentials $credentials,
        GitlabProject $gitlab_project,
        Project $project,
        GitlabRepositoryCreatorConfiguration $configuration,
    ): GitlabRepositoryIntegration {
        if ($this->exception) {
            throw $this->exception;
        }
        if (count($this->return_values) > 0) {
            return array_shift($this->return_values);
        }
        throw new \LogicException('No integration configured');
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveIntegrations(
        GitlabRepositoryIntegration $first_integration,
        GitlabRepositoryIntegration ...$other_integrations,
    ): self {
        return new self([$first_integration, ...$other_integrations], null);
    }

    public static function withGitlabRequestException(): self
    {
        return new self([], new GitlabRequestException(400, 'fail'));
    }

    public static function withGitlabResponseAPIException(): self
    {
        return new self([], new GitlabResponseAPIException('echec'));
    }
}
