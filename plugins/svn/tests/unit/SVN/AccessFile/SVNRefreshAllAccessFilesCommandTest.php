<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

use ForgeConfig;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\ForgeConfigSandbox;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\RepositoryByProjectCollection;
use Tuleap\SVN\Repository\RepositoryManager;

final class SVNRefreshAllAccessFilesCommandTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessFileHistoryCreator
     */
    private $access_file_history_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessFileHistoryFactory
     */
    private $access_file_history_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RepositoryManager
     */
    private $repository_manager;

    private SVNRefreshAllAccessFilesCommand $command;

    protected function setUp(): void
    {
        $this->repository_manager          = $this->createMock(RepositoryManager::class);
        $this->access_file_history_factory = $this->createMock(AccessFileHistoryFactory::class);
        $this->access_file_history_creator = $this->createMock(AccessFileHistoryCreator::class);
        $this->command                     = new SVNRefreshAllAccessFilesCommand(
            $this->repository_manager,
            $this->access_file_history_factory,
            $this->access_file_history_creator
        );

        ForgeConfig::set('svn_root_file', 'svn_root_file');
    }

    public function testItDisplayEmptyResultWhenPlatformDoNotHaveAnySVNPluginRepositories(): void
    {
        $this->repository_manager->method('getRepositoriesOfNonDeletedProjects')->willReturn([]);
        $command_tester = new CommandTester($this->command);
        $command_tester->execute([]);

        $text_table          = $command_tester->getDisplay();
        $expected_text_table = "Start refresh access files:\nNo SVN multi-repositories found.\nEnd of refresh access files.\n";

        self::assertEquals($expected_text_table, $text_table);
    }

    public function testItDisplayATableOfProjectRepositories(): void
    {
        $project_A = $this->createMock(\Project::class);
        $project_A->method('getId')->willReturn(101);
        $project_A->method('getUnixName')->willReturn("Project A");

        $repository_A = $this->createMock(Repository::class);
        $repository_A->method('getSystemPath')->willReturn('/var/lib/tuleap/101/repo_A');
        $repository_A->method('getFullName')->willReturn('Project A/Repository A');
        $repository_A->method('getName')->willReturn('Repository A');
        $project_A_repositories = [
            $repository_A,
        ];

        $project_B = $this->createMock(\Project::class);
        $project_B->method('getId')->willReturn(102);
        $project_B->method('getUnixName')->willReturn("Project B");

        $repository_B = $this->createMock(Repository::class);
        $repository_B->method('getSystemPath')->willReturn('/var/lib/tuleap/102/repo_B');
        $repository_B->method('getFullName')->willReturn('Project B/Repository B');
        $repository_B->method('getName')->willReturn('Repository B');
        $repository_C = $this->createMock(Repository::class);
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

        $this->access_file_history_factory->expects(self::exactly(3))->method('getCurrentVersion')->willReturn($this->createMock(AccessFileHistory::class));
        $this->access_file_history_creator->expects(self::exactly(3))->method('saveAccessFileAndForceDefaultGeneration');


        $command_tester = new CommandTester($this->command);
        $command_tester->execute([]);

        $text_table          = $command_tester->getDisplay();
        $expected_text_table = <<<EOT
            Start refresh access files:
            ┌────────────┬──────────────┬──────────────┐
            │ Project Id │ Project name │ Repository   │
            ├────────────┼──────────────┼──────────────┤
            │ 101        │ Project A    │              │
            │            │              │ Repository A │
            │ 102        │ Project B    │              │
            │            │              │ Repository B │
            │            │              │ Repository C │
            └────────────┴──────────────┴──────────────┘
            3 SVN access files restored.

            EOT;

        self::assertEquals($expected_text_table, $text_table);
    }
}
