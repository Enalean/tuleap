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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\RepositoryByProjectCollection;
use Tuleap\SVN\Repository\RepositoryManager;

class SVNRefreshAllAccessFilesCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AccessFileHistoryCreator
     */
    private $access_file_history_creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AccessFileHistoryFactory
     */
    private $access_file_history_factory;

    /**
     * @var Mockery\MockInterface|RepositoryManager
     */
    private $repository_manager;

    /**
     * @var bool
     */
    private $globals_svnaccess_set_initially;

    /**
     * @var bool
     */
    private $globals_svngroups_set_initially;

    /**
     * @var SVNRefreshAllAccessFilesCommand
     */
    private $command;

    protected function setUp(): void
    {
        $this->globals_svnaccess_set_initially = isset($GLOBALS['SVNACCESS']);
        $this->globals_svngroups_set_initially = isset($GLOBALS['SVNGROUPS']);

        $this->repository_manager          = Mockery::mock(RepositoryManager::class);
        $this->access_file_history_factory = Mockery::mock(AccessFileHistoryFactory::class);
        $this->access_file_history_creator = Mockery::mock(AccessFileHistoryCreator::class);
        $this->command                     = new SVNRefreshAllAccessFilesCommand(
            $this->repository_manager,
            $this->access_file_history_factory,
            $this->access_file_history_creator
        );

        ForgeConfig::set('svn_root_file', 'svn_root_file');
    }

    protected function tearDown(): void
    {
        if (!$this->globals_svnaccess_set_initially) {
            unset($GLOBALS['SVNACCESS']);
        }
        if (!$this->globals_svngroups_set_initially) {
            unset($GLOBALS['SVNGROUPS']);
        }
    }

    public function testItDisplayEmptyResultWhenPlatformDoNotHaveAnySVNPluginRepositories(): void
    {
        $this->repository_manager->shouldReceive('getRepositoriesOfNonDeletedProjects')->andReturn([]);
        $command_tester = new CommandTester($this->command);
        $command_tester->execute([]);

        $text_table          = $command_tester->getDisplay();
        $expected_text_table = "Start refresh access files:\nNo SVN multi-repositories found.\nEnd of refresh access files.\n";

        $this->assertEquals($expected_text_table, $text_table);
    }

    public function testItDisplayATableOfProjectRepositories(): void
    {
        $project_A = Mockery::mock(\Project::class);
        $project_A->shouldReceive('getId')->andReturn(101);
        $project_A->shouldReceive('getUnixName')->andReturn("Project A");

        $repository_A = Mockery::mock(Repository::class);
        $repository_A->shouldReceive('getSystemPath')->andReturn('/var/lib/tuleap/101/repo_A');
        $repository_A->shouldReceive('getFullName')->andReturn('Project A/Repository A');
        $repository_A->shouldReceive('getName')->andReturn('Repository A');
        $project_A_repositories = [
            $repository_A
        ];

        $project_B = Mockery::mock(\Project::class);
        $project_B->shouldReceive('getId')->andReturn(102);
        $project_B->shouldReceive('getUnixName')->andReturn("Project B");
        $repository_B = Mockery::mock(Repository::class);
        $repository_B->shouldReceive('getSystemPath')->andReturn('/var/lib/tuleap/102/repo_B');
        $repository_B->shouldReceive('getFullName')->andReturn('Project B/Repository B');
        $repository_B->shouldReceive('getName')->andReturn('Repository B');
        $repository_C = Mockery::mock(Repository::class);
        $repository_C->shouldReceive('getSystemPath')->andReturn('/var/lib/tuleap/102/repo_C');
        $repository_C->shouldReceive('getFullName')->andReturn('Project B/Repository C');
        $repository_C->shouldReceive('getName')->andReturn('Repository C');
        $project_B_repositories = [
            $repository_B,
            $repository_C,
        ];
        $this->repository_manager->shouldReceive('getRepositoriesOfNonDeletedProjects')->andReturn(
            [
                RepositoryByProjectCollection::build($project_A, $project_A_repositories),
                RepositoryByProjectCollection::build($project_B, $project_B_repositories),
            ]
        );

        $this->access_file_history_factory->shouldReceive('getCurrentVersion')->andReturn(Mockery::mock(AccessFileHistory::class))->times(3);
        $this->access_file_history_creator->shouldReceive('saveAccessFileAndForceDefaultGeneration')->times(3);


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

        $this->assertEquals($expected_text_table, $text_table);
    }
}
