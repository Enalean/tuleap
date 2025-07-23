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

use Tuleap\Baseline\Domain\RoleAssignmentsToUpdate;

final class RoleAssignmentsToUpdateStub implements RoleAssignmentsToUpdate
{
    private function __construct(
        private array $administators_ids,
        private array $readers_ids,
    ) {
    }

    public static function withUserGroupsIds(
        array $administators_ids,
        array $readers_ids,
    ): self {
        return new self(
            $administators_ids,
            $readers_ids
        );
    }

    #[\Override]
    public function getBaselineAdministratorsUserGroupsIds(): array
    {
        return $this->administators_ids;
    }

    #[\Override]
    public function getBaselineReadersUserGroupsIds(): array
    {
        return $this->readers_ids;
    }
}
