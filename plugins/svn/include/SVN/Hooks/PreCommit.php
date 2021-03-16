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
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Commit\CommitInfoEnhancer;
use Tuleap\SVN\Commit\CommitMessageValidator;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\Repository;
use Tuleap\Svn\SHA1CollisionDetector;
use Tuleap\Svn\SHA1CollisionException;

class PreCommit
{
    private $immutable_tag_factory;
    private $commit_info_enhancer;
    private $logger;

    /** @var Repository */
    private $repository;

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
    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    public function __construct(
        string $transaction,
        Repository $repository,
        CommitInfoEnhancer $commit_info_enhancer,
        ImmutableTagFactory $immutable_tag_factory,
        Svnlook $svnlook,
        SHA1CollisionDetector $sha1_collision_detector,
        LoggerInterface $logger,
        HookConfigRetriever $hook_config_retriever,
        ReferenceManager $reference_manager
    ) {
        $this->repository              = $repository;
        $this->immutable_tag_factory   = $immutable_tag_factory;
        $this->logger                  = $logger;
        $this->transaction             = $transaction;
        $this->commit_info_enhancer    = $commit_info_enhancer;
        $this->svnlook                 = $svnlook;
        $this->sha1_collision_detector = $sha1_collision_detector;
        $this->hook_config_retriever   = $hook_config_retriever;
        $this->reference_manager       = $reference_manager;

        $this->commit_info_enhancer->enhanceWithTransaction($this->repository, $transaction);
    }

    public function assertCommitIsValid(): void
    {
        $this->assertCommitMessageIsValid();

        $immutable_tag = $this->getImmutableTagFromRepository();

        $changed_paths = $this->svnlook->getTransactionPath($this->repository, $this->transaction);
        foreach ($changed_paths as $path) {
            $this->assertPathDoesNotContainSHA1Collision($path);
            $this->assertCommitIsNotDoneInImmutableTag($immutable_tag, $path);
        }
        $this->logger->debug("Commit is allowed \o/");
    }

    private function assertCommitMessageIsValid(): void
    {
        $validator = new CommitMessageValidator(
            $this->repository,
            $this->getCommitMessage(),
            $this->hook_config_retriever,
            $this->reference_manager
        );
        $validator->assertCommitMessageIsValid();
    }

    private function getCommitMessage()
    {
        return $this->commit_info_enhancer->getCommitInfo()->getCommitMessage();
    }

    /**
     * @throws SHA1CollisionException
     * @throws \RuntimeException
     */
    private function assertPathDoesNotContainSHA1Collision(string $path): void
    {
        $matches = [];
        if ($this->extractFilenameFromNonDeletedPath($path, $matches)) {
            return;
        }
        $filename    = $matches[1];
        $handle_file = $this->svnlook->getContent($this->repository, $this->transaction, $filename);
        if ($handle_file === false) {
            throw new \RuntimeException("Can't get the content of the file $filename");
        }
        $is_colliding = $this->sha1_collision_detector->isColliding($handle_file);
        $this->svnlook->closeContentResource($handle_file);
        if ($is_colliding) {
            throw new SHA1CollisionException("Known SHA-1 collision rejected on file $filename");
        }
    }

    private function extractFilenameFromNonDeletedPath(string $path, array &$matches): bool
    {
        return preg_match('/^[^D]\s+(.*)$/', $path, $matches) !== 1;
    }

    private function getImmutableTagFromRepository(): ImmutableTag
    {
        return $this->immutable_tag_factory->getByRepositoryId($this->repository);
    }

    /**
     * @throws SVN_CommitToTagDeniedException
     */
    private function assertCommitIsNotDoneInImmutableTag(ImmutableTag $immutable_tag, string $path): void
    {
        $this->logger->debug("Checking if commit is done in tag: $path");
        foreach ($immutable_tag->getPaths() as $immutable_path) {
            if ($this->isCommitForbidden($immutable_tag, $immutable_path, $path)) {
                throw new SVN_CommitToTagDeniedException("Commit to tag `$immutable_path` is not allowed");
            }
        }
    }

    private function isCommitForbidden(ImmutableTag $immutable_tag, string $immutable_path, string $path): bool
    {
        $immutable_path_regexp = $this->getWellFormedRegexImmutablePath($immutable_path);

        $pattern = "%^(?:
            (?:U|D)\s+$immutable_path_regexp            # U  moduleA/tags/v1
                                                        # U  moduleA/tags/v1/toto
            |
            A\s+" . $immutable_path_regexp . "/[^/]+/[^/]+  # A  moduleA/tags/v1/toto
            )%x";

        if (preg_match($pattern, $path)) {
            return ! $this->isCommitDoneOnWhitelistElement($immutable_tag, $path);
        }

        return false;
    }

    private function isCommitDoneOnWhitelistElement(ImmutableTag $immutable_tag, string $path): bool
    {
        $whitelist = $immutable_tag->getWhitelist();
        if (! $whitelist) {
            return false;
        }

        $whitelist_regexp = [];
        foreach ($whitelist as $whitelist_path) {
            $whitelist_regexp[] = $this->getWellFormedRegexImmutablePath($whitelist_path);
        }

        $allowed_tags = implode('|', $whitelist_regexp);

        $pattern = "%^
            A\s+(?:$allowed_tags)/[^/]+/?$  # A  tags/moduleA/v1/   (allowed)
                                            # A  tags/moduleA/toto  (allowed)
                                            # A  tags/moduleA/v1/toto (forbidden)
            %x";

        return preg_match($pattern, $path) === 1;
    }

    private function getWellFormedRegexImmutablePath($immutable_path)
    {
        $immutable_path = trim($immutable_path, '/');
        $immutable_path = preg_quote($immutable_path);
        $immutable_path = str_replace('\*', '[^/]+', $immutable_path);
        $immutable_path = str_replace(" ", "\s", $immutable_path);

        return $immutable_path;
    }
}
