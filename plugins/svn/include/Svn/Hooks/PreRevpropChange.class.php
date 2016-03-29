<?php
/**
 * Copyright Sogilis (c) 2016. All rights reserved.
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

use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\HookConfig;
use Tuleap\Svn\Commit\CommitMessageValidator;
use ReferenceManager;
use Exception;

class PreRevpropChange {

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var HookConfig
     */
    private $hook_config;

    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    private $action;
    private $propname;
    private $new_commit_message;

    public function __construct(
        $repository_path,
        $action,
        $propname,
        $new_commit_message,
        RepositoryManager $repository_manager
    ) {
        $this->repository_manager = $repository_manager;
        $this->repository  = $repository_manager->getRepositoryFromSystemPath($repository_path);
        $this->hook_config = $repository_manager->getHookConfig($this->repository);
        $this->action = $action;
        $this->propname = $propname;
        $this->new_commit_message = $new_commit_message;
    }

    public function checkAuthorized(ReferenceManager $reference_manager) {
        if (! ($this->action == 'M' && $this->propname == 'svn:log')) {
            throw new Exception('Cannot modify anything but svn:log');
        }
        if(!$this->hook_config->getHookConfig(HookConfig::COMMIT_MESSAGE_CAN_CHANGE)) {
            throw new Exception("Commit message is not allowed to change.");
        }

        $validator = new CommitMessageValidator($this->repository, $this->new_commit_message);
        $validator->assertCommitMessageIsValid($this->repository_manager, $reference_manager);
    }

}
