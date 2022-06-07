<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\Branch;

use Git_Exec;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BranchCreationExecutor
{
    /**
     * @throw CannotCreateNewBranchException
     */
    public function createNewBranch(Git_Exec $git_exec, string $new_branch_name, string $reference): void
    {
        $create_new_branch_process = new Process(
            ['sudo', '-u', 'gitolite', 'DISPLAY_ERRORS=true', __DIR__ . '/../../bin/create-new-branch.php', $git_exec->getPath(), $new_branch_name, $reference]
        );

        try {
            $create_new_branch_process->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new CannotCreateNewBranchException($exception->getMessage(), $exception);
        }
    }
}
