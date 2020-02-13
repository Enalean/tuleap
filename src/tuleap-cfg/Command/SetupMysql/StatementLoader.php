<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace TuleapCfg\Command\SetupMysql;

use ParagonIE\EasyDB\EasyDB;

class StatementLoader
{
    /**
     * @var EasyDB
     */
    private $db;

    public function __construct(EasyDB $db)
    {
        $this->db = $db;
    }

    public function loadFromFile(string $filepath)
    {
        if (! is_file($filepath)) {
            throw new \RuntimeException(sprintf('%s does not exist', $filepath));
        }

        $sql = file_get_contents($filepath);
        $transformed = str_replace("\\\n", '', $sql);

        $this->db->getPdo()->exec($transformed);
    }
}
