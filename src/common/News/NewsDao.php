<?php
/**
  * Copyright (c) Enalean, 2014. All rights reserved
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
}
