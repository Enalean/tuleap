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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Git\Reference;

/**
 * @psalm-immutable
 */
final class CommitDetails
{
    /**
     * @var string
     */
    private $hash;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $first_branch;
    /**
     * @var string
     */
    private $first_tag;
    /**
     * @var string
     */
    private $author_email;
    /**
     * @var string
     */
    private $author_name;
    /**
     * @var int
     */
    private $committer_epoch;

    public function __construct(
        string $hash,
        string $title,
        string $first_branch,
        string $first_tag,
        string $author_email,
        string $author_name,
        int $committer_epoch,
    ) {
        $this->hash            = $hash;
        $this->title           = $title;
        $this->first_branch    = $first_branch;
        $this->first_tag       = $first_tag;
        $this->author_email    = $author_email;
        $this->author_name     = $author_name;
        $this->committer_epoch = $committer_epoch;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFirstBranch(): string
    {
        return $this->first_branch;
    }

    public function getFirstTag(): string
    {
        return $this->first_tag;
    }

    public function getAuthorEmail(): string
    {
        return $this->author_email;
    }

    public function getAuthorName(): string
    {
        return $this->author_name;
    }

    public function getCommitterEpoch(): int
    {
        return $this->committer_epoch;
    }
}
