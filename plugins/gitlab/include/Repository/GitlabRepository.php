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

/**
 * @psalm-immutable
 */
class GitlabRepository
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $gitlab_id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $full_url;

    /**
     * @var DateTimeImmutable
     */
    private $last_push_date;

    public function __construct(
        int $id,
        int $gitlab_id,
        string $name,
        string $path,
        string $description,
        string $full_url,
        DateTimeImmutable $last_push_date
    ) {
        $this->id             = $id;
        $this->gitlab_id      = $gitlab_id;
        $this->name           = $name;
        $this->path           = $path;
        $this->description    = $description;
        $this->full_url       = $full_url;
        $this->last_push_date = $last_push_date;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGitlabId(): int
    {
        return $this->gitlab_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFullUrl(): string
    {
        return $this->full_url;
    }

    public function getLastPushDate(): DateTimeImmutable
    {
        return $this->last_push_date;
    }
}
