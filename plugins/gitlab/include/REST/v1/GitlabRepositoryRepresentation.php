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

namespace Tuleap\Gitlab\REST\v1;

use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class GitlabRepositoryRepresentation
{
    public const ROUTE = 'gitlab_repositories';

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $gitlab_id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $full_url;

    /**
     * @var string
     */
    public $last_push_date;

    private function __construct(
        int $id,
        int $gitlab_id,
        string $name,
        string $path,
        string $description,
        string $full_url,
        int $last_push_date_timestamp
    ) {
        $this->id             = JsonCast::toInt($id);
        $this->gitlab_id      = JsonCast::toInt($gitlab_id);
        $this->name           = $name;
        $this->path           = $path;
        $this->description    = $description;
        $this->full_url       = $full_url;
        $this->last_push_date = JsonCast::toDate($last_push_date_timestamp);
    }

    public static function buildFromGitlabRepository(GitlabRepository $gitlab_repository): self
    {
        return new self(
            $gitlab_repository->getId(),
            $gitlab_repository->getGitlabId(),
            $gitlab_repository->getName(),
            $gitlab_repository->getPath(),
            $gitlab_repository->getDescription(),
            $gitlab_repository->getFullUrl(),
            $gitlab_repository->getLastPushDate()->getTimestamp(),
        );
    }
}
