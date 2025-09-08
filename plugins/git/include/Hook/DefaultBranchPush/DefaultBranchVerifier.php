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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Git\Hook\DefaultBranchPush;

final class DefaultBranchVerifier implements VerifyIsDefaultBranch
{
    private const BRANCH_REF = 'refs/heads/';

    public function __construct(private \Git_Exec $git_exec)
    {
    }

    #[\Override]
    public function isDefaultBranch(string $refname): bool
    {
        $default_branch = $this->git_exec->getDefaultBranch();
        return $refname === self::BRANCH_REF . $default_branch;
    }
}
