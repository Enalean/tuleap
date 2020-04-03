<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Git\Repository;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class RepositoriesWithObjectsOverTheLimitCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $repository_factory;
    private $repository_objects_size_retriever;

    protected function setUp(): void
    {
        $this->repository_factory                = \Mockery::mock(\GitRepositoryFactory::class);
        $this->repository_objects_size_retriever = \Mockery::mock(GitRepositoryObjectsSizeRetriever::class);
    }

    public function testRepositoryOverTheLimitIsListed(): void
    {
        $command        = new RepositoriesWithObjectsOverTheLimitCommand(
            $this->repository_factory,
            $this->repository_objects_size_retriever
        );
        $command_tester = new CommandTester($command);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(2000);
        $repository->shouldReceive('getFullname')->andReturns('repository_name');
        $repository->shouldReceive('getProjectId')->andReturns(1000);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('project_name');
        $repository->shouldReceive('getProject')->andReturns($project);
        $this->repository_factory->shouldReceive('getAllRepositoriesWithActivityInTheLast2Months')->andReturns([
            $repository
        ]);
        $repository_with_largest_object_size = new LargestObjectSizeGitRepository($repository, PHP_INT_MAX);
        $this->repository_objects_size_retriever->shouldReceive('getLargestObjectSize')->andReturns(
            $repository_with_largest_object_size
        );

        $command_tester->execute([]);
        $text_table = $command_tester->getDisplay();
        $this->assertStringContainsString('2000', $text_table);
        $this->assertStringContainsString('repository_name', $text_table);
        $this->assertStringContainsString('1000', $text_table);
        $this->assertStringContainsString('project_name', $text_table);
        $this->assertStringContainsString((string) PHP_INT_MAX, $text_table);

        $command_tester->execute(['--format' => 'json'], ['capture_stderr_separately' => true]);
        $json_output = json_decode($command_tester->getDisplay(), true);
        $this->assertEqualsCanonicalizing(
            [
                [
                    'project_id'       => 1000,
                    'project_unixname' => 'project_name',
                    'repository_id'    => 2000,
                    'repository_name'  => 'repository_name',
                    'object_size'      => PHP_INT_MAX
                ]
            ],
            $json_output
        );
    }

    public function testUnknownFormatIsRejected(): void
    {
        $command        = new RepositoriesWithObjectsOverTheLimitCommand(
            $this->repository_factory,
            $this->repository_objects_size_retriever
        );
        $command_tester = new CommandTester($command);

        $this->repository_factory->shouldReceive('getAllRepositoriesWithActivityInTheLast2Months')->andReturns([]);

        $this->expectException(\RuntimeException::class);
        $command_tester->execute(['--format' => 'aaaaaaa']);
    }
}
