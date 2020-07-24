<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

class HookConfigUpdatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HookDao
     */
    private $hook_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HookConfigChecker
     */
    private $hook_config_checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HookConfigSanitizer
     */
    private $hook_config_sanitizer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectHistoryFormatter
     */
    private $project_history_formatter;
    /**
     * @var HookConfigUpdator
     */
    private $updator;

    /**
     * @var Repository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hook_dao                  = \Mockery::spy(\Tuleap\SVN\Repository\HookDao::class);
        $this->project_history_dao       = \Mockery::spy(\ProjectHistoryDao::class);
        $this->hook_config_checker       = \Mockery::spy(\Tuleap\SVN\Repository\HookConfigChecker::class);
        $this->hook_config_sanitizer     = \Mockery::spy(\Tuleap\SVN\Repository\HookConfigSanitizer::class);
        $this->project_history_formatter = \Mockery::spy(\Tuleap\SVN\Repository\ProjectHistoryFormatter::class);

        $this->updator = new HookConfigUpdator(
            $this->hook_dao,
            $this->project_history_dao,
            $this->hook_config_checker,
            $this->hook_config_sanitizer,
            $this->project_history_formatter
        );

        $project          = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(101);

        $this->repository = Mockery::mock(\Tuleap\SVN\Repository\Repository::class);
        $this->repository->shouldReceive('getProject')->andReturn($project);
        $this->repository->shouldReceive('getId')->andReturn(42);
    }

    public function testItUpdatesHookConfig(): void
    {
        $this->repository->shouldReceive('getName')->andReturn("repo name");

        $this->hook_config_checker->shouldReceive('hasConfigurationChanged')->andReturn(true);

        $this->hook_dao->shouldReceive('updateHookConfig')->once();
        $this->project_history_dao->shouldReceive('groupAddHistory')->once();

        $hook_config = [
            'mandatory_reference'       => true,
            'commit_message_can_change' => false

        ];

        $this->hook_config_sanitizer->shouldReceive('sanitizeHookConfigArray')->with($hook_config)->andReturn($hook_config);

        $this->updator->updateHookConfig($this->repository, $hook_config);
    }

    public function testItDoesNothingIfNothingChanged(): void
    {
        $this->hook_config_checker->shouldReceive('hasConfigurationChanged')->andReturn(false);

        $this->hook_dao->shouldReceive('updateHookConfig')->never();
        $this->project_history_dao->shouldReceive('groupAddHistory')->never();

        $hook_config = [
            'mandatory_reference'       => true,
            'commit_message_can_change' => false

        ];

        $this->hook_config_sanitizer->shouldReceive('sanitizeHookConfigArray')->with($hook_config)->andReturn($hook_config);

        $this->updator->updateHookConfig($this->repository, $hook_config);
    }

    public function testItCreatesHookConfig(): void
    {
        $this->hook_config_checker->shouldReceive('hasConfigurationChanged')->andReturn(true);

        $this->hook_dao->shouldReceive('updateHookConfig')->once();
        $this->project_history_dao->shouldReceive('groupAddHistory')->never();

        $hook_config = [
            'mandatory_reference'       => true,
            'commit_message_can_change' => false

        ];

        $this->hook_config_sanitizer->shouldReceive('sanitizeHookConfigArray')->with($hook_config)->andReturn($hook_config);

        $this->updator->initHookConfiguration($this->repository, $hook_config);
    }

    public function testItCreatesHookConfigInAnyCases(): void
    {
        $this->hook_config_checker->shouldReceive('hasConfigurationChanged')->andReturn(false);

        $this->hook_dao->shouldReceive('updateHookConfig')->once();
        $this->project_history_dao->shouldReceive('groupAddHistory')->never();

        $hook_config = [
            'mandatory_reference'       => true,
            'commit_message_can_change' => false

        ];

        $this->hook_config_sanitizer->shouldReceive('sanitizeHookConfigArray')->with($hook_config)->andReturn($hook_config);

        $this->updator->initHookConfiguration($this->repository, $hook_config);
    }
}
