<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use \GitRepository;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceUpdater;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentUpdater;
use \Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;
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
     * @var GitPullRequestReferenceUpdater
     */
    private $git_pull_request_reference_updater;

    public function __construct(
        Factory $pull_request_factory,
        PullRequestMerger $pull_request_merger,
        InlineCommentDao $inline_comment_dao,
        InlineCommentUpdater $inline_comment_updater,
        FileUniDiffBuilder $diff_builder,
        TimelineEventCreator $timeline_event_creator,
        GitRepositoryFactory $git_repository_factory,
        GitPullRequestReferenceUpdater $git_pull_request_reference_updater
    )
    {
        $this->pull_request_factory               = $pull_request_factory;
        $this->pull_request_merger                = $pull_request_merger;
        $this->inline_comment_dao                 = $inline_comment_dao;
        $this->inline_comment_updater             = $inline_comment_updater;
        $this->diff_builder                       = $diff_builder;
        $this->timeline_event_creator             = $timeline_event_creator;
        $this->git_repository_factory             = $git_repository_factory;
        $this->git_pull_request_reference_updater = $git_pull_request_reference_updater;
    }

    public function updatePullRequests(PFUser $user, GitExec $git_exec, GitRepository $repository, $branch_name, $new_rev)
    {
        $prs = $this->pull_request_factory->getOpenedBySourceBranch($repository, $branch_name);
        foreach ($prs as $pr) {
            $this->pull_request_factory->updateSourceRev($pr, $new_rev);

            $merge_status = $this->pull_request_merger->detectMergeabilityStatus($git_exec, $pr, $new_rev, $repository);
            $this->pull_request_factory->updateMergeStatus($pr, $merge_status);

            $ancestor_rev = $this->getCommonAncestorRev($git_exec, $pr);
            if ($ancestor_rev != $pr->getSha1Dest()) {
                $this->pull_request_factory->updateDestRev($pr, $ancestor_rev);
            }
            $this->updateInlineCommentsWhenSourceChanges($git_exec, $pr, $ancestor_rev, $new_rev);
            $this->timeline_event_creator->storeUpdateEvent($pr, $user, $new_rev);

            $updated_pr             = new PullRequest(
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
                $ancestor_rev,
                $pr->getLastBuildDate(),
                $pr->getLastBuildStatus(),
                $pr->getStatus(),
                $pr->getMergeStatus()
            );
            $repository_destination = $this->git_repository_factory->getRepositoryById($pr->getRepoDestId());
            $this->git_pull_request_reference_updater->updatePullRequestReference(
                $updated_pr,
                $git_exec,
                GitExec::buildFromRepository($repository_destination),
                $repository_destination
            );
        }

        $prs = $this->pull_request_factory->getOpenedByDestinationBranch($repository, $branch_name);
        foreach ($prs as $pr) {
            $pr_repository = $this->git_repository_factory->getRepositoryById($pr->getRepositoryId());
            $pr_git_exec   = new GitExec($pr_repository->getFullPath(), $pr_repository->getFullPath());
            $merge_status  = $this->pull_request_merger->detectMergeabilityStatus($pr_git_exec, $pr, $pr->getSha1Src(), $pr_repository);
            $this->pull_request_factory->updateMergeStatus($pr, $merge_status);
        }
    }

    private function getCommonAncestorRev(GitExec $git_exec, PullRequest $pr)
    {
        if ($pr->getRepositoryId() != $pr->getRepoDestId()) {
            $git_exec->fetchRemote($pr->getRepoDestId());
            $base_ref   = 'refs/heads/' . $pr->getBranchSrc();
            $merged_ref = 'refs/remotes/' . $pr->getRepoDestId() . '/' . $pr->getBranchDest();
        } else {
            $base_ref   = $pr->getBranchSrc();
            $merged_ref = $pr->getBranchDest();
        }
        $ancestor_rev = $git_exec->getCommonAncestor($base_ref, $merged_ref);
        return $ancestor_rev;
    }

    private function updateInlineCommentsWhenSourceChanges(GitExec $git_exec, PullRequest $pull_request, $new_dest_rev, $new_src_rev)
    {
        $comments_by_file = $this->getInlineCommentsByFile($pull_request);
        foreach ($comments_by_file as $file_path => $comments) {
            $original_diff     = $this->diff_builder->buildFileUniDiffFromCommonAncestor($git_exec, $file_path, $pull_request->getSha1Dest(), $pull_request->getSha1Src());
            $changes_diff      = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $pull_request->getSha1Src(), $new_src_rev);
            $dest_changes_diff = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $pull_request->getSha1Dest(), $new_dest_rev);
            $target_diff       = $this->diff_builder->buildFileUniDiffFromCommonAncestor($git_exec, $file_path, $new_dest_rev, $new_src_rev);

            $has_src_changes   = count($changes_diff->getLines()) > 0;
            $has_dest_changes  = count($dest_changes_diff->getLines()) > 0;

            if ($has_src_changes || $has_dest_changes) {
                if (! $has_src_changes) {
                    $changes_diff = $this->diff_builder->buildFileNullDiff();
                }
                if (! $has_dest_changes) {
                    $dest_changes_diff = $this->diff_builder->buildFileNullDiff();
                }

                $comments_to_update = $this->inline_comment_updater->updateWhenSourceChanges(
                    $comments, $original_diff, $changes_diff, $dest_changes_diff, $target_diff);
                $this->saveInDb($comments_to_update);
            }
        }
    }

    private function getInlineCommentsByFile(PullRequest $pull_request)
    {
        $res = $this->inline_comment_dao->searchUpToDateByPullRequestId($pull_request->getid());

        $comments_by_file = array();
        foreach ($res as $row) {
            $comment   = InlineComment::buildFromRow($row);
            $file_path = $comment->getFilePath();
            if (! isset($comments_by_file[$file_path])) {
                $comments_by_file[$file_path] = array();
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
