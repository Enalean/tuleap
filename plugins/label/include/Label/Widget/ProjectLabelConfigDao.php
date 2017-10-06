<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label\Widget;

class ProjectLabelConfigDao extends \DataAccessObject
{
    public function searchByContentId($content_id)
    {
        $content_id = $this->da->escapeInt($content_id);

        $sql = "SELECT *
                  FROM plugin_label_widget_config
                  INNER JOIN project_label
                    ON plugin_label_widget_config.label_id = project_label.id
                  WHERE content_id = $content_id
                  ORDER BY is_outline, name";

        return $this->retrieve($sql);
    }
}
