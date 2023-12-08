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

use Tuleap\SVNCore\Repository;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class HookConfigUpdatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HookDao
     */
    private $hook_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HookConfigChecker
     */
    private $hook_config_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HookConfigSanitizer
     */
    private $hook_config_sanitizer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectHistoryFormatter
     */
    private $project_history_formatter;
    private HookConfigUpdator $updator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Repository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hook_dao                  = $this->createMock(\Tuleap\SVN\Repository\HookDao::class);
        $this->project_history_dao       = $this->createMock(\ProjectHistoryDao::class);
        $this->hook_config_checker       = $this->createMock(\Tuleap\SVN\Repository\HookConfigChecker::class);
        $this->hook_config_sanitizer     = $this->createMock(\Tuleap\SVN\Repository\HookConfigSanitizer::class);
        $this->project_history_formatter = $this->createMock(\Tuleap\SVN\Repository\ProjectHistoryFormatter::class);

        $this->updator = new HookConfigUpdator(
            $this->hook_dao,
            $this->project_history_dao,
            $this->hook_config_checker,
            $this->hook_config_sanitizer,
            $this->project_history_formatter
        );

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->repository = $this->createMock(\Tuleap\SVNCore\Repository::class);
        $this->repository->method('getProject')->willReturn($project);
        $this->repository->method('getId')->willReturn(42);
    }

    public function testItUpdatesHookConfig(): void
    {
        $this->repository->method('getName')->willReturn("repo name");

        $this->hook_config_checker->method('hasConfigurationChanged')->willReturn(true);

        $this->hook_dao->expects(self::once())->method('updateHookConfig');
        $this->project_history_dao->expects(self::once())->method('groupAddHistory');

        $hook_config = [
            'mandatory_reference'       => true,
            'commit_message_can_change' => false,

        ];

        $this->project_history_formatter->method('getHookConfigHistory')->willReturn('');
        $this->hook_config_sanitizer->method('sanitizeHookConfigArray')->with($hook_config)->willReturn($hook_config);

        $this->updator->updateHookConfig($this->repository, $hook_config);
    }

    public function testItDoesNothingIfNothingChanged(): void
    {
        $this->hook_config_checker->method('hasConfigurationChanged')->willReturn(false);

        $this->hook_dao->expects(self::never())->method('updateHookConfig');
        $this->project_history_dao->expects(self::never())->method('groupAddHistory');

        $hook_config = [
            'mandatory_reference'       => true,
            'commit_message_can_change' => false,

        ];

        $this->hook_config_sanitizer->method('sanitizeHookConfigArray')->with($hook_config)->willReturn($hook_config);

        $this->updator->updateHookConfig($this->repository, $hook_config);
    }

    public function testItCreatesHookConfig(): void
    {
        $this->hook_config_checker->method('hasConfigurationChanged')->willReturn(true);

        $this->hook_dao->expects(self::once())->method('updateHookConfig');
        $this->project_history_dao->expects(self::never())->method('groupAddHistory');

        $hook_config = [
            'mandatory_reference'       => true,
            'commit_message_can_change' => false,

        ];

        $this->hook_config_sanitizer->method('sanitizeHookConfigArray')->with($hook_config)->willReturn($hook_config);

        $this->updator->initHookConfiguration($this->repository, $hook_config);
    }

    public function testItCreatesHookConfigInAnyCases(): void
    {
        $this->hook_config_checker->method('hasConfigurationChanged')->willReturn(false);

        $this->hook_dao->expects(self::once())->method('updateHookConfig');
        $this->project_history_dao->expects(self::never())->method('groupAddHistory');

        $hook_config = [
            'mandatory_reference'       => true,
            'commit_message_can_change' => false,

        ];

        $this->hook_config_sanitizer->method('sanitizeHookConfigArray')->with($hook_config)->willReturn($hook_config);

        $this->updator->initHookConfiguration($this->repository, $hook_config);
    }
}
