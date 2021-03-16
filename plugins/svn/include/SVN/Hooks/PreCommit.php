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
use Tuleap\SVN\Commit\CollidingSHA1Validator;
use Tuleap\SVN\Commit\ImmutableTagCommitValidator;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Commit\CommitInfoEnhancer;
use Tuleap\SVN\Commit\CommitMessageValidator;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\Repository;
use Tuleap\Svn\SHA1CollisionDetector;

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

        $immutable_tag_validator = new ImmutableTagCommitValidator($this->logger, $this->immutable_tag_factory);
        $sha1_validator          = new CollidingSHA1Validator($this->svnlook, $this->sha1_collision_detector);

        $changed_paths = $this->svnlook->getTransactionPath($this->repository, $this->transaction);
        foreach ($changed_paths as $path) {
            $sha1_validator->assertPathDoesNotContainSHA1Collision($this->repository, $this->transaction, $path);
            $immutable_tag_validator->assertCommitIsNotDoneInImmutableTag($this->repository, $path);
        }
        $this->logger->debug("Commit is allowed \o/");
    }

    private function assertCommitMessageIsValid(): void
    {
        $validator = new CommitMessageValidator(
            $this->repository,
            $this->commit_info_enhancer->getCommitInfo()->getCommitMessage(),
            $this->hook_config_retriever,
            $this->reference_manager
        );
        $validator->assertCommitMessageIsValid();
    }
}
