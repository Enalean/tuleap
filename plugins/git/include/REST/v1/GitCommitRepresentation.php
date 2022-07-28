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

use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
class GitCommitRepresentation
{
    public string $id;
    public string $author_name;
    public string $authored_date;
    public string $committed_date;
    public string $title;
    public string $message;
    public string $author_email;
    public ?MinimalUserRepresentation $author = null;
    public string $html_url;
    public ?GitCommitStatusRepresentation $commit_status;
    public GitCommitVerificationRepresentation $verification;

    public function __construct(
        string $id,
        string $title,
        string $message,
        string $author_name,
        string $author_email,
        string $authored_date,
        string $committed_date,
        GitCommitVerificationRepresentation $verification,
        ?MinimalUserRepresentation $author,
        ?GitCommitStatusRepresentation $commit_status,
        string $repository_path,
    ) {
        $this->id             = $id;
        $this->title          = $title;
        $this->message        = $message;
        $this->author_name    = $author_name;
        $this->authored_date  = JsonCast::toDate($authored_date);
        $this->committed_date = JsonCast::toDate($committed_date);
        $this->verification   = $verification;
        $this->author         = $author;
        $this->commit_status  = $commit_status;
        $this->html_url       = $repository_path . '?' . http_build_query(
            [
                'a' => 'commit',
                'h' => $id,
            ]
        );
        $this->author_email   = $author_email;
    }
}
