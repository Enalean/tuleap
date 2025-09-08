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

namespace Tuleap\Git\Hook\Asynchronous;

use Tuleap\Git\CommitMetadata\AuthorRetriever;
use Tuleap\Git\CommitMetadata\CommitMessageRetriever;
use Tuleap\Git\Hook\DefaultBranchPush\DefaultBranchPushProcessor;

final class DefaultBranchPushProcessorBuilder implements BuildDefaultBranchPushProcessor
{
    #[\Override]
    public function getProcessor(\GitRepository $repository): DefaultBranchPushProcessor
    {
        $git_exec = \Git_Exec::buildFromRepository($repository);
        return new DefaultBranchPushProcessor(
            new \GitDao(),
            new CommitMessageRetriever($git_exec),
            new AuthorRetriever($git_exec, \UserManager::instance())
        );
    }
}
