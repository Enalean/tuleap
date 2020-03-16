<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

use Tuleap\DB\DataAccessObject;

class BackgroundColorDao extends DataAccessObject
{
    public function searchBackgroundColor($tracker_id)
    {
        $sql = "SELECT field_id FROM plugin_cardwall_background_color_card_field WHERE tracker_id = ?";

        return $this->getDB()->single($sql, [$tracker_id]);
    }

    public function save($tracker_id, $field_id)
    {
        $sql = "REPLACE INTO plugin_cardwall_background_color_card_field
                  VALUES (?, ?)";

        $this->getDB()->run($sql, $tracker_id, $field_id);
    }

    public function isFieldUsedAsBackgroundColor($field_id)
    {
        $sql = "SELECT COUNT(*) FROM plugin_cardwall_background_color_card_field WHERE field_id = ?";

        return $this->getDB()->single($sql, [$field_id]) > 0;
    }

    public function unsetBackgroundColorSemantic($tracker_id)
    {
        $sql = "DELETE FROM plugin_cardwall_background_color_card_field WHERE tracker_id = ?";

        $this->getDB()->run($sql, $tracker_id);
    }
}
