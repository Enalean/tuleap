<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace GitPHP\Commit;

use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\FileDiff;
use Tuleap\Git\GitPHP\TreeDiff;

class CommitPresenter
{
    const ADDED_STATUS        = "A";
    const DELETED_STATUS      = "D";
    const MODIFIED_STATUS     = "M";
    const TYPE_CHANGED_STATUS = "T";
    const RENAMED_STATUS      = "R";

    public $description;
    public $has_description;
    public $number_of_parents;
    /** @var Commit */
    private $commit;
    /** @var int */
    public $stats_removed;
    /** @var int */
    public $stats_added;
    /** @var CommitUserPresenter */
    public $author;
    /** @var CommitUserPresenter */
    public $committer;

    public function __construct(Commit $commit, TreeDiff $tree_diff)
    {
        $this->commit            = $commit;
        $this->description       = $this->buildCommitDescription();
        $this->has_description   = ! empty($this->description);
        $this->number_of_parents = count($commit->getParents());

        $this->stats_added   = 0;
        $this->stats_removed = 0;
        /** @var FileDiff $line_diff */
        foreach ($tree_diff as $line_diff) {
            if ($line_diff->hasStats()) {
                $this->stats_added   += (int) $line_diff->getAddedStats();
                $this->stats_removed += (int) $line_diff->getRemovedStats();
            }
        }

        $this->author = new CommitUserPresenter();
        $this->author->build($commit->getAuthorEmail());

        $this->committer = new CommitUserPresenter();
        $this->committer->build($commit->getCommitterEmail());
    }

    private function buildCommitDescription()
    {
        $comment = $this->commit->getComment();
        array_shift($comment);

        return trim(implode("\n", $comment));
    }

    public function getStatusClassname(FileDiff $diff_line)
    {
        switch ($diff_line->getStatus()) {
            case self::ADDED_STATUS:
                return "git-repository-commit-file-added";
            case self::DELETED_STATUS:
                return "git-repository-commit-file-deleted";
            case self::MODIFIED_STATUS:
            case self::TYPE_CHANGED_STATUS:
            case self::RENAMED_STATUS:
                return "git-repository-commit-file-changed";
            default:
                return "";
        }
    }

    public function getCommitDiffLink()
    {
        return '?' .
            http_build_query(
                [
                    'a' => 'commitdiff',
                    'h' => $this->commit->getHash()
                ]
            );
    }

    public function getDiffLink(FileDiff $diff_line)
    {
        return '?' .
            http_build_query(
                [
                    'a'  => 'blobdiff',
                    'h'  => $diff_line->getToHash(),
                    'hp' => $diff_line->getFromHash(),
                    'hb' => $this->commit->getHash(),
                    'f'  => $diff_line->getToFile()
                ]
            );
    }
}
