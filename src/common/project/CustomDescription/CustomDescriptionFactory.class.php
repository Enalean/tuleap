<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'CustomDescription.class.php';

/**
 * Factory to instanciate Project_CustomDescription_CustomDescription
 */
class Project_CustomDescription_CustomDescriptionFactory {

    /**
     * @return Project_CustomDescription_CustomDescription[]
     */
    public function getRequiredCustomDescriptions() {
        $required_custom_descriptions = array();
        $res = db_query('SELECT * FROM group_desc WHERE desc_required = 1 ORDER BY desc_rank');
        while ($row = db_fetch_array($res)) {
            $required_custom_descriptions[$row['group_desc_id']] = new Project_CustomDescription_CustomDescription(
                $row['group_desc_id'],
                $row['desc_name'],
                $row['desc_description'],
                $row['desc_required'],
                $row['desc_type'],
                $row['desc_rank']
            );
        }
        return $required_custom_descriptions;
    }

    /**
     * @return Project_CustomDescription_CustomDescription[]
     */
    public function getCustomDescriptions() {
        $custom_descriptions = array();
        $res = db_query('SELECT * FROM group_desc ORDER BY desc_rank');
        while ($row = db_fetch_array($res)) {
            $custom_descriptions[$row['group_desc_id']] = new Project_CustomDescription_CustomDescription(
                $row['group_desc_id'],
                $row['desc_name'],
                $row['desc_description'],
                $row['desc_required'],
                $row['desc_type'],
                $row['desc_rank']
            );
        }
        return $custom_descriptions;
    }
}
?>
