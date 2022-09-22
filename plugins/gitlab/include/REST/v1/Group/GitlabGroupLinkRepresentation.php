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

namespace Tuleap\Gitlab\REST\v1\Group;

use DateTimeImmutable;
use Tuleap\Gitlab\Group\GroupLink;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final class GitlabGroupLinkRepresentation
{
    public string $last_synchronization_date;

    private function __construct(
        public int $id,
        public int $gitlab_group_id,
        public int $project_id,
        public string $name,
        public string $full_path,
        public string $web_url,
        public ?string $avatar_url,
        DateTimeImmutable $last_synchronization_date,
        public bool $allow_artifact_closure,
        public ?string $create_branch_prefix,
    ) {
        $this->last_synchronization_date = JsonCast::fromNotNullDateTimeToDate($last_synchronization_date);
    }

    public static function buildFromObject(GroupLink $gitlab_group): self
    {
        return new self(
            $gitlab_group->id,
            $gitlab_group->gitlab_group_id,
            $gitlab_group->project_id,
            $gitlab_group->name,
            $gitlab_group->full_path,
            $gitlab_group->web_url,
            $gitlab_group->avatar_url,
            $gitlab_group->last_synchronization_date,
            $gitlab_group->allow_artifact_closure,
            $gitlab_group->prefix_branch_name,
        );
    }
}
