<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Git\Gitolite;

use DataAccessObject;

class GitoliteFileLogsDao extends DataAccessObject
{
    public function getLastReadLine($file_name)
    {
        $file_name = $this->da->quoteSmart($file_name);

        $sql = "SELECT end_line
                FROM plugin_git_file_logs_parse
                WHERE file_name = $file_name";

        return $this->retrieveFirstRow($sql);
    }

    public function storeLastLine($file_name, $end_line)
    {
        $file_name = $this->da->quoteSmart($file_name);
        $end_line  = $this->da->escapeInt($end_line);

        $sql = "INSERT INTO plugin_git_file_logs_parse (file_name, end_line)
                VALUES ($file_name, $end_line)
                 ON DUPLICATE KEY UPDATE end_line = $end_line";

        return $this->update($sql);
    }
}
