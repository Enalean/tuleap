<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabRepositoryIntegrationFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItRetrievesGitlabIntegrationsForProject(): void
    {
        $dao             = $this->createMock(GitlabRepositoryIntegrationDao::class);
        $project_manager = $this->createMock(ProjectManager::class);

        $factory = new GitlabRepositoryIntegrationFactory(
            $dao,
            $project_manager
        );

        $project = ProjectTestBuilder::aProject()->build();

        $dao
            ->expects($this->once())
            ->method('searchAllIntegrationsInProject')
            ->with(101)
            ->willReturn(
                [
                    [
                        'id'                     => 1,
                        'gitlab_repository_id'   => 1254652,
                        'name'                   => 'proj/test01',
                        'description'            => '',
                        'gitlab_repository_url'  => 'https://example.com/proj/test01',
                        'last_push_date'         => 1603371803,
                        'project_id'             => 101,
                        'allow_artifact_closure' => 1,
                    ],
                ]
            );

        $project_manager
            ->method('getProject')
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $gitlab_repositories = $factory->getAllIntegrationsInProject($project);

        self::assertCount(1, $gitlab_repositories);

        $gitlab_repository = $gitlab_repositories[0];
        self::assertSame(1, $gitlab_repository->getId());
        self::assertSame(1254652, $gitlab_repository->getGitlabRepositoryId());
        self::assertSame('proj/test01', $gitlab_repository->getName());
        self::assertSame('', $gitlab_repository->getDescription());
        self::assertSame('https://example.com/proj/test01', $gitlab_repository->getGitlabRepositoryUrl());
        self::assertSame(1603371803, $gitlab_repository->getLastPushDate()->getTimestamp());
    }
}
