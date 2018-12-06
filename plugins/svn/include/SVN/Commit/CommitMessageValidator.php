<?php
/**
 * Copyright Enalean (c) 2016 - 2018. All rights reserved.
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

namespace Tuleap\SVN\Commit;

use Exception;
use ForgeConfig;
use ReferenceManager;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\Repository;

class CommitMessageValidator
{
    /** @var Repository */
    private $repository;

    private $commit_message;
    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    public function __construct(
        Repository $repository,
        $commit_message,
        HookConfigRetriever $hook_config_retriever,
        ReferenceManager $reference_manager
    ) {
        $this->repository            = $repository;
        $this->commit_message        = $commit_message;
        $this->hook_config_retriever = $hook_config_retriever;
        $this->reference_manager     = $reference_manager;
    }

    /**
     * @throws Exception
     */
    public function assertCommitMessageIsValid()
    {
        $this->assertCommitMessageIsNotEmpty();
        $this->assertCommitMessageContainsArtifactReference();
    }

    private function assertCommitMessageIsNotEmpty()
    {
        if (ForgeConfig::get('sys_allow_empty_svn_commit_message')) {
            return;
        }
        if ($this->commit_message === "") {
            throw new Exception('Commit message must not be empty');
        }
    }

    private function assertCommitMessageContainsArtifactReference()
    {
        $hook_config = $this->hook_config_retriever->getHookConfig($this->repository);
        if (! $hook_config->getHookConfig(HookConfig::MANDATORY_REFERENCE)) {
            return;
        }

        $project = $this->repository->getProject();
        if (! $this->reference_manager->stringContainsReferences($this->commit_message, $project)) {
            throw new Exception('Commit message must contains a reference');
        }
    }
}
