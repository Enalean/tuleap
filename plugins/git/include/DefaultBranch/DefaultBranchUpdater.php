<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

class DefaultBranchUpdater
{
    /**
     * @throws CannotSetANonExistingBranchAsDefaultException
     */
    public function updateDefaultBranch(\Git_Exec $git_exec, string $branch_name): void
    {
        $current_default_branch = $git_exec->getDefaultBranch();
        if ($current_default_branch === $branch_name) {
            return;
        }

        $all_branches = $git_exec->getAllBranchesSortedByCreationDate();
        if (! in_array($branch_name, $all_branches, true)) {
            throw new CannotSetANonExistingBranchAsDefaultException($branch_name);
        }

        $git_exec->setDefaultBranch($branch_name);
    }
}
