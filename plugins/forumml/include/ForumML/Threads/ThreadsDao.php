<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML\Threads;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class ThreadsDao extends DataAccessObject
{
    public const HEADER_ID_DATE    = 2;
    public const HEADER_ID_FROM    = 3;
    public const HEADER_ID_SUBJECT = 4;

    public function searchActiveList(int $id): ?array
    {
        $sql = "SELECT *
                FROM mail_group_list
                WHERE group_list_id = ?
                  AND is_public IN (0, 1)";

        return $this->getDB()->row($sql, $id);
    }

    public function searchThreadsOfLists(int $id, int $limit, int $offset, string $search): array
    {
        if ($search) {
            return $this->searchThreadsOfListsForAGivenSubject($id, $limit, $offset, $search);
        }

        $sql = 'SELECT SQL_CALC_FOUND_ROWS m.id_message,
                           m.last_thread_update,
                           mh_d.value as date,
                           mh_f.value as sender,
                           mh_s.value as subject
                FROM plugin_forumml_message AS m
                         LEFT JOIN plugin_forumml_messageheader AS mh_d
                                   ON (mh_d.id_message = m.id_message AND mh_d.id_header = ?)
                         LEFT JOIN plugin_forumml_messageheader AS mh_f
                                   ON (mh_f.id_message = m.id_message AND mh_f.id_header = ?)
                         LEFT JOIN plugin_forumml_messageheader AS mh_s
                                   ON (mh_s.id_message = m.id_message AND mh_s.id_header = ?)
                WHERE (m.id_parent = 0 OR
                       m.id_parent NOT IN (
                           SELECT id_message
                           FROM plugin_forumml_message
                           WHERE id_list = ?
                       )
                    )
                  AND id_list = ?
                ORDER BY last_thread_update DESC
                LIMIT ?, ?';

        return $this->getDB()->run(
            $sql,
            self::HEADER_ID_DATE,
            self::HEADER_ID_FROM,
            self::HEADER_ID_SUBJECT,
            $id,
            $id,
            $offset,
            $limit
        );
    }

    private function searchThreadsOfListsForAGivenSubject(int $id, int $limit, int $offset, string $search): array
    {
        $subject = '%' . $this->getDB()->escapeLikeValue($search) . '%';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS m.id_message,
                           m.last_thread_update,
                           mh_d.value as date,
                           mh_f.value as sender,
                           mh_s.value as subject
                FROM plugin_forumml_message AS m
                         LEFT JOIN plugin_forumml_messageheader AS mh_d
                                   ON (mh_d.id_message = m.id_message AND mh_d.id_header = ?)
                         LEFT JOIN plugin_forumml_messageheader AS mh_f
                                   ON (mh_f.id_message = m.id_message AND mh_f.id_header = ?)
                         INNER JOIN plugin_forumml_messageheader AS mh_s
                                   ON (mh_s.id_message = m.id_message AND mh_s.id_header = ?)
                WHERE m.id_parent = 0
                  AND id_list = ?
                  AND mh_s.value LIKE ?
                ORDER BY last_thread_update DESC
                LIMIT ?, ?';

        return $this->getDB()->run(
            $sql,
            self::HEADER_ID_DATE,
            self::HEADER_ID_FROM,
            self::HEADER_ID_SUBJECT,
            $id,
            $subject,
            $offset,
            $limit
        );
    }

    public function searchNbChildren(array $parent_ids, int $id): int
    {
        if (empty($parent_ids)) {
            return 0;
        }

        $in_condition = EasyStatement::open()->in('?*', $parent_ids);

        $sql = "SELECT id_message
                FROM plugin_forumml_message
                WHERE id_list = ? AND id_parent IN ($in_condition)";

        $children = array_values(
            $this->getDB()->col($sql, 0, $id, ...$in_condition->values())
        );

        return count($children) + $this->searchNbChildren($children, $id);
    }
}
