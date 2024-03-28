<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Config;

use Tuleap\DB\DataAccessObject;

class ConfigDao extends DataAccessObject
{
    /**
     * @return list<array{name: string, value: string}>
     */
    public function searchAll(): array
    {
        $sql = 'SELECT * FROM forgeconfig';

        return $this->getDB()->run($sql);
    }

    public function save(string $name, string $value): void
    {
        $sql = 'REPLACE INTO forgeconfig (name, value) VALUES (?, ?)';

        $this->getDB()->safeQuery($sql, [$name, $value]);
    }

    public function saveBool(string $name, bool $value): void
    {
        $this->save($name, $value ? '1' : '0');
    }

    public function saveInt(string $name, int $value): void
    {
        $this->save($name, (string) $value);
    }

    public function delete(string $name): void
    {
        $this->getDB()->run('DELETE FROM forgeconfig WHERE name = ?', $name);
    }
}
