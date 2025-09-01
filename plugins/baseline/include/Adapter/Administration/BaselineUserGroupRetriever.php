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

namespace Tuleap\Baseline\Adapter\Administration;

use Override;
use Tuleap\Baseline\Adapter\UserGroupProxy;
use Tuleap\Baseline\Domain\RetrieveBaselineUserGroup;
use Tuleap\Baseline\Domain\UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException;
use Tuleap\Baseline\Domain\BaselineUserGroup;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\UGroupRetriever;

final class BaselineUserGroupRetriever implements RetrieveBaselineUserGroup
{
    public function __construct(
        private ProjectByIDFactory $project_factory,
        private UGroupRetriever $group_retriever,
    ) {
    }

    #[Override]
    public function retrieveUserGroupFromBaselineProjectAndId(ProjectIdentifier $project_identifier, int $ugroup_id): BaselineUserGroup
    {
        $user_group = $this->group_retriever->getUGroup(
            $this->project_factory->getValidProjectById($project_identifier->getID()),
            $ugroup_id
        );

        if (! $user_group) {
            throw new UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException($ugroup_id);
        }

        return UserGroupProxy::fromProjectUGroup($user_group);
    }
}
