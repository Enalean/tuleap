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

use PHPUnit\Framework\MockObject\MockObject;
use ReferenceManager;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

final class PreRevpropChangeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var HookConfigRetriever&MockObject
     */
    private $hook_config_retriever;

    private string $repo_path;

    /** @var RepositoryManager&MockObject */
    private $repo_manager;

    /** @var HookConfig&MockObject */
    private $hook_config;

    private PreRevpropChange $hook;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = $this->createMock(Repository::class);
        $repository->method('getProject')->willReturn($this->createMock(\Project::class));

        $this->repo_manager          = $this->createMock(RepositoryManager::class);
        $this->hook_config           = $this->createMock(HookConfig::class);
        $this->hook_config_retriever = $this->createMock(HookConfigRetriever::class);
        $this->repo_path             = "FOO";

        $this->repo_manager->method('getRepositoryFromSystemPath')->willReturn($repository);
        $this->hook_config_retriever->method('getHookConfig')->willReturn($this->hook_config);

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
        $reference_manager = $this->createMock(ReferenceManager::class);

        $this->hook_config->method('getHookConfig')->willReturnMap([
            [HookConfig::COMMIT_MESSAGE_CAN_CHANGE, false],
            [HookConfig::MANDATORY_REFERENCE, false],
        ]);

        $this->expectException(\Exception::class);

        $this->hook->checkAuthorized($reference_manager);
    }

    public function testItAllowsPropChangeIfNotAllowed(): void
    {
        $this->expectNotToPerformAssertions();

        $reference_manager = $this->createMock(ReferenceManager::class);
        $reference_manager->method('stringContainsReferences')->willReturn(true);

        $this->hook_config->method('getHookConfig')->willReturnMap([
            [HookConfig::COMMIT_MESSAGE_CAN_CHANGE, true],
            [HookConfig::MANDATORY_REFERENCE, false],
        ]);

        $this->hook->checkAuthorized($reference_manager);
    }
}
