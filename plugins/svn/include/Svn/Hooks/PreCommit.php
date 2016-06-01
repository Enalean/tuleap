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
use Tuleap\Svn\Commit\CommitInfoEnhancer;
use Tuleap\Svn\Commit\CommitMessageValidator;
use Tuleap\Svn\Commit\CommitInfo;
use Tuleap\Svn\Commit\SVNLook;
use ReferenceManager;
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
        $validator = new CommitMessageValidator($this->repository, $this->getCommitMessage());
        $validator->assertCommitMessageIsValid($this->repository_manager, $reference_manager);
    }

    private function getCommitMessage() {
        return $this->commit_info_enhancer->getCommitInfo()->getCommitMessage();
    }

    public function assertCommitToTagIsAllowed() {
        if ($this->repositoryUsesImmutableTags()
            && ! $this->isCommitAllowed()
        ) {
            throw new SVN_CommitToTagDeniedException("Commit to tag is not allowed");
        }
    }

    private function repositoryUsesImmutableTags() {
        return (bool) $this->getImmutableTagFromRepository()->getPaths();
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
        $immutable_path = trim($immutable_path, '/');
        $immutable_path = preg_quote($immutable_path);
        $immutable_path = str_replace('\*', '[^/]+', $immutable_path);

        return $immutable_path;
    }

}
