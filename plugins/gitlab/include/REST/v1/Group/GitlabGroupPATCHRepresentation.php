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
final class GitlabGroupPATCHRepresentation
{
    /**
     * @var string | null {@required false}
     */
    public $create_branch_prefix;
    /**
     * @var bool | null {@required false}
     */
    public $allow_artifact_closure;
    /**
     * @var string | null {@required false}
     */
    public $gitlab_token;

    private function __construct(?string $create_branch_prefix, ?bool $allow_artifact_closure, ?string $gitlab_token)
    {
        $this->create_branch_prefix   = $create_branch_prefix;
        $this->allow_artifact_closure = $allow_artifact_closure;
        $this->gitlab_token           = $gitlab_token;
    }

    public static function build(?string $create_branch_prefix, ?bool $allow_artifact_closure, ?string $gitlab_token): self
    {
        return new self($create_branch_prefix, $allow_artifact_closure, $gitlab_token);
    }
}
