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
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentUpdater;
use \Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;

class PullRequestUpdater
{

    /**
     * @var Factory
     */
    private $pull_request_factory;

    /**
     * \Tuleap\PullRequest\InlineComment\Dao
     */
    private $inline_comment_dao;

    /**
     * InlineCommentUpdater
     */
    private $inline_comment_updater;

    public function __construct(
        Factory $pull_request_factory,
        InlineCommentDao $inline_comment_dao,
        InlineCommentUpdater $inline_comment_updater,
        FileUniDiffBuilder $diff_builder
    )
    {
        $this->pull_request_factory   = $pull_request_factory;
        $this->inline_comment_dao     = $inline_comment_dao;
        $this->inline_comment_updater = $inline_comment_updater;
        $this->diff_builder           = $diff_builder;
    }

    public function updatePullRequests(GitExec $git_exec, GitRepository $repository, $src_branch_name, $new_rev)
    {
        $prs = $this->pull_request_factory->getOpenedBySourceBranch($repository, $src_branch_name);
        foreach ($prs as $pr) {
            $this->pull_request_factory->updateSourceRev($pr, $new_rev);
            $ancestor_rev = $git_exec->getCommonAncestor($pr->getBranchSrc(), $pr->getBranchDest());
            if ($ancestor_rev != $pr->getSha1Dest()) {
                $this->pull_request_factory->updateDestRev($pr, $ancestor_rev);
                $this->updateInlineCommentsOnRebase($git_exec, $pr, $ancestor_rev, $new_rev);
            } else {
                $this->updateInlineCommentsWhenSourceChanges($git_exec, $pr, $new_rev);
            }
        }
    }

    private function updateInlineCommentsWhenSourceChanges(GitExec $git_exec, PullRequest $pull_request, $new_rev)
    {
        $comments_by_file = $this->getInlineCommentsByFile($pull_request);
        foreach ($comments_by_file as $file_path => $comments) {
            $dest_content         = $git_exec->getFileContent($pull_request->getSha1Dest(), $file_path);
            $new_src_content      = $git_exec->getFileContent($new_rev, $file_path);
            $src_content          = $git_exec->getFileContent($pull_request->getSha1Src(), $file_path);
            $original_diff        = $this->diff_builder->buildFileUniDiff($dest_content, $src_content);
            $changes_diff         = $this->diff_builder->buildFileUniDiff($src_content, $new_src_content);
            $targeted_diff        = $this->diff_builder->buildFileUniDiff($dest_content, $new_src_content);

            $comments_to_update = $this->inline_comment_updater->updateWhenSourceChanges($comments, $original_diff, $changes_diff, $targeted_diff);
            $this->saveInDb($comments_to_update);
        }

    }

    private function updateInlineCommentsOnRebase(GitExec $git_exec, PullRequest $pull_request, $new_ancestor_rev, $new_rev)
    {
        $comments_by_file = $this->getInlineCommentsByFile($pull_request);
        foreach ($comments_by_file as $file_path => $comments) {
            $old_dest_content     = $git_exec->getFileContent($pull_request->getSha1Dest(), $file_path);
            $new_dest_content     = $git_exec->getFileContent($new_ancestor_rev, $file_path);
            $src_content          = $git_exec->getFileContent($new_rev, $file_path);
            $original_diff        = $this->diff_builder->buildFileUniDiff($old_dest_content, $src_content);
            $changes_diff         = $this->diff_builder->buildFileUniDiff($old_dest_content, $new_dest_content);
            $targeted_diff        = $this->diff_builder->buildFileUniDiff($new_dest_content, $src_content);

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
