<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\REST\v1;

use Tuleap\Git\CommitMetadata\CommitMetadata;
use Tuleap\Git\CommitStatus\CommitStatusUnknown;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

class GitCommitRepresentation
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $author_name;
    /**
     * @var int
     */
    public $authored_date;
    /**
     * @var int
     */
    public $committed_date;
    /**
     * @var string
     */
    public $title;
    /**
     * @var String
     */
    public $message;
    /**
     * @var string
     */
    public $author_email;
    /**
     * @var MinimalUserRepresentation|null
     */
    public $author = null;
    /**
     * @var string
     */
    public $html_url;
    /**
     * @var \Tuleap\Git\REST\v1\GitCommitStatusRepresentation
     */
    public $commit_status;
    /**
     * @var \Tuleap\Git\REST\v1\GitCommitVerificationRepresentation
     */
    public $verification;

    public function build($repository_path, Commit $commit, CommitMetadata $commit_metadata)
    {
        $this->id             = $commit->GetHash();
        $this->title          = $commit->GetTitle();
        $this->message        = implode("\n", $commit->GetComment());
        $this->author_name    = $commit->GetAuthorName();
        $this->author_email   = $commit->getAuthorEmail();
        $this->authored_date  = JsonCast::toDate($commit->GetAuthorEpoch());
        $this->committed_date = JsonCast::toDate($commit->GetCommitterEpoch());
        $this->verification   = new GitCommitVerificationRepresentation();
        $this->verification->build($commit->getPGPSignature());

        $this->html_url = $repository_path . '?' . http_build_query(
            [
                'a' => 'commit',
                'h' => $commit->GetHash()
            ]
        );

        $author = $commit_metadata->getAuthor();
        if ($author !== null) {
            $author_representation = MinimalUserRepresentation::build($author);
            $this->author = $author_representation;
        }

        $this->commit_status = null;
        $commit_status       = $commit_metadata->getCommitStatus();
        if ($commit_status->getStatusName() !== CommitStatusUnknown::NAME) {
            $this->commit_status = new GitCommitStatusRepresentation();
            $this->commit_status->build($commit_status);
        }
    }
}
