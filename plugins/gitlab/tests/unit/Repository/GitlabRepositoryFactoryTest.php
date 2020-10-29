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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;

final class GitlabRepositoryFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItRetrievesGitlabIntegrationsForProject(): void
    {
        $dao = Mockery::mock(GitlabRepositoryDao::class);

        $factory = new GitlabRepositoryFactory(
            $dao
        );

        $project = Project::buildForTest();

        $dao->shouldReceive('getGitlabRepositoriesForProject')
            ->once()
            ->with(101)
            ->andReturn([
                [
                    'id' => 1,
                    'gitlab_id' => 1254652,
                    'name' => 'test01',
                    'path' => 'proj/test01',
                    'description' => '',
                    'full_url' => 'https://example.com/proj/test01',
                    'last_push_date' => 1603371803,
                ]
            ]);

        $gitlab_repositories = $factory->getGitlabRepositoriesForProject($project);

        $this->assertCount(1, $gitlab_repositories);

        $gitlab_repository = $gitlab_repositories[0];
        $this->assertSame(1, $gitlab_repository->getId());
        $this->assertSame(1254652, $gitlab_repository->getGitlabId());
        $this->assertSame('test01', $gitlab_repository->getName());
        $this->assertSame('proj/test01', $gitlab_repository->getPath());
        $this->assertSame('', $gitlab_repository->getDescription());
        $this->assertSame('https://example.com/proj/test01', $gitlab_repository->getFullUrl());
        $this->assertSame(1603371803, $gitlab_repository->getLastPushDate()->getTimestamp());
    }
}
