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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program;

use Luracast\Restler\RestException;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\InvalidProgramUserGroup;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrieveProgramUserGroup;
use Tuleap\ProgramManagement\Domain\Program\ProgramUserGroupDoesNotExistException;
use Tuleap\Project\REST\UserGroupRetriever;

final class ProgramUserGroupRetriever implements RetrieveProgramUserGroup
{
    private UserGroupRetriever $user_group_retriever;

    public function __construct(UserGroupRetriever $user_group_retriever)
    {
        $this->user_group_retriever = $user_group_retriever;
    }

    /**
     * @throws InvalidProgramUserGroup
     */
    #[\Override]
    public function getProjectUserGroupId(string $raw_user_group_id, ProgramForAdministrationIdentifier $program): int
    {
        try {
            $project_user_group = $this->user_group_retriever->getExistingUserGroup($raw_user_group_id);
        } catch (RestException $e) {
            throw new ProgramUserGroupDoesNotExistException($raw_user_group_id);
        }

        if ((int) $project_user_group->getProjectId() !== $program->id) {
            throw new ProgramUserGroupDoesNotExistException($raw_user_group_id);
        }

        return $project_user_group->getId();
    }
}
