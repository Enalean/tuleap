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
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchInfoDao;

class GitlabBranchFactory
{
    private BranchInfoDao $dao;

    public function __construct(BranchInfoDao $dao)
    {
        $this->dao = $dao;
    }

    public function getGitlabBranchInRepositoryWithBranchName(
        GitlabRepositoryIntegration $repository_integration,
        string $branch_name,
    ): ?GitlabBranch {
        $row = $this->dao->searchBranchInRepositoryWithBranchName(
            $repository_integration->getId(),
            $branch_name
        );

        if ($row === null) {
            return null;
        }

        $last_push_date = null;
        if ($row['last_push_date'] !== null) {
            $last_push_date = (new DateTimeImmutable())->setTimestamp($row['last_push_date']);
        }

        return new GitlabBranch(
            $row['commit_sha1'],
            $row['branch_name'],
            $last_push_date,
        );
    }
}
