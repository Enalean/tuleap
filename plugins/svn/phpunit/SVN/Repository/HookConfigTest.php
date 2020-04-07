<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use Mockery;
use PHPUnit\Framework\TestCase;
use ProjectManager;
use Tuleap\Project\ProjectAccessChecker;

class HookConfigTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Project
     */
    private $project;

    /**
     * @var HookConfigRetriever
     */
    private $hook_retriever;

    /**
     * @var HookDao
     */
    private $hook_dao;

    /**
     * @var HookConfigUpdator
     */
    private $hook_updater;

    protected function setUp(): void
    {
        parent::setUp();

        $project_history_dao = \Mockery::spy(\ProjectHistoryDao::class);
        $project_dao         = \Mockery::spy(\ProjectDao::class);
        $this->hook_dao      = \Mockery::spy(\Tuleap\SVN\Repository\HookDao::class);

        $project_manager = ProjectManager::testInstance(
            Mockery::mock(ProjectAccessChecker::class),
            $project_history_dao,
            $project_dao
        );

        $this->project = $project_manager->getProjectFromDbRow(
            [
                'group_id'           => 123,
                'unix_group_name'    => 'test_project',
                'access'             => 'private',
                'svn_tracker'        => null,
                'svn_can_change_log' => null
            ]
        );

        $this->hook_retriever = new HookConfigRetriever($this->hook_dao, new HookConfigSanitizer());
        $hook_checker         = \Mockery::spy(\Tuleap\SVN\Repository\HookConfigChecker::class);
        $this->hook_updater   = new HookConfigUpdator(
            $this->hook_dao,
            $project_history_dao,
            $hook_checker,
            new HookConfigSanitizer(),
            new ProjectHistoryFormatter()
        );
        $hook_checker->shouldReceive('hasConfigurationChanged')->andReturn(true);
    }

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();

        parent::tearDown();
    }

    public function testItReturnsFalseForEveryConfigWHenNoCustomConfigurationIsStored(): void
    {
        $this->hook_dao->shouldReceive('getHookConfig')->withArgs([33])->andReturn([]);

        $repo = new Repository(33, 'reponame', '', '', $this->project);
        $cfg  = $this->hook_retriever->getHookConfig($repo);

        $mandatory_ref = $cfg->getHookConfig(HookConfig::MANDATORY_REFERENCE);
        $this->assertEquals(false, $mandatory_ref);
    }

    public function testItReturnsCustomsConfigurationWhenSaved(): void
    {
        $this->hook_dao->shouldReceive('getHookConfig')->withArgs([33])->andReturn(
            [
                HookConfig::MANDATORY_REFERENCE => true
            ]
        );

        $repo = new Repository(33, 'reponame', '', '', $this->project);
        $cfg  = $this->hook_retriever->getHookConfig($repo);

        $mandatory_ref = $cfg->getHookConfig(HookConfig::MANDATORY_REFERENCE);
        $this->assertEquals(true, $mandatory_ref);
    }

    public function testItCanChangeTheHookConfig(): void
    {
        $repository = new Repository(22, 'reponame', '', '', $this->project);
        $this->hook_dao->shouldReceive('updateHookConfig')->withArgs(
            [
                22,
                [HookConfig::MANDATORY_REFERENCE => true]
            ]
        )->once()->andReturn(true);

        $this->hook_updater->updateHookConfig(
            $repository,
            [
                HookConfig::MANDATORY_REFERENCE => true,
                'foo'                           => true
            ]
        );
    }
}
