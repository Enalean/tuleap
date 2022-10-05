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
use Tuleap\Gitlab\API\Group\GitlabGroupApiDataRepresentation;

/**
 * @psalm-immutable
 */
final class NewGroup
{
    private function __construct(
        public int $gitlab_group_id,
        public int $project_id,
        public string $name,
        public string $full_path,
        public string $web_url,
        public ?string $avatar_url,
        public DateTimeImmutable $last_synchronization_date,
        public bool $allow_artifact_closure,
        public string $prefix_branch_name,
    ) {
    }

    public static function fromAPIRepresentation(
        GitlabGroupApiDataRepresentation $representation,
        \Project $project,
        \DateTimeImmutable $last_synchronization_date,
        bool $allow_artifact_closure,
        string $prefix_branch_name,
    ): self {
        return new self(
            $representation->getGitlabGroupId(),
            (int) $project->getID(),
            $representation->getName(),
            $representation->getFullPath(),
            $representation->getWebUrl(),
            $representation->getAvatarUrl(),
            $last_synchronization_date,
            $allow_artifact_closure,
            $prefix_branch_name
        );
    }
}
