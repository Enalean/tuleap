<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\DAO;

use Tuleap\DB\DataAccessObject;

/**
 *  Data Access Object for DB Tables
 */
class DBTablesDao extends DataAccessObject
{
    public function updateFromFile(string $filename): void
    {
        $file_content = @file($filename);
        if ($file_content === false) {
            throw new \RuntimeException("$filename cannot be read");
        }
        $query = '';
        foreach ($file_content as $sql_line) {
            if (trim($sql_line) !== '' && strpos($sql_line, '--') === false) {
                $query .= $sql_line;
                if (preg_match("/;\s*(\r\n|\n|$)/", $sql_line)) {
                    $this->getDB()->run(str_replace("\\\n", '', trim($query)));
                    $query = '';
                }
            }
        }
    }
}
