<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\BranchUpdate;

use Git_Command_Exception;
use Git_Exec;

class PullRequestUpdateCommitDiff
{
    /**
     * @return string[]
     * @throws Git_Command_Exception
     */
    public function findNewCommitReferences(
        Git_Exec $git_exec,
        string $old_src_reference,
        string $new_src_reference,
        string $old_dst_reference,
        string $new_dst_reference
    ): array {
        $new_commit_references = $git_exec->revList($new_dst_reference, $new_src_reference);

        try {
            $old_commit_references = $git_exec->revList($old_dst_reference, $old_src_reference);
        } catch (Git_Command_Exception $exception) {
            return $new_commit_references;
        }

        return array_diff($new_commit_references, $old_commit_references);
    }
}
