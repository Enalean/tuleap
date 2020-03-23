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
 *
 */

class Project_CustomDescription_CustomDescriptionValueDao extends DataAccessObject
{

    public function setDescriptionFieldValue($group_id, $field_id_to_update, $value)
    {
        $id       = $this->getDescriptionFieldValueId($group_id, $field_id_to_update);

        if ($id) {
            $this->updateDescriptionFieldValue($id, $value);
        } else {
            $this->addDescriptionFieldValue($group_id, $field_id_to_update, $value);
        }
    }

    private function updateDescriptionFieldValue($id, $value)
    {
        $value                = $this->da->quoteSmart($value);

        $sql = "UPDATE group_desc_value
                SET value = $value
                WHERE desc_value_id = $id";

        return $this->update($sql);
    }

    private function addDescriptionFieldValue($group_id, $description_field_id, $value)
    {
        $group_id             = $this->da->escapeInt($group_id);
        $description_field_id = $this->da->escapeInt($description_field_id);
        $value                = $this->da->quoteSmart($value);

        $sql = "INSERT INTO group_desc_value (group_id, group_desc_id, value)
                VALUES ($group_id, $description_field_id, $value)";

        return $this->update($sql);
    }

    public function getDescriptionFieldValueId($group_id, $description_field_id)
    {
        $group_id             = $this->da->escapeInt($group_id);
        $description_field_id = $this->da->escapeInt($description_field_id);

        $sql = "SELECT desc_value_id
                FROM group_desc_value
                WHERE group_id = $group_id
                  AND group_desc_id = $description_field_id";

        $result = $this->retrieve($sql);

        if ($result->rowCount() == 0) {
            return null;
        }

        $row = $result->getRow();
        return $row['desc_value_id'];
    }

    public function getDescriptionFieldsValue($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "SELECT *
                FROM group_desc_value
                WHERE group_id = $group_id";

        return $this->retrieve($sql);
    }

    public function getAllDescriptionValues($group_desc_id)
    {
        $group_desc_id = $this->da->escapeInt($group_desc_id);

        $sql = "SELECT group_id, value AS result
                FROM group_desc_value
                WHERE group_desc_id = $group_desc_id
                GROUP BY group_id";
        return $this->retrieve($sql);
    }
}
