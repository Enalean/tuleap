<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
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

use Exception;
use ReferenceManager;
use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVN\Commit\CommitMessageValidator;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

class PreRevpropChange
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var HookConfig
     */
    private $hook_config;

    private $action;
    private $propname;
    private $new_commit_message;
    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;

    public function __construct(
        $repository_path,
        $action,
        $propname,
        $new_commit_message,
        RepositoryManager $repository_manager,
        HookConfigRetriever $hook_config_retriever,
    ) {
        $this->repository            = $repository_manager->getRepositoryFromSystemPath($repository_path);
        $this->hook_config           = $hook_config_retriever->getHookConfig($this->repository);
        $this->action                = $action;
        $this->propname              = $propname;
        $this->new_commit_message    = $new_commit_message;
        $this->hook_config_retriever = $hook_config_retriever;
    }

    public function checkAuthorized(ReferenceManager $reference_manager)
    {
        if (! ($this->action == 'M' && $this->propname == 'svn:log')) {
            throw new Exception('Cannot modify anything but svn:log');
        }
        if (! $this->hook_config->getHookConfig(HookConfig::COMMIT_MESSAGE_CAN_CHANGE)) {
            throw new Exception("Commit message is not allowed to change.");
        }

        $validator = new CommitMessageValidator(
            $this->hook_config_retriever,
            $reference_manager
        );

        $commit_info = new CommitInfo();
        $commit_info->setCommitMessage($this->new_commit_message);

        $validator->assertCommitMessageIsValid($this->repository, $commit_info);
    }
}
