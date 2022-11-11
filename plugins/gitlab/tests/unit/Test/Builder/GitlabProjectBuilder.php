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

use Tuleap\Gitlab\API\GitlabProject;

final class GitlabProjectBuilder
{
    private string $description         = 'xanthosis merwinite';
    private string $web_url             = 'https://gitlab.example.com/sandaled/upfill';
    private string $path_with_namespace = 'sandaled/upfill';
    private \DateTimeImmutable $last_activity_at;
    private string $default_branch = 'main';

    private function __construct(private int $id)
    {
        $this->last_activity_at = new \DateTimeImmutable('@1658457229');
    }

    public static function aGitlabProject(int $id): self
    {
        return new self($id);
    }

    public function build(): GitlabProject
    {
        return new GitlabProject(
            $this->id,
            $this->description,
            $this->web_url,
            $this->path_with_namespace,
            $this->last_activity_at,
            $this->default_branch
        );
    }
}
