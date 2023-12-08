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

namespace Tuleap\SVN\Commit;

use ForgeConfig;
use ReferenceManager;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVNCore\Repository;

class CommitMessageValidator
{
    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    public function __construct(
        HookConfigRetriever $hook_config_retriever,
        ReferenceManager $reference_manager,
    ) {
        $this->hook_config_retriever = $hook_config_retriever;
        $this->reference_manager     = $reference_manager;
    }

    /**
     * @throws EmptyCommitMessageException
     * @throws CommitMessageWithoutReferenceException
     */
    public function assertCommitMessageIsValid(Repository $repository, CommitInfo $commit_info): void
    {
        $this->assertCommitMessageIsNotEmpty($commit_info);
        $this->assertCommitMessageContainsArtifactReference($repository, $commit_info);
    }

    /**
     * @throws EmptyCommitMessageException
     */
    private function assertCommitMessageIsNotEmpty(CommitInfo $commit_info): void
    {
        if (ForgeConfig::get('sys_allow_empty_svn_commit_message')) {
            return;
        }
        if ($commit_info->getCommitMessage() === "") {
            throw new EmptyCommitMessageException();
        }
    }

    /**
     * @throws CommitMessageWithoutReferenceException
     */
    private function assertCommitMessageContainsArtifactReference(Repository $repository, CommitInfo $commit_info): void
    {
        $hook_config = $this->hook_config_retriever->getHookConfig($repository);
        if (! $hook_config->getHookConfig(HookConfig::MANDATORY_REFERENCE)) {
            return;
        }

        $project = $repository->getProject();
        if (! $this->reference_manager->stringContainsReferences($commit_info->getCommitMessage(), $project)) {
            throw new CommitMessageWithoutReferenceException();
        }
    }
}
