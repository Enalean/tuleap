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

final class EasyDBWrapper implements DBWrapperInterface
{

    /**
     * @var EasyDB
     */
    private $db;

    public function __construct(EasyDB $db)
    {
        $this->db = $db;
    }

    public function run(string $statement, ...$params)
    {
        return $this->db->run($statement, ...$params);
    }

    public function escapeIdentifier(string $identifier, bool $quote = true): string
    {
        return $this->db->escapeIdentifier($identifier, $quote);
    }

    public function row(string $statement)
    {
        return $this->db->row($statement);
    }

    public function single(string $statement)
    {
        return $this->db->single($statement);
    }

    public function rawExec(string $statement): void
    {
        $this->db->getPdo()->exec($statement);
    }
}
