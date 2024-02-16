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

namespace Tuleap\Gitlab\API\Group;

use Tuleap\Gitlab\API\GitlabResponseAPIException;

/**
 * @psalm-immutable
 */
final class GitlabGroupApiDataRepresentation
{
    private int $gitlab_group_id;
    private string $name;
    private string $full_path;
    private string $web_url;
    private ?string $avatar_url;

    private function __construct(array $group_data)
    {
        $this->gitlab_group_id = $group_data['id'];
        $this->name            = $group_data['name'];
        $this->avatar_url      = $group_data['avatar_url'];
        $this->full_path       = $group_data['full_path'];
        $this->web_url         = $group_data['web_url'];
    }

    /**
     * @throws GitlabResponseAPIException
     */
    public static function buildGitlabGroupFromApi(array $group_data): self
    {
        if (
            ! array_key_exists('id', $group_data) ||
            ! array_key_exists('name', $group_data) ||
            ! array_key_exists('full_path', $group_data) ||
            ! array_key_exists('web_url', $group_data) ||
            ! array_key_exists('avatar_url', $group_data)
        ) {
            throw new GitlabResponseAPIException("Some keys are missing in the group Json. This is not expected. Aborting.");
        }

        if (
            ! is_int($group_data['id']) ||
            ! is_string($group_data['name']) ||
            ! is_string($group_data['full_path']) ||
            ! is_string($group_data['web_url']) ||
            ! (is_string($group_data['avatar_url']) || $group_data['avatar_url'] === null)
        ) {
            throw new GitlabResponseAPIException("Some keys haven't the expected types. This is not expected. Aborting.");
        }
        return new self($group_data);
    }

    public function getGitlabGroupId(): int
    {
        return $this->gitlab_group_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    public function getFullPath(): string
    {
        return $this->full_path;
    }

    public function getWebUrl(): string
    {
        return $this->web_url;
    }
}
