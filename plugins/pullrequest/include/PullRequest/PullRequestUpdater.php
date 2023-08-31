<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use GitRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\BranchUpdate\PullRequestUpdatedEvent;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceUpdater;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentUpdater;
use Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;
use PFUser;
use GitRepositoryFactory;

class PullRequestUpdater
{
    /**
     * @var Factory
     */
    private $pull_request_factory;

    /**
     * @var PullRequestMerger
     */
    private $pull_request_merger;

    /**
     * \Tuleap\PullRequest\InlineComment\Dao
     */
    private $inline_comment_dao;

    /**
     * InlineCommentUpdater
     */
    private $inline_comment_updater;

    /**
     * @var FileUniDiffBuilder
     */
    private $diff_builder;

    /**
     * TimelineEventCreator
     */
    private $timeline_event_creator;
    /**
     * GitRepositoryFactory
     */
    private $git_repository_factory;
    /**
     * @var GitExecFactory
     */
    private $git_exec_factory;
    /**
     * @var GitPullRequestReferenceUpdater
     */
    private $git_pull_request_reference_updater;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        Factory $pull_request_factory,
        PullRequestMerger $pull_request_merger,
        InlineCommentDao $inline_comment_dao,
        InlineCommentUpdater $inline_comment_updater,
        FileUniDiffBuilder $diff_builder,
        TimelineEventCreator $timeline_event_creator,
        GitRepositoryFactory $git_repository_factory,
        GitExecFactory $git_exec_factory,
        GitPullRequestReferenceUpdater $git_pull_request_reference_updater,
        EventDispatcherInterface $event_dispatcher,
    ) {
        $this->pull_request_factory               = $pull_request_factory;
        $this->pull_request_merger                = $pull_request_merger;
        $this->inline_comment_dao                 = $inline_comment_dao;
        $this->inline_comment_updater             = $inline_comment_updater;
        $this->diff_builder                       = $diff_builder;
        $this->timeline_event_creator             = $timeline_event_creator;
        $this->git_repository_factory             = $git_repository_factory;
        $this->git_exec_factory                   = $git_exec_factory;
        $this->git_pull_request_reference_updater = $git_pull_request_reference_updater;
        $this->event_dispatcher                   = $event_dispatcher;
    }

    public function updatePullRequests(PFUser $user, GitRepository $repository, $branch_name, $new_rev)
    {
        $prs = $this->pull_request_factory->getOpenedBySourceBranch($repository, $branch_name);
        foreach ($prs as $pr) {
            $this->updatePullRequestWithNewSourceRev($pr, $user, $repository, $new_rev);
        }

        $prs = $this->pull_request_factory->getOpenedByDestinationBranch($repository, $branch_name);
        foreach ($prs as $pr) {
            $pr_repository = $this->git_repository_factory->getRepositoryById($pr->getRepoDestId());
            if ($pr_repository === null) {
                continue;
            }
            $pr_git_exec  = $this->git_exec_factory->getGitExec($pr_repository);
            $merge_status = $this->pull_request_merger->detectMergeabilityStatus(
                $pr_git_exec,
                $pr->getSha1Src(),
                $branch_name
            );
            $this->pull_request_factory->updateMergeStatus($pr, $merge_status);
        }
    }

    /**
     * @throws GitReference\GitReferenceNotFound
     * @throws \Git_Command_Exception
     */
    public function updatePullRequestWithNewSourceRev(
        PullRequest $pr,
        PFUser $user,
        GitRepository $repository,
        string $new_rev,
    ): void {
        $this->pull_request_factory->updateSourceRev($pr, $new_rev);

        $repository_destination = $this->git_repository_factory->getRepositoryById($pr->getRepoDestId());
        if ($repository_destination === null) {
            return;
        }
        $executor_repository_destination = $this->git_exec_factory->getGitExec($repository_destination);

        $updated_pr = new PullRequest(
            $pr->getId(),
            $pr->getTitle(),
            $pr->getDescription(),
            $pr->getRepositoryId(),
            $pr->getUserId(),
            $pr->getCreationDate(),
            $pr->getBranchSrc(),
            $new_rev,
            $pr->getRepoDestId(),
            $pr->getBranchDest(),
            $pr->getSha1Dest(),
            $pr->getDescriptionFormat(),
            $pr->getStatus(),
            $pr->getMergeStatus()
        );

        $executor_repository_source = $this->git_exec_factory->getGitExec($repository);
        $this->git_pull_request_reference_updater->updatePullRequestReference(
            $updated_pr,
            $executor_repository_source,
            $executor_repository_destination,
            $repository_destination
        );

        $ancestor_rev = $executor_repository_destination->getCommonAncestor($new_rev, $pr->getBranchDest());
        if ($ancestor_rev !== $pr->getSha1Dest()) {
            $this->pull_request_factory->updateDestRev($pr, $ancestor_rev);
        }
        $this->event_dispatcher->dispatch(
            PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
                $pr,
                $user,
                $pr->getSha1Src(),
                $new_rev,
                $pr->getBranchDest(),
                $ancestor_rev
            )
        );

        $merge_status = $this->pull_request_merger->detectMergeabilityStatus(
            $executor_repository_destination,
            $new_rev,
            $ancestor_rev
        );
        $this->pull_request_factory->updateMergeStatus($pr, $merge_status);

        $this->updateInlineCommentsWhenSourceChanges($executor_repository_destination, $pr, $ancestor_rev, $new_rev);
        $this->timeline_event_creator->storeUpdateEvent($pr, $user);
    }

    private function updateInlineCommentsWhenSourceChanges(GitExec $git_exec, PullRequest $pull_request, $new_dest_rev, $new_src_rev)
    {
        $comments_by_file = $this->getInlineCommentsByFile($pull_request);
        foreach ($comments_by_file as $file_path => $comments) {
            $original_diff     = $this->diff_builder->buildFileUniDiffFromCommonAncestor($git_exec, $file_path, $pull_request->getSha1Dest(), $pull_request->getSha1Src());
            $changes_diff      = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $pull_request->getSha1Src(), $new_src_rev);
            $dest_changes_diff = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $pull_request->getSha1Dest(), $new_dest_rev);
            $target_diff       = $this->diff_builder->buildFileUniDiffFromCommonAncestor($git_exec, $file_path, $new_dest_rev, $new_src_rev);

            $has_src_changes  = count($changes_diff->getLines()) > 0;
            $has_dest_changes = count($dest_changes_diff->getLines()) > 0;

            if ($has_src_changes || $has_dest_changes) {
                if (! $has_src_changes) {
                    $changes_diff = $this->diff_builder->buildFileNullDiff();
                }
                if (! $has_dest_changes) {
                    $dest_changes_diff = $this->diff_builder->buildFileNullDiff();
                }

                $comments_to_update = $this->inline_comment_updater->updateWhenSourceChanges(
                    $comments,
                    $original_diff,
                    $changes_diff,
                    $dest_changes_diff,
                    $target_diff
                );
                $this->saveInDb($comments_to_update);
            }
        }
    }

    private function getInlineCommentsByFile(PullRequest $pull_request)
    {
        $res = $this->inline_comment_dao->searchUpToDateByPullRequestId($pull_request->getid());

        $comments_by_file = [];
        foreach ($res as $row) {
            $comment   = InlineComment::buildFromRow($row);
            $file_path = $comment->getFilePath();
            if (! isset($comments_by_file[$file_path])) {
                $comments_by_file[$file_path] = [];
            }
            $comments_by_file[$file_path][] = $comment;
        }
        return $comments_by_file;
    }

    private function saveInDb(array $comments)
    {
        foreach ($comments as $comment) {
            $this->inline_comment_dao->updateComment($comment->getId(), $comment->getUnidiffOffset(), $comment->isOutdated());
        }
    }
}
