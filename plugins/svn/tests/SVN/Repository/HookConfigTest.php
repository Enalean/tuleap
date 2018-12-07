<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class HookConfigTest extends \TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();

        $project_history_dao = mock('ProjectHistoryDao');
        $project_dao         = mock('ProjectDao');
        $this->hook_dao      = mock('Tuleap\SVN\Repository\HookDao');

        $project_manager = ProjectManager::testInstance($project_dao);

        $this->project = $project_manager->getProjectFromDbRow(
            array(
                'group_id'           => 123,
                'unix_group_name'    => 'test_project',
                'access'             => 'private',
                'svn_tracker'        => null,
                'svn_can_change_log' => null
            )
        );

        $this->hook_retriever = new HookConfigRetriever($this->hook_dao, new HookConfigSanitizer());
        $hook_checker         = mock('Tuleap\SVN\Repository\HookConfigChecker');
        $this->hook_updater   = new HookConfigUpdator(
            $this->hook_dao,
            $project_history_dao,
            $hook_checker,
            new HookConfigSanitizer(),
            new ProjectHistoryFormatter()
        );
        stub($hook_checker)->hasConfigurationChanged()->returns(true);
    }

    public function tearDown()
    {
        ProjectManager::clearInstance();

        parent::tearDown();
    }

    public function itReturnsFalseForEveryConfigWHenNoCustomConfigurationIsStored()
    {
        stub($this->hook_dao)->getHookConfig(33)->returns(array());

        $repo = new Repository(33, 'reponame', '', '', $this->project);
        $cfg  = $this->hook_retriever->getHookConfig($repo);

        $mandatory_ref = $cfg->getHookConfig(HookConfig::MANDATORY_REFERENCE);
        $this->assertEqual(false, $mandatory_ref);
    }

    public function itReturnsCustomsConfigurationWhenSaved()
    {
        stub($this->hook_dao)->getHookConfig(33)->returns(
            array(
                HookConfig::MANDATORY_REFERENCE => true
            )
        );

        $repo = new Repository(33, 'reponame', '', '', $this->project);
        $cfg  = $this->hook_retriever->getHookConfig($repo);

        $mandatory_ref = $cfg->getHookConfig(HookConfig::MANDATORY_REFERENCE);
        $this->assertEqual(true, $mandatory_ref);
    }

    public function itCanChangeTheHookConfig()
    {
        $repository = new Repository(22, 'reponame', '', '', $this->project);
        stub($this->hook_dao)->updateHookConfig(
            22,
            array(HookConfig::MANDATORY_REFERENCE => true)
        )->once()->returns(true);

        $this->hook_updater->updateHookConfig(
            $repository,
            array(
                HookConfig::MANDATORY_REFERENCE => true,
                'foo'                           => true
            )
        );
    }
}
