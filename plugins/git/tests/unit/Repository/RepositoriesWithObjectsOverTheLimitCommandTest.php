<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RepositoriesWithObjectsOverTheLimitCommandTest extends TestCase
{
    private GitRepositoryFactory&MockObject $repository_factory;
    private GitRepositoryObjectsSizeRetriever&MockObject $repository_objects_size_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository_factory                = $this->createMock(GitRepositoryFactory::class);
        $this->repository_objects_size_retriever = $this->createMock(GitRepositoryObjectsSizeRetriever::class);
    }

    public function testRepositoryOverTheLimitIsListed(): void
    {
        $command        = new RepositoriesWithObjectsOverTheLimitCommand(
            $this->repository_factory,
            $this->repository_objects_size_retriever
        );
        $command_tester = new CommandTester($command);

        $project    = ProjectTestBuilder::aProject()->withId(1000)->withUnixName('project_name')->build();
        $repository = GitRepositoryTestBuilder::aProjectRepository()->withId(2000)->withName('repository_name')->inProject($project)->build();
        $this->repository_factory->method('getAllRepositoriesWithActivityInTheLast2Months')->willReturn([$repository]);
        $repository_with_largest_object_size = new LargestObjectSizeGitRepository($repository, PHP_INT_MAX);
        $this->repository_objects_size_retriever->method('getLargestObjectSize')->willReturn($repository_with_largest_object_size);

        $command_tester->execute([]);
        $text_table = $command_tester->getDisplay();
        self::assertStringContainsString('2000', $text_table);
        self::assertStringContainsString('repository_name', $text_table);
        self::assertStringContainsString('1000', $text_table);
        self::assertStringContainsString('project_name', $text_table);
        self::assertStringContainsString((string) PHP_INT_MAX, $text_table);

        $command_tester->execute(['--format' => 'json'], ['capture_stderr_separately' => true]);
        $json_output = json_decode($command_tester->getDisplay(), true);
        self::assertEqualsCanonicalizing([
            [
                'project_id'       => 1000,
                'project_unixname' => 'project_name',
                'repository_id'    => 2000,
                'repository_name'  => 'repository_name',
                'object_size'      => PHP_INT_MAX,
            ],
        ], $json_output);
    }

    public function testUnknownFormatIsRejected(): void
    {
        $command        = new RepositoriesWithObjectsOverTheLimitCommand(
            $this->repository_factory,
            $this->repository_objects_size_retriever
        );
        $command_tester = new CommandTester($command);

        $this->repository_factory->method('getAllRepositoriesWithActivityInTheLast2Months')->willReturn([]);

        $this->expectException(RuntimeException::class);
        $command_tester->execute(['--format' => 'aaaaaaa']);
    }
}
