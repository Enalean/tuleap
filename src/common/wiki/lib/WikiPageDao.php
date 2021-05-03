<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\PHPWiki;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class WikiPageDao extends DataAccessObject
{
    public function getAllUserPages(int $project_id, array $excluded_pages): array
    {
        $conditions = EasyStatement::open()
            ->in('wiki_page.pagename NOT IN (?*)', $excluded_pages)
            ->andWith('wiki_page.group_id = ?', $project_id);

        $sql = "SELECT pagename
                FROM wiki_page
                    JOIN wiki_nonempty ON (wiki_nonempty.id = wiki_page.id)
                WHERE $conditions";

        $res = $this->getDB()->safeQuery($sql, $conditions->values());
        if (! is_array($res)) {
            throw new \RuntimeException('Impossible to fetch the list of pages. Not an array');
        }
        return $res;
    }
}
