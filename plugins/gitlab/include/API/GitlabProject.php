<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\API;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
class GitlabProject
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $web_url;

    /**
     * @var string
     */
    private $path_with_namespace;

    /**
     * @var DateTimeImmutable
     */
    private $last_activity_at;
    /**
     * @var string
     */
    private $default_branch;

    public function __construct(
        int $id,
        string $description,
        string $web_url,
        string $path_with_namespace,
        DateTimeImmutable $last_activity_at,
        string $default_branch,
    ) {
        $this->id                  = $id;
        $this->description         = $description;
        $this->web_url             = $web_url;
        $this->path_with_namespace = $path_with_namespace;
        $this->last_activity_at    = $last_activity_at;
        $this->default_branch      = $default_branch;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getWebUrl(): string
    {
        return $this->web_url;
    }

    public function getPathWithNamespace(): string
    {
        return $this->path_with_namespace;
    }

    public function getLastActivityAt(): DateTimeImmutable
    {
        return $this->last_activity_at;
    }

    public function getDefaultBranch(): string
    {
        return $this->default_branch;
    }
}
