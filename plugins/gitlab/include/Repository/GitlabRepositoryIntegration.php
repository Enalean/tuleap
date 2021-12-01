<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use DateTimeImmutable;
use Project;

/**
 * @psalm-immutable
 */
class GitlabRepositoryIntegration
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $gitlab_repository_id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $gitlab_repository_url;

    /**
     * @var DateTimeImmutable
     */
    private $last_push_date;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var bool
     */
    private $allow_artifact_closure;

    public function __construct(
        int $id,
        int $gitlab_repository_id,
        string $name,
        string $description,
        string $gitlab_repository_url,
        DateTimeImmutable $last_push_date,
        Project $project,
        bool $allow_artifact_closure,
    ) {
        $this->id                     = $id;
        $this->gitlab_repository_id   = $gitlab_repository_id;
        $this->name                   = $name;
        $this->description            = $description;
        $this->gitlab_repository_url  = $gitlab_repository_url;
        $this->last_push_date         = $last_push_date;
        $this->project                = $project;
        $this->allow_artifact_closure = $allow_artifact_closure;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGitlabRepositoryId(): int
    {
        return $this->gitlab_repository_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getGitlabRepositoryUrl(): string
    {
        return $this->gitlab_repository_url;
    }

    public function getGitlabServerUrl(): string
    {
        return str_replace($this->name, "", $this->gitlab_repository_url);
    }

    public function getLastPushDate(): DateTimeImmutable
    {
        return $this->last_push_date;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function isArtifactClosureAllowed(): bool
    {
        return $this->allow_artifact_closure;
    }
}
