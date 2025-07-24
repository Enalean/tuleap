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

use Throwable;
use Tuleap\Gitlab\API\BuildGitlabProjects;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;

final class BuildGitlabProjectsStub implements BuildGitlabProjects
{
    public function __construct(private ?Throwable $exception, private array $gitlab_projects)
    {
    }

    /**
     * @return GitlabProject[]
     * @throws Throwable
     */
    #[\Override]
    public function getGroupProjectsFromGitlabAPI(Credentials $credentials, int $gitlab_group_id): array
    {
        if ($this->exception) {
            throw $this->exception;
        }
        return $this->gitlab_projects;
    }

    public static function buildWithException(Throwable $exception): self
    {
        return new self($exception, []);
    }

    /**
     * @param GitlabProject[] $projects
     */
    public static function withProjects(array $projects): self
    {
        return new self(null, $projects);
    }
}
