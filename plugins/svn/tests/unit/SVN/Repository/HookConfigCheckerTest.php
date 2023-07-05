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

use Tuleap\Test\Builders\ProjectTestBuilder;

class HookConfigCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject&HookConfigRetriever $config_hook_retriever;
    private Repository $repository;
    private HookConfigChecker $config_hook_checker;

    protected function setUp(): void
    {
        $this->config_hook_retriever = $this->createMock(\Tuleap\SVN\Repository\HookConfigRetriever::class);
        $this->config_hook_checker   = new HookConfigChecker($this->config_hook_retriever);

        $project          = ProjectTestBuilder::aProject()->build();
        $this->repository = SvnRepository::buildActiveRepository(12, 'repo01', $project);
    }

    public function testItReturnsTrueWhenCommitMessageParameterHaveChanged(): void
    {
        $this->config_hook_retriever->method('getHookConfig')->with($this->repository)->willReturn(
            new HookConfig(
                $this->repository,
                [
                    HookConfig::MANDATORY_REFERENCE       => false,
                    HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
                ]
            )
        );

        $hook_config = [
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        ];

        self::assertTrue($this->config_hook_checker->hasConfigurationChanged($this->repository, $hook_config));
    }

    public function testItReturnsTrueWhenMandatoryReferenceParameterHaveChanged(): void
    {
        $this->config_hook_retriever->method('getHookConfig')->with($this->repository)->willReturn(
            new HookConfig(
                $this->repository,
                [
                    HookConfig::MANDATORY_REFERENCE       => true,
                    HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
                ]
            )
        );

        $hook_config = [
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        ];

        self::assertTrue($this->config_hook_checker->hasConfigurationChanged($this->repository, $hook_config));
    }

    public function testItReturnsFalseWhenNoChangeAreMade(): void
    {
        $this->config_hook_retriever->method('getHookConfig')->with($this->repository)->willReturn(
            new HookConfig(
                $this->repository,
                [
                    HookConfig::MANDATORY_REFERENCE       => false,
                    HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
                ]
            )
        );

        $hook_config = [
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        ];

        self::assertFalse($this->config_hook_checker->hasConfigurationChanged($this->repository, $hook_config));
    }
}
