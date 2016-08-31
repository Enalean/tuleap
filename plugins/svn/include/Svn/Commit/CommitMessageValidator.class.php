<?php
/**
 * Copyright Enalean (c) 2016. All rights reserved.
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

namespace Tuleap\Svn\Commit;

use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\HookConfig;
use ReferenceManager;
use ForgeConfig;
use Exception;

class CommitMessageValidator {

    /** @var Repository */
    private $repository;

    private $commit_message;

    public function __construct(Repository $repository, $commit_message) {
        $this->repository = $repository;
        $this->commit_message = $commit_message;
    }

    public function assertCommitMessageIsValid(RepositoryManager $repository_manager, ReferenceManager $reference_manager) {
        $this->assertCommitMessageIsNotEmpty();
        $this->assertCommitMessageContainsArtifactReference($repository_manager, $reference_manager);
    }

    private function assertCommitMessageIsNotEmpty(){
        if(ForgeConfig::get('sys_allow_empty_svn_commit_message')) {
            return;
        }
        if ($this->commit_message === "") {
            throw new Exception('Commit message must not be empty');
        }
    }

    private function assertCommitMessageContainsArtifactReference(
        RepositoryManager $repository_manager,
        ReferenceManager $reference_manager
    ) {
        $hookcfg = $repository_manager->getHookConfig($this->repository);

        if(!$hookcfg->getHookConfig(HookConfig::MANDATORY_REFERENCE)) {
            return;
        }

        $project = $this->repository->getProject();

        if (! $reference_manager->stringContainsReferences($this->commit_message, $project)) {
            throw new Exception('Commit message must contains a reference');
        }
    }
}
