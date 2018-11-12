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

namespace GitPHP\Shortlog;

use GitPHP\Commit\CommitUserPresenter;
use Tuleap\Git\CommitMetadata\CommitMetadata;
use Tuleap\Git\GitPHP\Commit;

class ShortlogCommitPresenter
{
    /** @var Commit */
    public $commit;
    /** @var string */
    public $short_id;
    /**
     * @var CommitUserPresenter
     */
    public $author;
    /**
     * @var CommitUserPresenter
     */
    public $committer;
    /** @var string */
    public $commit_date;

    public function __construct(
        Commit $commit,
        CommitMetadata $commit_metadata
    ) {
        $this->commit   = $commit;
        $this->short_id = substr($commit->GetHash(), 0, 10);

        $this->author    = CommitUserPresenter::buildFromTuleapUser($commit_metadata->getAuthor());
        $this->committer = CommitUserPresenter::buildFromTuleapUser($commit_metadata->getCommitter());

        $committed_on      = new \DateTimeImmutable('@' . $commit->GetCommitterEpoch());
        $this->commit_date = $committed_on->format($GLOBALS['Language']->getText('system', 'datefmt'));
    }
}
