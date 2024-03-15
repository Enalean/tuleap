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

/**
 * @psalm-immutable
 */
final class GitlabGroupPOSTRepresentation
{
    /**
     * @var string {@pattern /^https:\/\//i}
     */
    public string $gitlab_server_url;

    /**
     * @var string | null $create_branch_prefix {@required false}
     */
    public ?string $create_branch_prefix = null;

    public function __construct(
        public int $project_id,
        public int $gitlab_group_id,
        public string $gitlab_token,
        string $gitlab_server_url,
        public bool $allow_artifact_closure,
        ?string $create_branch_prefix,
    ) {
        $this->gitlab_server_url    = $gitlab_server_url;
        $this->create_branch_prefix = $create_branch_prefix;
    }
}
