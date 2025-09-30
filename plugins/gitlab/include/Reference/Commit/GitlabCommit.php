<?php
/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Reference\Commit;

/**
 * @psalm-immutable
 */
class GitlabCommit
{
    /**
     * @var string
     */
    private $commit_sha1;
    /**
     * @var int
     */
    private $commit_date;
    /**
     * @var string
     */
    private $commit_title;
    /**
     * @var string
     */
    private $commit_branch_name;
    /**
     * @var string
     */
    private $commit_author_name;
    /**
     * @var string
     */
    private $commit_author_email;

    public function __construct(
        string $commit_sha1,
        int $commit_date,
        string $commit_title,
        string $commit_branch_name,
        string $commit_author_name,
        string $commit_author_email,
    ) {
        $this->commit_sha1         = $commit_sha1;
        $this->commit_date         = $commit_date;
        $this->commit_title        = $commit_title;
        $this->commit_branch_name  = $commit_branch_name;
        $this->commit_author_name  = $commit_author_name;
        $this->commit_author_email = $commit_author_email;
    }

    public function getCommitSha1(): string
    {
        return $this->commit_sha1;
    }

    public function getCommitDate(): int
    {
        return $this->commit_date;
    }

    public function getCommitTitle(): string
    {
        return $this->commit_title;
    }

    public function getCommitBranchName(): string
    {
        return $this->commit_branch_name;
    }

    public function getCommitAuthorName(): string
    {
        return $this->commit_author_name;
    }

    public function getCommitAuthorEmail(): string
    {
        return $this->commit_author_email;
    }
}
