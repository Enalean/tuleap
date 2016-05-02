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

namespace Tuleap\PullRequest\InlineComment;

use DataAccessObject;

class Dao extends DataAccessObject
{
    public function searchUpToDateByFilePath($pull_request_id, $file_path)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $file_path       = $this->da->quoteSmart($file_path);

        $sql = "SELECT * FROM plugin_pullrequest_inline_comments
                WHERE pull_request_id=$pull_request_id
                AND file_path=$file_path AND is_outdated=false";

        return $this->retrieve($sql);
    }

}
