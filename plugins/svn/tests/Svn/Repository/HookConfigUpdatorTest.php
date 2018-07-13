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

namespace Tuleap\Svn\Repository;

use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class HookConfigUpdatorTest extends TuleapTestCase
{
    /**
     * @var HookConfigUpdator
     */
    private $updator;

    /**
     * @var Repository
     */
    private $repository;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->hook_dao                  = \Mockery::spy(\Tuleap\Svn\Repository\HookDao::class);
        $this->project_history_dao       = \Mockery::spy(\ProjectHistoryDao::class);
        $this->hook_config_checker       = \Mockery::spy(\Tuleap\Svn\Repository\HookConfigChecker::class);
        $this->hook_config_sanitizer     = \Mockery::spy(\Tuleap\Svn\Repository\HookConfigSanitizer::class);
        $this->project_history_formatter = \Mockery::spy(\Tuleap\Svn\Repository\ProjectHistoryFormatter::class);

        $this->updator = new HookConfigUpdator(
            $this->hook_dao,
            $this->project_history_dao,
            $this->hook_config_checker,
            $this->hook_config_sanitizer,
            $this->project_history_formatter
        );

        $project          = aMockProject()->withId(101)->build();
        $this->repository = mockery_stub(\Tuleap\Svn\Repository\Repository::class)->getProject()->returns($project);
    }

    public function itUpdatesHookConfig()
    {
        stub($this->hook_config_checker)->hasConfigurationChanged()->returns(true);

        expect($this->hook_dao)->updateHookConfig()->once();
        expect($this->project_history_dao)->groupAddHistory()->once();

        $hook_config = array(
            'mandatory_reference'       => true,
            'commit_message_can_change' => false

        );

        $this->hook_config_sanitizer->shouldReceive('sanitizeHookConfigArray')->with($hook_config)->andReturn($hook_config);

        $this->updator->updateHookConfig($this->repository, $hook_config);
    }

    public function itDoesNothingIfNothingChanged()
    {
        stub($this->hook_config_checker)->hasConfigurationChanged()->returns(false);

        expect($this->hook_dao)->updateHookConfig()->never();
        expect($this->project_history_dao)->groupAddHistory()->never();

        $hook_config = array(
            'mandatory_reference'       => true,
            'commit_message_can_change' => false

        );

        $this->hook_config_sanitizer->shouldReceive('sanitizeHookConfigArray')->with($hook_config)->andReturn($hook_config);

        $this->updator->updateHookConfig($this->repository, $hook_config);
    }

    public function itCreatesHookConfig()
    {
        stub($this->hook_config_checker)->hasConfigurationChanged()->returns(true);

        expect($this->hook_dao)->updateHookConfig()->once();
        expect($this->project_history_dao)->groupAddHistory()->never();

        $hook_config = array(
            'mandatory_reference'       => true,
            'commit_message_can_change' => false

        );

        $this->hook_config_sanitizer->shouldReceive('sanitizeHookConfigArray')->with($hook_config)->andReturn($hook_config);

        $this->updator->initHookConfiguration($this->repository, $hook_config);
    }

    public function itCreatesHookConfigInAnyCases()
    {
        stub($this->hook_config_checker)->hasConfigurationChanged()->returns(false);

        expect($this->hook_dao)->updateHookConfig()->once();
        expect($this->project_history_dao)->groupAddHistory()->never();

        $hook_config = array(
            'mandatory_reference'       => true,
            'commit_message_can_change' => false

        );

        $this->hook_config_sanitizer->shouldReceive('sanitizeHookConfigArray')->with($hook_config)->andReturn($hook_config);

        $this->updator->initHookConfiguration($this->repository, $hook_config);
    }
}
