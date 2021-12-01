<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Reference\Branch;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
class GitlabBranch
{
    private string $commit_sha1;
    private string $branch_name;
    private ?DateTimeImmutable $last_push_date;

    public function __construct(
        string $commit_sha1,
        string $branch_name,
        ?DateTimeImmutable $last_push_date,
    ) {
        $this->commit_sha1    = $commit_sha1;
        $this->branch_name    = $branch_name;
        $this->last_push_date = $last_push_date;
    }

    public function getCommitSha1(): string
    {
        return $this->commit_sha1;
    }

    public function getBranchName(): string
    {
        return $this->branch_name;
    }

    public function getLastPushDate(): ?DateTimeImmutable
    {
        return $this->last_push_date;
    }
}
