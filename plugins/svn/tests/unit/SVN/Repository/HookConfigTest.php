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

use ProjectManager;
use Tuleap\Project\ProjectAccessChecker;

class HookConfigTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Project $project;
    private HookConfigRetriever $hook_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HookDao
     */
    private $hook_dao;
    private HookConfigUpdator $hook_updater;
    private \ProjectHistoryDao&\PHPUnit\Framework\MockObject\MockObject $project_history_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_history_dao = $this->createMock(\ProjectHistoryDao::class);
        $project_dao               = $this->createMock(\ProjectDao::class);
        $this->hook_dao            = $this->createMock(\Tuleap\SVN\Repository\HookDao::class);

        $project_manager = ProjectManager::testInstance(
            $this->createMock(ProjectAccessChecker::class),
            $this->project_history_dao,
            $project_dao
        );

        $this->project = $project_manager->getProjectFromDbRow(
            [
                'group_id'           => 123,
                'unix_group_name'    => 'test_project',
                'access'             => 'private',
                'svn_tracker'        => null,
                'svn_can_change_log' => null,
            ]
        );

        $this->hook_retriever = new HookConfigRetriever($this->hook_dao, new HookConfigSanitizer());
        $hook_checker         = $this->createMock(\Tuleap\SVN\Repository\HookConfigChecker::class);
        $this->hook_updater   = new HookConfigUpdator(
            $this->hook_dao,
            $this->project_history_dao,
            $hook_checker,
            new HookConfigSanitizer(),
            new ProjectHistoryFormatter()
        );
        $hook_checker->method('hasConfigurationChanged')->willReturn(true);
    }

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();

        parent::tearDown();
    }

    public function testItReturnsFalseForEveryConfigWHenNoCustomConfigurationIsStored(): void
    {
        $this->hook_dao->method('getHookConfig')->with(33)->willReturn([]);

        $repo = SvnRepository::buildActiveRepository(33, 'reponame', $this->project);
        $cfg  = $this->hook_retriever->getHookConfig($repo);

        $mandatory_ref = $cfg->getHookConfig(HookConfig::MANDATORY_REFERENCE);
        self::assertEquals(false, $mandatory_ref);
    }

    public function testItReturnsCustomsConfigurationWhenSaved(): void
    {
        $this->hook_dao->method('getHookConfig')->with(33)->willReturn(
            [
                HookConfig::MANDATORY_REFERENCE => true,
            ]
        );

        $repo = SvnRepository::buildActiveRepository(33, 'reponame', $this->project);
        $cfg  = $this->hook_retriever->getHookConfig($repo);

        $mandatory_ref = $cfg->getHookConfig(HookConfig::MANDATORY_REFERENCE);
        self::assertEquals(true, $mandatory_ref);
    }

    public function testItCanChangeTheHookConfig(): void
    {
        $repository = SvnRepository::buildActiveRepository(22, 'reponame', $this->project);
        $this->hook_dao->expects(self::once())
            ->method('updateHookConfig')
            ->with(22, [HookConfig::MANDATORY_REFERENCE => true])
            ->willReturn(true);

        $this->project_history_dao->method('groupAddHistory');

        $this->hook_updater->updateHookConfig(
            $repository,
            [
                HookConfig::MANDATORY_REFERENCE => true,
                'foo'                           => true,
            ]
        );
    }
}
