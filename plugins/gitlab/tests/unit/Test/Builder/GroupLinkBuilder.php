<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Test\Builder;

use Tuleap\Gitlab\Group\GroupLink;

final class GroupLinkBuilder
{
    private int $gitlab_group_id = 45;
    private int $project_id      = 174;
    private string $name         = 'Undeliverableness';
    private string $full_path    = 'bombproof/undeliverableness';
    private string $web_url      = 'https://gitlab.example.com/groups/bombproof/undeliverableness';
    private ?string $avatar_url  = 'https://gitlab.example.com/uploads/-/system/group/avatar/45/avatar.png';
    private int $last_synchronization_timestamp;
    private bool $allow_artifact_closure = true;
    private string $branch_prefix        = 'dev-';

    private function __construct(private int $id)
    {
        $this->last_synchronization_timestamp = 1630915579;
    }

    public static function aGroupLink(int $group_link_id): self
    {
        return new self($group_link_id);
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withWebURL(string $web_url): self
    {
        $this->web_url = $web_url;
        return $this;
    }

    public function withFullPath(string $full_path): self
    {
        $this->full_path = $full_path;
        return $this;
    }

    public function withAllowArtifactClosure(bool $allow_artifact_closure): self
    {
        $this->allow_artifact_closure = $allow_artifact_closure;
        return $this;
    }

    public function withBranchPrefix(string $branch_prefix): self
    {
        $this->branch_prefix = $branch_prefix;
        return $this;
    }

    public function withNoBranchPrefix(): self
    {
        $this->branch_prefix = '';
        return $this;
    }

    public function withProjectId(int $project_id): self
    {
        $this->project_id = $project_id;
        return $this;
    }

    public function build(): GroupLink
    {
        return GroupLink::buildGroupLinkFromRow([
            'id'                        => $this->id,
            'gitlab_group_id'           => $this->gitlab_group_id,
            'project_id'                => $this->project_id,
            'name'                      => $this->name,
            'full_path'                 => $this->full_path,
            'web_url'                   => $this->web_url,
            'avatar_url'                => $this->avatar_url,
            'last_synchronization_date' => $this->last_synchronization_timestamp,
            'allow_artifact_closure'    => (int) $this->allow_artifact_closure,
            'create_branch_prefix'      => $this->branch_prefix,
        ]);
    }
}
