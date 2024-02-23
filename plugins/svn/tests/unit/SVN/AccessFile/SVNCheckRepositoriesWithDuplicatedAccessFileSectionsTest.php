<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\AccessControl;

use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\SVN\Repository\RepositoryByProjectCollection;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVNCore\Repository;
use Tuleap\SVNCore\SVNAccessFileContent;
use Tuleap\SVNCore\SVNAccessFileReader;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class SVNCheckRepositoriesWithDuplicatedAccessFileSectionsTest extends TestCase
{
    private SVNCheckRepositoriesWithDuplicatedAccessFileSections $command;
    private RepositoryManager&\PHPUnit\Framework\MockObject\MockObject $repository_manager;
    private SVNAccessFileReader&\PHPUnit\Framework\MockObject\MockObject $access_file_reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository_manager = $this->createMock(RepositoryManager::class);
        $this->access_file_reader = $this->createMock(SVNAccessFileReader::class);

        $this->command = new SVNCheckRepositoriesWithDuplicatedAccessFileSections(
            $this->repository_manager,
            $this->access_file_reader,
            new DuplicateSectionDetector(),
        );
    }

    public function testItDisplayEmptyResultWhenPlatformDoNotHaveAnySVNPluginRepositories(): void
    {
        $this->repository_manager->method('getRepositoriesOfNonDeletedProjects')->willReturn([]);
        $command_tester = new CommandTester($this->command);
        $command_tester->execute([]);

        $text_table          = $command_tester->getDisplay();
        $expected_text_table = "Start checking of duplicated sections in platform access files:\nNo duplicated sections in platform access files found.\n";

        self::assertEquals($expected_text_table, $text_table);
    }

    public function testItDisplayATableOfProjectRepositoriesWithDuplicatedSections(): void
    {
        $project_A = ProjectTestBuilder::aProject()->withId(101)->withUnixName('Project A')->build();
        $project_B = ProjectTestBuilder::aProject()->withId(102)->withUnixName('Project B')->build();

        $repository_A = $this->createMock(Repository::class);
        $repository_A->method('getProject')->willReturn($project_A);
        $repository_A->method('getSystemPath')->willReturn('/var/lib/tuleap/101/repo_A');
        $repository_A->method('getFullName')->willReturn('Project A/Repository A');
        $repository_A->method('getName')->willReturn('Repository A');
        $project_A_repositories = [
            $repository_A,
        ];

        $repository_B = $this->createMock(Repository::class);
        $repository_B->method('getProject')->willReturn($project_B);
        $repository_B->method('getSystemPath')->willReturn('/var/lib/tuleap/102/repo_B');
        $repository_B->method('getFullName')->willReturn('Project B/Repository B');
        $repository_B->method('getName')->willReturn('Repository B');
        $repository_C = $this->createMock(Repository::class);
        $repository_C->method('getProject')->willReturn($project_B);
        $repository_C->method('getSystemPath')->willReturn('/var/lib/tuleap/102/repo_C');
        $repository_C->method('getFullName')->willReturn('Project B/Repository C');
        $repository_C->method('getName')->willReturn('Repository C');
        $project_B_repositories = [
            $repository_B,
            $repository_C,
        ];
        $this->repository_manager->method('getRepositoriesOfNonDeletedProjects')->willReturn(
            [
                RepositoryByProjectCollection::build($project_A, $project_A_repositories),
                RepositoryByProjectCollection::build($project_B, $project_B_repositories),
            ]
        );

        $this->access_file_reader->method('getAccessFileContent')->willReturnCallback(
            function (Repository $repository): SVNAccessFileContent {
                if ($repository->getSystemPath() === '/var/lib/tuleap/101/repo_A') {
                    return new SVNAccessFileContent(
                        <<<EOT
                            [/]
                            *=
                        EOT,
                        <<<EOT
                            [/]
                            *=rw
                        EOT,
                    );
                }
                if ($repository->getSystemPath() === '/var/lib/tuleap/102/repo_C') {
                    return new SVNAccessFileContent(
                        <<<EOT
                            [groups]
                            member=user01
                        EOT,
                        <<<EOT
                            [groups]
                            member=user01, user02
                        EOT,
                    );
                }

                return new SVNAccessFileContent(
                    <<<EOT
                        [/]
                        *=rw
                    EOT,
                    <<<EOT
                        [groups]
                        member=user01, user02
                    EOT,
                );
            }
        );

        $command_tester = new CommandTester($this->command);
        $command_tester->execute([]);

        $text_table          = $command_tester->getDisplay();
        $expected_text_table = <<<EOT
            Start checking of duplicated sections in platform access files:
            ┌────────────┬──────────────┬──────────────┐
            │ Project Id │ Project name │ Repository   │
            ├────────────┼──────────────┼──────────────┤
            │ 101        │ project a    │ Repository A │
            │ 102        │ project b    │ Repository C │
            └────────────┴──────────────┴──────────────┘
            2 SVN access files with duplicated sections found.

            EOT;

        self::assertEquals($expected_text_table, $text_table);
    }
}
