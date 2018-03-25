<?php
/**
 * Copyright (c) Enalean, 2016-2018. All rights reserved
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

use Tuleap\DB\DataAccessObject;

class GitoliteFileLogsDao extends DataAccessObject
{
    public function getLastReadLine($file_name)
    {
        $sql = 'SELECT end_line
                FROM plugin_git_file_logs_parse
                WHERE file_name = ?';

        return $this->getDB()->row($sql, $file_name);
    }

    public function storeLastLine($file_name, $end_line)
    {
        $sql = 'INSERT INTO plugin_git_file_logs_parse (file_name, end_line)
                VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE end_line = ?';

        $this->getDB()->run($sql, $file_name, $end_line, $end_line);
    }
}
