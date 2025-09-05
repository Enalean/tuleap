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

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class DefaultBranchUpdateExecutorAsGitoliteUser implements DefaultBranchUpdateExecutor
{
    #[\Override]
    public function setDefaultBranch(\Git_Exec $git_exec, string $default_branch): void
    {
        $set_default_branch_process = new Process(
            ['sudo', '-u', 'gitolite', 'DISPLAY_ERRORS=true', __DIR__ . '/../../bin/change-default-branch.php', $git_exec->getPath(), $default_branch]
        );
        try {
            $set_default_branch_process->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new CannotExecuteDefaultBranchUpdateException($exception->getMessage(), $exception);
        }
    }
}
