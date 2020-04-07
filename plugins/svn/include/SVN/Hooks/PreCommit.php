<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

use Psr\Log\LoggerInterface;
use ReferenceManager;
use SVN_CommitToTagDeniedException;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Commit\CommitInfoEnhancer;
use Tuleap\SVN\Commit\CommitMessageValidator;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\Svn\SHA1CollisionDetector;
use Tuleap\Svn\SHA1CollisionException;

class PreCommit
{
    private $immutable_tag_factory;
    private $commit_info_enhancer;
    private $logger;

    /** @var Repository */
    private $repository;

    /** @var RepositoryManager */
    private $repository_manager;

    private $transaction;
    /**
     * @var Svnlook
     */
    private $svnlook;
    /**
     * @var SHA1CollisionDetector
     */
    private $sha1_collision_detector;
    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;

    public function __construct(
        $repository_path,
        $transaction,
        RepositoryManager $repository_manager,
        CommitInfoEnhancer $commit_info_enhancer,
        ImmutableTagFactory $immutable_tag_factory,
        Svnlook $svnlook,
        SHA1CollisionDetector $sha1_collision_detector,
        LoggerInterface $logger,
        HookConfigRetriever $hook_config_retriever
    ) {
        $this->repository_manager      = $repository_manager;
        $this->immutable_tag_factory   = $immutable_tag_factory;
        $this->logger                  = $logger;
        $this->transaction             = $transaction;
        $this->commit_info_enhancer    = $commit_info_enhancer;
        $this->svnlook                 = $svnlook;
        $this->sha1_collision_detector = $sha1_collision_detector;
        $this->repository              = $this->repository_manager->getRepositoryFromSystemPath($repository_path);
        $this->hook_config_retriever   = $hook_config_retriever;

        $this->commit_info_enhancer->enhanceWithTransaction($this->repository, $transaction);
    }

    public function assertCommitMessageIsValid(
        ReferenceManager $reference_manager
    ) {
        $validator = new CommitMessageValidator(
            $this->repository,
            $this->getCommitMessage(),
            $this->hook_config_retriever,
            $reference_manager
        );
        $validator->assertCommitMessageIsValid();
    }

    private function getCommitMessage()
    {
        return $this->commit_info_enhancer->getCommitInfo()->getCommitMessage();
    }

    public function assertCommitToTagIsAllowed()
    {
        if (
            $this->repositoryUsesImmutableTags()
            && !$this->isCommitAllowed()
        ) {
            throw new SVN_CommitToTagDeniedException("Commit to tag is not allowed");
        }
    }

    /**
     * @throws SHA1CollisionException
     * @throws \RuntimeException
     */
    public function assertCommitDoesNotContainSHA1Collision()
    {
        $changed_paths = $this->svnlook->getTransactionPath($this->repository, $this->transaction);
        foreach ($changed_paths as $path) {
            $matches = array();
            if ($this->extractFilenameFromNonDeletedPath($path, $matches)) {
                continue;
            }
            $filename    = $matches[1];
            $handle_file = $this->svnlook->getContent($this->repository, $this->transaction, $filename);
            if ($handle_file === false) {
                throw new \RuntimeException("Can't get the content of the file $filename");
            }
            $is_colliding = $this->sha1_collision_detector->isColliding($handle_file);
            pclose($handle_file);
            if ($is_colliding) {
                throw new SHA1CollisionException("Known SHA-1 collision rejected on file $filename");
            }
        }
    }

    /**
     * @param array $matches
     * @return bool
     */
    private function extractFilenameFromNonDeletedPath($path, array &$matches)
    {
        return preg_match('/^[^D]\s+(.*)$/', $path, $matches) !== 1;
    }

    private function repositoryUsesImmutableTags()
    {
        return count($this->getImmutableTagFromRepository()->getPaths()) > 0;
    }

    private function getImmutableTagFromRepository()
    {
        return $this->immutable_tag_factory->getByRepositoryId($this->repository);
    }

    private function isCommitAllowed()
    {
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

    private function isCommitDoneInImmutableTag($path)
    {
        foreach ($this->getImmutableTagFromRepository()->getPaths() as $immutable_path) {
            if ($this->isCommitForbidden($immutable_path, $path)) {
                return true;
            }
        }

        return false;
    }

    private function isCommitForbidden($immutable_path, $path)
    {
        $immutable_path_regexp = $this->getWellFormedRegexImmutablePath($immutable_path);

        $pattern = "%^(?:
            (?:U|D)\s+$immutable_path_regexp            # U  moduleA/tags/v1
                                                        # U  moduleA/tags/v1/toto
            |
            A\s+" . $immutable_path_regexp . "/[^/]+/[^/]+  # A  moduleA/tags/v1/toto
            )%x";

        if (preg_match($pattern, $path)) {
            return !$this->isCommitDoneOnWhitelistElement($path);
        }

        return false;
    }

    private function isCommitDoneOnWhitelistElement($path)
    {
        $whitelist = $this->getImmutableTagFromRepository()->getWhitelist();
        if (!$whitelist) {
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

    private function getWellFormedRegexImmutablePath($immutable_path)
    {
        $immutable_path = trim($immutable_path, '/');
        $immutable_path = preg_quote($immutable_path);
        $immutable_path = str_replace('\*', '[^/]+', $immutable_path);

        return $immutable_path;
    }
}
