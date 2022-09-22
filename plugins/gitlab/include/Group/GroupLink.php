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

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
final class GroupLink
{
    private function __construct(
        public int $id,
        public int $gitlab_group_id,
        public int $project_id,
        public string $name,
        public string $full_path,
        public string $web_url,
        public ?string $avatar_url,
        public DateTimeImmutable $last_synchronization_date,
        public bool $allow_artifact_closure,
        public ?string $prefix_branch_name,
    ) {
    }

    public static function buildGroupLinkFromInsertionRows(
        int $id,
        NewGroup $group_DB_insertion,
    ): self {
        return new self(
            $id,
            $group_DB_insertion->gitlab_group_id,
            $group_DB_insertion->project_id,
            $group_DB_insertion->name,
            $group_DB_insertion->full_path,
            $group_DB_insertion->web_url,
            $group_DB_insertion->avatar_url,
            $group_DB_insertion->last_synchronization_date,
            $group_DB_insertion->allow_artifact_closure,
            $group_DB_insertion->prefix_branch_name
        );
    }

    /**
     * @psalm-param array{id: int, gitlab_group_id: int, project_id: int, name:string, full_path: string, web_url: string, avatar_url: ?string, last_synchronization_date: int, allow_artifact_closure: int, create_branch_prefix: ?string} $row
     */
    public static function buildGroupLinkFromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['gitlab_group_id'],
            $row['project_id'],
            $row['name'],
            $row['full_path'],
            $row['web_url'],
            $row['avatar_url'],
            (new DateTimeImmutable())->setTimestamp($row['last_synchronization_date']),
            (bool) $row['allow_artifact_closure'],
            $row['create_branch_prefix'],
        );
    }
}
