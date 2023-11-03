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

namespace Tuleap\Git\Tests\Stub\DefaultBranch;

use Tuleap\Git\DefaultBranch\DefaultBranchUpdateExecutor;

final class DefaultBranchUpdateExecutorStub implements DefaultBranchUpdateExecutor
{
    /**
     * @var callable|null
     * @psalm-var callable(string):void|null
     */
    private $callback_on_set_default_branch;
    private bool $does_default_branch_been_set = false;

    public function setDefaultBranch(\Git_Exec $git_exec, string $default_branch): void
    {
        $this->does_default_branch_been_set = true;
        if ($this->callback_on_set_default_branch !== null) {
            ($this->callback_on_set_default_branch)($default_branch);
        }
    }

    /**
     * @psalm-param callable(string):void $callback_on_set_default_branch
     */
    public function setCallbackOnSetDefaultBranch(callable $callback_on_set_default_branch): void
    {
        $this->callback_on_set_default_branch = $callback_on_set_default_branch;
    }

    public function doesADefaultBranchBeenSet(): bool
    {
        return $this->does_default_branch_been_set;
    }
}
