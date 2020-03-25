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

use Tuleap\Git\CommitMetadata\CommitMetadata;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\FileDiff;
use Tuleap\Git\GitPHP\TreeDiff;

class CommitPresenter
{
    public const ADDED_STATUS        = "A";
    public const DELETED_STATUS      = "D";
    public const MODIFIED_STATUS     = "M";
    public const TYPE_CHANGED_STATUS = "T";
    public const RENAMED_STATUS      = "R";

    public $description;
    public $has_description;
    public $number_of_parents;
    /**
     * @var bool
     */
    public $is_diff_between_two_commits;
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
    /** @var \Codendi_HTMLPurifier */
    public $purifier;

    public function __construct(Commit $commit, CommitMetadata $metadata, TreeDiff $tree_diff)
    {
        $this->commit                      = $commit;
        $this->description                 = $this->commit->getDescription();
        $this->has_description             = ! empty($this->description);
        $this->number_of_parents           = count($commit->getParents());
        $this->is_diff_between_two_commits = $this->isDiffBetweenTwoCommits();
        $this->purifier                    = \Codendi_HTMLPurifier::instance();

        $this->stats_added   = 0;
        $this->stats_removed = 0;
        foreach ($tree_diff as $line_diff) {
            \assert($line_diff instanceof FileDiff);
            if ($line_diff->hasStats()) {
                $this->stats_added   += (int) $line_diff->getAddedStats();
                $this->stats_removed += (int) $line_diff->getRemovedStats();
            }
        }

        $this->author    = CommitUserPresenter::buildFromTuleapUser($metadata->getAuthor());
        $this->committer = CommitUserPresenter::buildFromTuleapUser($metadata->getCommitter());
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

    public function getCommitDiffSideBySideLink()
    {
        return '?' .
            http_build_query(
                [
                    'a' => 'commitdiff',
                    'h' => $this->commit->getHash(),
                    'o' => 'side-by-side'
                ]
            );
    }

    public function getCommitListLink()
    {
        return '?' . http_build_query(['a' => 'commit', 'h' => $this->commit->getHash()]);
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

    /**
     * @return bool
     */
    private function isDiffBetweenTwoCommits()
    {
        if (! isset($_GET['a']) && $_GET['a'] !== "commitdiff") {
            return false;
        }

        return isset($_GET['hp']);
    }
}
