<?php
/**
 * Copyright (c) Enalean SAS, 2016 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ReferenceAliasGit;

use Tuleap\DB\DataAccessObject;

class Dao extends DataAccessObject
{
    public function insertRef(string $source, int $repository_id, string $sha1): bool
    {
        $sql = "REPLACE INTO plugin_referencealias_git(source, repository_id, sha1)
                VALUES (?, ?, UNHEX(?))";

        try {
            $this->getDB()->run($sql, $source, $repository_id, $sha1);
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }

    /**
     * @psalm-return array{repository_id:int, sha1:string}
     */
    public function getRef(string $source): ?array
    {
        $sql = "SELECT repository_id, LOWER(HEX(sha1)) as sha1
                FROM plugin_referencealias_git
                WHERE source = ?";

        return $this->getDB()->row($sql, $source);
    }
}
