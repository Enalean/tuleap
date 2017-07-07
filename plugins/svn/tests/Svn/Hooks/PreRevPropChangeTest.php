<?php
/**
 * Copyright Enalean (c) 2016 - 2017. All rights reserved.
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


namespace Tuleap\Svn\Hooks;

use Tuleap\Svn\Repository\HookConfig;
use Tuleap\Svn\Repository\HookConfigRetriever;
use Tuleap\Svn\Repository\RepositoryManager;
use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class PreRevPropChangeTest extends TuleapTestCase {
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

    public function setUp() {
        global $Language;
        parent::setUp();

        $repo                        = safe_mock('Tuleap\Svn\Repository\Repository');
        $this->repo_manager          = safe_mock('Tuleap\Svn\Repository\RepositoryManager');
        $this->hook_config           = safe_mock('Tuleap\Svn\Repository\HookConfig');
        $this->hook_config_retriever = mock('Tuleap\Svn\Repository\HookConfigRetriever');
        $this->repo_path             = "FOO";

        stub($this->repo_manager)->getRepositoryFromSystemPath()->returns($repo);
        stub($this->hook_config_retriever)->getHookConfig()->returns($this->hook_config);

        $Language = mock('BaseLanguage');
    }

    public function tearDown() {
        global $Language;
        unset($Language);
        parent::tearDown();
    }

    private function changeRevProp()
    {
        $this->hook = new PreRevPropChange(
            $this->repo_path,
            'M',
            'svn:log',
            'New Commit Message',
            $this->repo_manager,
            $this->hook_config_retriever
        );
    }

    public function itRejectsPropChangeIfNotAllowed() {
        $ref_manager = safe_mock('ReferenceManager');
        stub($this->hook_config)->getHookConfig(HookConfig::COMMIT_MESSAGE_CAN_CHANGE)->returns(false);
        stub($this->hook_config)->getHookConfig(HookConfig::MANDATORY_REFERENCE)->returns(false);

        $this->changeRevProp();

        $this->expectException('Exception');
        $this->hook->checkAuthorized($ref_manager);
    }

    public function itAllowsPropChangeIfNotAllowed() {
        $ref_manager = safe_mock('ReferenceManager');
        stub($this->hook_config)->getHookConfig(HookConfig::COMMIT_MESSAGE_CAN_CHANGE)->returns(true);
        stub($this->hook_config)->getHookConfig(HookConfig::MANDATORY_REFERENCE)->returns(false);

        $this->changeRevProp();

        $this->hook->checkAuthorized($ref_manager);
    }
}
