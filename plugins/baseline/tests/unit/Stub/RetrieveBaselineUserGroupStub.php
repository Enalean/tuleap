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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Stub;

use Tuleap\Baseline\Adapter\UserGroupProxy;
use Tuleap\Baseline\Domain\BaselineUserGroup;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\RetrieveBaselineUserGroup;
use Tuleap\Baseline\Domain\UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException;

final class RetrieveBaselineUserGroupStub implements RetrieveBaselineUserGroup
{
    /**
     * @param \ProjectUGroup[] $user_groups
     */
    private function __construct(private array $user_groups)
    {
    }

    public static function withUserGroups(\ProjectUGroup ...$user_groups): self
    {
        $user_groups_by_ids = [];

        foreach ($user_groups as $user_group) {
            $user_groups_by_ids[$user_group->getId()] = $user_group;
        }
        return new self($user_groups_by_ids);
    }

    #[\Override]
    public function retrieveUserGroupFromBaselineProjectAndId(ProjectIdentifier $project, int $ugroup_id): BaselineUserGroup
    {
        if (! array_key_exists($ugroup_id, $this->user_groups)) {
            throw new UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException($ugroup_id);
        }

        return UserGroupProxy::fromProjectUGroup($this->user_groups[$ugroup_id]);
    }
}
