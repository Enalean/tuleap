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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HookConfigCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HookConfigRetriever
     */
    private $config_hook_retriever;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var HookConfigChecker
     */
    private $config_hook_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config_hook_retriever = \Mockery::spy(\Tuleap\SVN\Repository\HookConfigRetriever::class);
        $this->config_hook_checker   = new HookConfigChecker($this->config_hook_retriever);

        $project                     = \Mockery::mock(\Project::class);
        $this->repository            = new Repository(12, 'repo01', '', '', $project);
    }

    public function testItReturnsTrueWhenCommitMessageParameterHaveChanged(): void
    {
        $this->config_hook_retriever->shouldReceive('getHookConfig')->withArgs([$this->repository])->andReturn(
            new HookConfig(
                $this->repository,
                [
                    HookConfig::MANDATORY_REFERENCE       => false,
                    HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
                ]
            )
        );

        $hook_config = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $this->assertTrue($this->config_hook_checker->hasConfigurationChanged($this->repository, $hook_config));
    }

    public function testItReturnsTrueWhenMandatoryReferenceParameterHaveChanged(): void
    {
        $this->config_hook_retriever->shouldReceive('getHookConfig')->withArgs([$this->repository])->andReturn(
            new HookConfig(
                $this->repository,
                [
                    HookConfig::MANDATORY_REFERENCE       => true,
                    HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
                ]
            )
        );

        $hook_config = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $this->assertTrue($this->config_hook_checker->hasConfigurationChanged($this->repository, $hook_config));
    }

    public function testItReturnsFalseWhenNoChangeAreMade(): void
    {
        $this->config_hook_retriever->shouldReceive('getHookConfig')->withArgs([$this->repository])->andReturn(
            new HookConfig(
                $this->repository,
                [
                    HookConfig::MANDATORY_REFERENCE       => false,
                    HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
                ]
            )
        );

        $hook_config = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $this->assertFalse($this->config_hook_checker->hasConfigurationChanged($this->repository, $hook_config));
    }
}
