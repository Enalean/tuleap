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

use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class RepositoryIntegrationBuilder
{
    private int $gitlab_project_id = 79;
    private string $name           = 'ultratechnical';
    private string $description    = 'Hydnum turbination';
    private string $web_url        = 'https://gitlab.example.com/ultratechnical';
    private \DateTimeImmutable $last_push_date;
    private \Project $project;
    private bool $allow_artifact_closure = true;

    private function __construct(
        private int $intregration_id,
    ) {
        $this->last_push_date = new \DateTimeImmutable('@1453448602');
        $this->project        = ProjectTestBuilder::aProject()->withId(136)->build();
    }

    public static function aGitlabRepositoryIntegration(int $integration_id): self
    {
        return new self($integration_id);
    }

    public function withGitLabProjectId(int $gitlab_project_id): self
    {
        $this->gitlab_project_id = $gitlab_project_id;
        return $this;
    }

    public function inProject(\Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function build(): GitlabRepositoryIntegration
    {
        return new GitlabRepositoryIntegration(
            $this->intregration_id,
            $this->gitlab_project_id,
            $this->name,
            $this->description,
            $this->web_url,
            $this->last_push_date,
            $this->project,
            $this->allow_artifact_closure
        );
    }
}
