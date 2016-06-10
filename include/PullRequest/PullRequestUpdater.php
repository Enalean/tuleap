<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use Git_Command_Exception;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentUpdater;
use \Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;
use PFUser;
use ForgeConfig;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FileSystemIterator;

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
     * TimelineEventCreator
     */
    private $timeline_event_creator;

    public function __construct(
        Factory $pull_request_factory,
        PullRequestMerger $pull_request_merger,
        InlineCommentDao $inline_comment_dao,
        InlineCommentUpdater $inline_comment_updater,
        FileUniDiffBuilder $diff_builder,
        TimelineEventCreator $timeline_event_creator
    )
    {
        $this->pull_request_factory   = $pull_request_factory;
        $this->pull_request_merger    = $pull_request_merger;
        $this->inline_comment_dao     = $inline_comment_dao;
        $this->inline_comment_updater = $inline_comment_updater;
        $this->diff_builder           = $diff_builder;
        $this->timeline_event_creator = $timeline_event_creator;
    }

    public function updatePullRequests(PFUser $user, GitExec $git_exec, GitRepository $repository, $branch_name, $new_rev)
    {
        $prs = $this->pull_request_factory->getOpenedBySourceBranch($repository, $branch_name);
        foreach ($prs as $pr) {
            $this->pull_request_factory->updateSourceRev($pr, $new_rev);

            $merge_status = $this->pull_request_merger->detectMergeabilityStatus($git_exec, $pr);
            $this->pull_request_factory->updateMergeStatus($pr, $merge_status);

            $ancestor_rev = $this->getCommonAncestorRev($git_exec, $pr);
            if ($ancestor_rev != $pr->getSha1Dest()) {
                $this->pull_request_factory->updateDestRev($pr, $ancestor_rev);
                $this->updateInlineCommentsOnRebase($git_exec, $pr, $ancestor_rev, $new_rev);
                $this->timeline_event_creator->storeRebaseEvent($pr, $user, $new_rev);
            } else {
                $this->updateInlineCommentsWhenSourceChanges($git_exec, $pr, $new_rev);
                $this->timeline_event_creator->storeUpdateEvent($pr, $user, $new_rev);
            }
        }

        $prs = $this->pull_request_factory->getOpenedByDestinationBranch($repository, $branch_name);
        foreach ($prs as $pr) {
            $merge_status = $this->pull_request_merger->detectMergeabilityStatus($git_exec, $pr);
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

    private function updateInlineCommentsWhenSourceChanges(GitExec $git_exec, PullRequest $pull_request, $new_rev)
    {
        $comments_by_file = $this->getInlineCommentsByFile($pull_request);
        foreach ($comments_by_file as $file_path => $comments) {
            $original_diff        = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $pull_request->getSha1Dest(), $pull_request->getSha1Src());
            $changes_diff         = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $pull_request->getSha1Src(), $new_rev);
            $targeted_diff        = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $pull_request->getSha1Dest(), $new_rev);

            if (count($changes_diff->getLines()) > 0) {
                $comments_to_update = $this->inline_comment_updater->updateWhenSourceChanges($comments, $original_diff, $changes_diff, $targeted_diff);
                $this->saveInDb($comments_to_update);
            }
        }

    }

    private function updateInlineCommentsOnRebase(GitExec $git_exec, PullRequest $pull_request, $new_ancestor_rev, $new_rev)
    {
        $comments_by_file = $this->getInlineCommentsByFile($pull_request);
        foreach ($comments_by_file as $file_path => $comments) {
            $original_diff        = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $pull_request->getSha1Dest(), $new_rev);
            $changes_diff         = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $pull_request->getSha1Dest(), $new_ancestor_rev);
            $targeted_diff        = $this->diff_builder->buildFileUniDiff($git_exec, $file_path, $new_ancestor_rev, $new_rev);

            $comments_to_update = $this->inline_comment_updater->updateOnRebase($comments, $original_diff, $changes_diff, $targeted_diff);
            $this->saveInDb($comments_to_update);
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
