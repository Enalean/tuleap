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

use Tuleap\Test\PHPUnit\TestCase;

final class DefaultBranchPostReceiveUpdaterTest extends TestCase
{
    private DefaultBranchPostReceiveUpdater $default_branch_post_receive_updater;

    protected function setUp(): void
    {
        $this->default_branch_post_receive_updater = new DefaultBranchPostReceiveUpdater();
    }

    public function testUpdatesDefaultBranchWhenTheExistingDefaultBranchDoesNotExist(): void
    {
        $git_exec = $this->createMock(\Git_Exec::class);
        $git_exec->method('getAllBranchesSortedByCreationDate')->willReturn(['main']);
        $git_exec->method('getDefaultBranch')->willReturn('dev');

        $git_exec->expects(self::once())->method('setDefaultBranch')->with('main');

        $this->default_branch_post_receive_updater->updateDefaultBranchWhenNeeded($git_exec);
    }

    public function testDoesNotAttemptToUpdateTheDefaultBranchWhenNoneExistInTheRepository(): void
    {
        $git_exec = $this->createMock(\Git_Exec::class);
        $git_exec->method('getAllBranchesSortedByCreationDate')->willReturn([]);

        $git_exec->expects(self::never())->method('setDefaultBranch');

        $this->default_branch_post_receive_updater->updateDefaultBranchWhenNeeded($git_exec);
    }

    public function testDoesNotUpdateTheDefaultBranchWhenItExistsInTheRepository(): void
    {
        $git_exec = $this->createMock(\Git_Exec::class);
        $git_exec->method('getAllBranchesSortedByCreationDate')->willReturn(['main']);
        $git_exec->method('getDefaultBranch')->willReturn('main');

        $git_exec->expects(self::never())->method('setDefaultBranch');
    }
}
