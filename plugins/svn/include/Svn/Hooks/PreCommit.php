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

namespace Tuleap\Svn\Hooks;

use SVN_CommitToTagDeniedException;
use Tuleap\Svn\Admin\ImmutableTagFactory;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\HookConfig;
use Tuleap\Svn\Commit\CommitInfoEnhancer;
use Tuleap\Svn\Commit\CommitInfo;
use Tuleap\Svn\Commit\SVNLook;
use ReferenceManager;
use ForgeConfig;
use Exception;
use Logger;


class PreCommit {

    private $immutable_tag_factory;
    private $commit_info_enhancer;
    private $logger;
    private $handler;

    /** @var Repository */
    private $repository;

    /** @var RepositoryManager */
    private $repository_manager;

    private $transaction;

    public function __construct(
        $repository_path,
        $transaction,
        RepositoryManager $repository_manager,
        CommitInfoEnhancer $commit_info_enhancer,
        ImmutableTagFactory $immutable_tag_factory,
        Logger $logger)
    {
        $this->repository_manager    = $repository_manager;
        $this->immutable_tag_factory = $immutable_tag_factory;
        $this->logger                = $logger;
        $this->transaction           = $transaction;
        $this->commit_info_enhancer  = $commit_info_enhancer;
        $this->repository            = $this->repository_manager
            ->getRepositoryFromSystemPath($repository_path);

        $this->commit_info_enhancer->enhanceWithTransaction($this->repository, $transaction);
    }

    public function assertCommitMessageIsValid(ReferenceManager $reference_manager) {
        $this->assertCommitMessageIsNotEmpty();
        $this->assertCommitMessageContainsArtifactReference($reference_manager);
    }

    private function assertCommitMessageIsNotEmpty(){
        if(ForgeConfig::get('sys_allow_empty_svn_commit_message')) {
            return;
        }
        if ($this->getCommitMessage() === "") {
            throw new Exception('Commit message must not be empty');
        }
    }

    private function assertCommitMessageContainsArtifactReference(ReferenceManager $reference_manager){
        $hookcfg = $this->repository_manager->getHookConfig($this->repository);

        if(!$hookcfg->getHookConfig(HookConfig::MANDATORY_REFERENCE)) {
            return;
        }
        $commit_message = $this->getCommitMessage();
        $project = $this->repository->getProject();

        // Marvelous, extractCrossRef depends on globals group_id to find the group
        // when it's not explicit... yeah!
        $GLOBALS['group_id'] = $project->getID();
        if (! $reference_manager->stringContainsReferences($commit_message, $project)) {
            throw new Exception('Commit message must contains a reference');
        }
    }

    private function getCommitMessage() {
        return $this->commit_info_enhancer->getCommitInfo()->getCommitMessage();
    }

    public function assertCommitToTagIsAllowed() {
        if ($this->getImmutableTagFromRepository()
            && ! $this->isCommitAllowed()
        ) {
            throw new SVN_CommitToTagDeniedException("Commit to tag is not allowed");
        }
    }

    private function getImmutableTagFromRepository() {
        return $this->immutable_tag_factory->getByRepositoryId($this->repository);
    }

    private function isCommitAllowed() {
        $this->commit_info_enhancer->setTransactionPath($this->repository, $this->transaction);

        $this->logger->debug("Checking if commit is done in tag");
        foreach ($this->commit_info_enhancer->getCommitInfo()->getTransactionPath() as $path) {
            if ($this->isCommitDoneInImmutableTag($path)) {
                $this->logger->debug("$path is denied");
                return false;
            }
        }

        $this->logger->debug("Commit is allowed \o/");
        return true;
    }

    private function isCommitDoneInImmutableTag($path) {
        $paths = $this->immutable_tag_factory->getByRepositoryId($this->repository)->getPaths();
        $immutable_paths = explode(PHP_EOL, $this->getImmutableTagFromRepository()->getPaths());

        foreach ($immutable_paths as $immutable_path) {
            if ($this->isCommitForbidden($immutable_path, $path)) {
                return true;
            }
        }

        return false;
    }

    private function isCommitForbidden($immutable_path, $path) {
        $immutable_path_regexp = $this->getWellFormedRegexImmutablePath($immutable_path);

        $pattern = "%^(?:
            (?:U|D)\s+$immutable_path_regexp            # U  moduleA/tags/v1
                                                        # U  moduleA/tags/v1/toto
            |
            A\s+".$immutable_path_regexp."/[^/]+/[^/]+  # A  moduleA/tags/v1/toto
            )%x";

        if (preg_match($pattern, $path)) {
            return ! $this->isCommitDoneOnWhitelistElement($path);
        }

        return false;
    }

    private function isCommitDoneOnWhitelistElement($path) {
        $whitelist = explode(PHP_EOL, $this->getImmutableTagFromRepository()->getWhitelist());
        if (! $whitelist) {
            return false;
        }

        $whitelist_regexp = array();
        foreach ($whitelist as $whitelist_path) {
            $whitelist_regexp[] = $this->getWellFormedRegexImmutablePath($whitelist_path);
        }

        $allowed_tags = implode('|', $whitelist_regexp);

        $pattern = "%^
            A\s+(?:$allowed_tags)/[^/]+/?$  # A  tags/moduleA/v1/   (allowed)
                                            # A  tags/moduleA/toto  (allowed)
                                            # A  tags/moduleA/v1/toto (forbidden)
            %x";

        return preg_match($pattern, $path);
    }

    private function getWellFormedRegexImmutablePath($immutable_path) {
        if (strpos($immutable_path, '/') === 0) {
            $immutable_path = substr($immutable_path, 1);
        }

        if (strrpos($immutable_path, '/') === strlen($immutable_path)) {
            $immutable_path = substr($immutable_path, -1);
        }

        $immutable_path = preg_quote($immutable_path);
        $immutable_path = str_replace('\*', '[^/]+', $immutable_path);

        return $immutable_path;
    }

}

