<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\DB;

use ParagonIE\EasyDB\EasyDB;

class DBConnection implements ReconnectAfterALongRunningProcess
{
    /**
     * @var DBCreator
     */
    private $db_creator;
    /**
     * @var EasyDB|null
     */
    private $db;

    public function __construct(DBCreator $db_creator)
    {
        $this->db_creator = $db_creator;
    }

    public function getDB(): EasyDB
    {
        if ($this->db === null) {
            $this->db = $this->db_creator->createDB();
        }
        return $this->db;
    }

    public function reconnectAfterALongRunningProcess(): void
    {
        if ($this->db === null) {
            return;
        }
        try {
            @$this->db->run('SELECT 1');
        } catch (\PDOException $ex) {
            if (preg_match('/HY000.*2006 MySQL server has gone away$/', $ex->getMessage()) === 1) {
                $this->db = null;
                return;
            }
            throw $ex;
        }
    }
}
