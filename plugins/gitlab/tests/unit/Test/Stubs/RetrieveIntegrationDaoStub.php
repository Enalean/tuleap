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

namespace Tuleap\Gitlab\Test\Stubs;

use DateTimeImmutable;
use Project;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\Repository\RetrieveIntegrationDao;

final class RetrieveIntegrationDaoStub implements RetrieveIntegrationDao
{
    private function __construct(private ?array $integration_row)
    {
    }

    /**
     * @psalm-return array{id:int, gitlab_repository_id:int, name:string, description:string, gitlab_repository_url:string, last_push_date:int, project_id:int, allow_artifact_closure:int}
     */
    #[\Override]
    public function searchUniqueIntegration(Project $project, GitlabProject $gitlab_project): ?array
    {
        return $this->integration_row;
    }

    public static function fromNullRow(): self
    {
        return new self(null);
    }

    public static function fromDefaultRow(): self
    {
        $row                           = [];
        $row['id']                     = 1;
        $row['gitlab_repository_id']   = 10;
        $row['name']                   = 'Hearts';
        $row['description']            = 'None';
        $row['gitlab_repository_url']  = 'https://gitlab.example.com/hearts';
        $row['last_push_date']         = (new DateTimeImmutable('@10'))->getTimestamp();
        $row['project_id']             = 101;
        $row['allow_artifact_closure'] = false;

        return new self($row);
    }
}
