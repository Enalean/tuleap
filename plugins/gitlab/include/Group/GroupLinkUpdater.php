<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group;

use Tuleap\Git\Branch\BranchName;
use Tuleap\Git\Branch\InvalidBranchNameException;
use Tuleap\Gitlab\Group\Token\UpdateGroupLinkToken;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class GroupLinkUpdater
{
    private const string FAKE_BRANCH_NAME = 'branch_name';

    public function __construct(
        private UpdateBranchPrefixOfGroupLink $update_branch_prefix_of_group,
        private UpdateArtifactClosureOfGroupLink $update_artifact_closure_of_group,
        private UpdateGroupLinkToken $update_group_token,
    ) {
    }

    /**
     * @return Ok<null> | Err<Fault>
     */
    public function updateGroupLink(
        GroupLink $gitlab_group_link,
        UpdateGroupLinkCommand $command,
    ): Ok|Err {
        return $this->updateBranchPrefixOfGroupLink($gitlab_group_link, $command)
            ->map(fn() => $this->updateArtifactClosureOfGroupLink($gitlab_group_link, $command))
            ->map(fn () => $this->updateGroupToken($gitlab_group_link, $command));
    }

    /**
     * @return Ok<null> | Err<Fault>
     */
    private function updateBranchPrefixOfGroupLink(
        GroupLink $gitlab_group_link,
        UpdateGroupLinkCommand $command,
    ): Ok|Err {
        $prefix_branch_name = $command->branch_prefix;

        if ($prefix_branch_name === null) {
            return Result::ok(null);
        }

        try {
            BranchName::fromBranchNameShortHand($prefix_branch_name . self::FAKE_BRANCH_NAME);
        } catch (InvalidBranchNameException) {
            return Result::err(InvalidBranchPrefixFault::fromBranchPrefix($prefix_branch_name));
        }
        $this->update_branch_prefix_of_group->updateBranchPrefixOfGroupLink(
            $gitlab_group_link->id,
            $prefix_branch_name,
        );
        return Result::ok(null);
    }

    private function updateArtifactClosureOfGroupLink(
        GroupLink $gitlab_group_link,
        UpdateGroupLinkCommand $command,
    ): void {
        $allow_artifact_closure = $command->allow_artifact_closure;

        if ($allow_artifact_closure === null) {
            return;
        }

        $this->update_artifact_closure_of_group->updateArtifactClosureOfGroupLink(
            $gitlab_group_link->id,
            $allow_artifact_closure,
        );
    }

    private function updateGroupToken(
        GroupLink $gitlab_group_link,
        UpdateGroupLinkCommand $command,
    ): void {
        $gitlab_token = $command->gitlab_token;

        if ($gitlab_token === null) {
            return;
        }

        $this->update_group_token->updateToken($gitlab_group_link, $gitlab_token->getToken());
    }
}
