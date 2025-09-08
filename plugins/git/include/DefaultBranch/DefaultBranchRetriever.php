<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git\DefaultBranch;

use GitRepository;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class DefaultBranchRetriever implements RetrieveRepositoryDefaultBranch
{
    /**
     * @return Ok<string>|Err<Fault>
     */
    #[\Override]
    public function getRepositoryDefaultBranch(GitRepository $repository): Ok|Err
    {
        $git_exec            = \Git_Exec::buildFromRepository($repository);
        $default_branch_name = $git_exec->getDefaultBranch();

        if ($default_branch_name === null) {
            return Result::err(Fault::fromMessage('Default branch not found'));
        }

        return Result::ok($default_branch_name);
    }
}
