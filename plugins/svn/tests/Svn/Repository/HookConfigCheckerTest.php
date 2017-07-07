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

require_once __DIR__ . '/../../bootstrap.php';

class HookConfigCheckerTest extends \TuleapTestCase
{
    /**
     * @var Repository
     */
    public $repository;

    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    /**
     * @var HookConfigChecker
     */
    private $config_hook_checker;

    public function setUp()
    {
        parent::setUp();

        $this->repository_manager  = mock('Tuleap\Svn\Repository\RepositoryManager');
        $this->config_hook_checker = new HookConfigChecker($this->repository_manager);

        $project          = aMockProject()->build();
        $this->repository = new Repository(12, 'repo01', '', '', $project);
    }

    public function itReturnsTrueWhenCommitMessageParameterHaveChanged()
    {
        stub($this->repository_manager)->getHookConfig($this->repository)->returns(
            new HookConfig(
                $this->repository,
                array(
                    HookConfig::MANDATORY_REFERENCE       => false,
                    HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
                )
            )
        );

        $hook_config = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $this->assertTrue($this->config_hook_checker->hasConfigurationChanged($this->repository, $hook_config));
    }

    public function itReturnsTrueWhenMandatoryReferenceParameterHaveChanged()
    {
        stub($this->repository_manager)->getHookConfig($this->repository)->returns(
            new HookConfig(
                $this->repository,
                array(
                    HookConfig::MANDATORY_REFERENCE       => true,
                    HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
                )
            )
        );

        $hook_config = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $this->assertTrue($this->config_hook_checker->hasConfigurationChanged($this->repository, $hook_config));
    }

    public function itReturnsFalseWhenNoChangeAreMade()
    {
        stub($this->repository_manager)->getHookConfig($this->repository)->returns(
            new HookConfig(
                $this->repository,
                array(
                    HookConfig::MANDATORY_REFERENCE       => false,
                    HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
                )
            )
        );

        $hook_config = array(
            HookConfig::MANDATORY_REFERENCE       => false,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => false,
        );

        $this->assertFalse($this->config_hook_checker->hasConfigurationChanged($this->repository, $hook_config));
    }
}
