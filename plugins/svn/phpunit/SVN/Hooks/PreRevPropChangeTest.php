<?php
/**
 * Copyright Enalean (c) 2016 - present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\SVN\Hooks;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ReferenceManager;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

class PreRevPropChangeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;

    /** @var string repository path */
    private $repo_path;

    /** @var RepositoryManager */
    private $repo_manager;

    /** @var HookConfig */
    private $hook_config;

    /** @var PreRevPropChange */
    private $hook;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = Mockery::mock(Repository::class);
        $repository->shouldReceive('getProject')->andReturn(Mockery::mock(\Project::class));

        $this->repo_manager = Mockery::mock(RepositoryManager::class);
        $this->hook_config = Mockery::mock(HookConfig::class);
        $this->hook_config_retriever = \Mockery::spy(HookConfigRetriever::class);
        $this->repo_path = "FOO";

        $this->repo_manager->shouldReceive('getRepositoryFromSystemPath')->andReturn($repository);
        $this->hook_config_retriever->shouldReceive('getHookConfig')->andReturn($this->hook_config);

        $this->hook = new PreRevpropChange(
            $this->repo_path,
            'M',
            'svn:log',
            'New Commit Message',
            $this->repo_manager,
            $this->hook_config_retriever
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testItRejectsPropChangeIfNotAllowed(): void
    {
        $reference_manager = Mockery::mock(ReferenceManager::class);
        $this->hook_config->shouldReceive('getHookConfig')->withArgs(
            [HookConfig::COMMIT_MESSAGE_CAN_CHANGE]
        )->andReturn(false);
        $this->hook_config->shouldReceive('getHookConfig')->withArgs([HookConfig::MANDATORY_REFERENCE])->andReturn(
            false
        );

        $this->expectException('Exception');
        $this->hook->checkAuthorized($reference_manager);
    }

    public function testItAllowsPropChangeIfNotAllowed(): void
    {
        $reference_manager = Mockery::mock(ReferenceManager::class);
        $reference_manager->shouldReceive('stringContainsReferences')->andReturnTrue();
        $this->hook_config->shouldReceive('getHookConfig')->withArgs(
            [HookConfig::COMMIT_MESSAGE_CAN_CHANGE]
        )->andReturn(true);
        $this->hook_config->shouldReceive('getHookConfig')->withArgs([HookConfig::MANDATORY_REFERENCE])->andReturn(
            false
        );

        $this->hook->checkAuthorized($reference_manager);
    }
}
