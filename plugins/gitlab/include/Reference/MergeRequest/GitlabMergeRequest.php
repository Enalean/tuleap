<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference\MergeRequest;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
final class GitlabMergeRequest
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $state;
    /**
     * @var DateTimeImmutable
     */
    private $created_at;
    /**
     * @var string|null
     */
    private $author_name;
    /**
     * @var string|null
     */
    private $author_email;

    public function __construct(
        string $title,
        string $state,
        DateTimeImmutable $created_at,
        ?string $author_name,
        ?string $author_email,
    ) {
        $this->title        = $title;
        $this->state        = $state;
        $this->created_at   = $created_at;
        $this->author_name  = $author_name;
        $this->author_email = $author_email;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getCreatedAtDate(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getAuthorName(): ?string
    {
        return $this->author_name;
    }

    public function getAuthorEmail(): ?string
    {
        return $this->author_email;
    }

    public function isAuthorAlreadyFetched(): bool
    {
        return $this->author_name !== null;
    }
}
