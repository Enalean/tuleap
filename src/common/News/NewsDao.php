<?php
/**
  * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

namespace Tuleap\News;

use ParagonIE\EasyDB\EasyStatement;

class NewsDao extends \Tuleap\DB\DataAccessObject
{
    /**
     * Note:
     * news_bytes.is_approved has values between 0 and 5.
     * Values 0-3 are ok for displaying in the widget (not sure what else each one means) according
     * to the function "news_show_latest()"
     * Value 4 corresponds to deleted
     * Value 5 corresponds to suspended
     */
    public function fetchAll($project_id)
    {
        return $this->getDB()->run(
            'SELECT * FROM news_bytes
                WHERE group_id= ?
                AND is_approved != 4
                ORDER BY id DESC',
            $project_id
        );
    }

    public function updatePromotedItems(array $promoted_ids, $project_id)
    {
        $promoted_ids_condition = EasyStatement::open()->in('?*', $promoted_ids);

        $sql = "UPDATE news_bytes
                SET is_approved = CASE WHEN id IN ($promoted_ids_condition) THEN 0 ELSE 5 END
                WHERE is_approved != 4
                AND group_id = ?";

        return $this->getDB()->safeQuery($sql, array_merge($promoted_ids_condition->values(), [$project_id]));
    }

    public function getNewsForSitePublicRSSFeed()
    {
        return $this->getSiteNewsLimit(10);
    }

    private function getSiteNewsLimit(int $limit)
    {
        $where_statement = EasyStatement::open()->in('?*', [\Project::ACCESS_PUBLIC, \Project::ACCESS_PUBLIC_UNRESTRICTED]);

        $sql = "SELECT
                    `groups`.group_id,
                    `groups`.group_name,
                    `groups`.unix_group_name,
                    news_bytes.submitted_by,
                    news_bytes.forum_id,
                    news_bytes.summary,
                    news_bytes.date,
                    news_bytes.details
            FROM news_bytes
                INNER JOIN `groups` ON (news_bytes.group_id = `groups`.group_id)
            WHERE news_bytes.is_approved = 1
                AND `groups`.status = 'A'
                AND `groups`.access IN ($where_statement)
            ORDER BY news_bytes.date DESC LIMIT ? ";

        return $this->getDB()->safeQuery($sql, array_merge($where_statement->values(), [$limit]));
    }
}
