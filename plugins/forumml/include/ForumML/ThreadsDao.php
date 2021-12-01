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

namespace Tuleap\ForumML;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class ThreadsDao extends DataAccessObject
{
    public const HEADER_ID_DATE         = 2;
    public const HEADER_ID_FROM         = 3;
    public const HEADER_ID_SUBJECT      = 4;
    public const HEADER_ID_CONTENT_TYPE = 12;
    public const HEADER_ID_CC           = 34;

    /**
     * @return null|array{group_id: int, list_name: string, is_public: int, description: string, group_list_id: int}
     */
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

    public function searchMessageInfo(int $list_id, int $message_id): array
    {
        $sql = 'SELECT
                    m.*,
                    mh_d.value AS date,
                    mh_f.value AS sender,
                    mh_s.value AS subject,
                    mh_ct.value AS content_type,
                    mh_cc.value AS cc,
                    a.id_attachment,
                    a.file_name,
                    a.file_path
                FROM plugin_forumml_message m
                    LEFT JOIN plugin_forumml_messageheader mh_d
                        ON (mh_d.id_message = m.id_message AND mh_d.id_header = ?)
                    LEFT JOIN plugin_forumml_messageheader mh_f
                        ON (mh_f.id_message = m.id_message AND mh_f.id_header = ?)
                    LEFT JOIN plugin_forumml_messageheader mh_s
                        ON (mh_s.id_message = m.id_message AND mh_s.id_header = ?)
                    LEFT JOIN plugin_forumml_messageheader mh_ct
                        ON (mh_ct.id_message = m.id_message AND mh_ct.id_header = ?)
                    LEFT JOIN plugin_forumml_messageheader mh_cc
                        ON (mh_cc.id_message = m.id_message AND mh_cc.id_header = ?)
                    LEFT JOIN plugin_forumml_attachment a
                        ON (a.id_message = m.id_message AND a.content_id = "")
                WHERE m.id_list = ?
                  AND m.id_message = ?';

        return $this->getDB()->run(
            $sql,
            self::HEADER_ID_DATE,
            self::HEADER_ID_FROM,
            self::HEADER_ID_SUBJECT,
            self::HEADER_ID_CONTENT_TYPE,
            self::HEADER_ID_CC,
            $list_id,
            $message_id
        );
    }

    /**
     * @param int[] $parent_ids
     */
    public function searchChildrenMessageInfo(int $list_id, array $parent_ids): array
    {
        $in_condition = EasyStatement::open()->in('?*', $parent_ids);

        $sql = 'SELECT
                    m.*,
                    mh_d.value AS date,
                    mh_f.value AS sender,
                    mh_s.value AS subject,
                    mh_ct.value AS content_type,
                    mh_cc.value AS cc,
                    a.id_attachment,
                    a.file_name,
                    a.file_path
                FROM plugin_forumml_message m
                    LEFT JOIN plugin_forumml_messageheader mh_d
                        ON (mh_d.id_message = m.id_message AND mh_d.id_header = ?)
                    LEFT JOIN plugin_forumml_messageheader mh_f
                        ON (mh_f.id_message = m.id_message AND mh_f.id_header = ?)
                    LEFT JOIN plugin_forumml_messageheader mh_s
                        ON (mh_s.id_message = m.id_message AND mh_s.id_header = ?)
                    LEFT JOIN plugin_forumml_messageheader mh_ct
                        ON (mh_ct.id_message = m.id_message AND mh_ct.id_header = ?)
                    LEFT JOIN plugin_forumml_messageheader mh_cc
                        ON (mh_cc.id_message = m.id_message AND mh_cc.id_header = ?)
                    LEFT JOIN plugin_forumml_attachment a
                        ON (a.id_message = m.id_message AND a.content_id = "")
                WHERE m.id_list = ?
                  AND m.id_parent IN (' . $in_condition . ')';

        return $this->getDB()->run(
            $sql,
            self::HEADER_ID_DATE,
            self::HEADER_ID_FROM,
            self::HEADER_ID_SUBJECT,
            self::HEADER_ID_CONTENT_TYPE,
            self::HEADER_ID_CC,
            $list_id,
            ...$in_condition->values()
        );
    }

    public function storeCachedHtml(int $message_id, string $cached_html): void
    {
        $this->getDB()->update(
            'plugin_forumml_message',
            [
                'cached_html' => $cached_html,
            ],
            [
                'id_message' => $message_id,
            ]
        );
    }
}
