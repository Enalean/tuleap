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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\REST\v1;

use Tuleap\Git\GitPHP\Commit;
use Tuleap\REST\JsonCast;

class GitCommitRepresentation
{
    /**
     * @var string
     */
    public $commit_id;
    /**
     * @var string
     */
    public $author_name;
    /**
     * @var int
     */
    public $authored_date;
    /**
     * @var string
     */
    public $title;
    /**
     * @var String
     */
    public $message;

    public function build(Commit $commit)
    {
        $this->commit_id     = $commit->GetHash();
        $this->authored_date = JsonCast::toDate($commit->GetAuthorEpoch());
        $this->title         = $commit->GetTitle();
        $this->author_name   = $commit->GetAuthorName();
        $this->message       = implode("\n", $commit->GetComment());
    }
}
