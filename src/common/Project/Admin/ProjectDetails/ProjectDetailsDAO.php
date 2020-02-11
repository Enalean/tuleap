<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\ProjectDetails;

use DataAccessObject;

class ProjectDetailsDAO extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

    public function searchGroupInfo($group_id)
    {
        $escaped_group_id = $this->da->escapeInt($group_id);
        $group_info       = $this->retrieveFirstRow("SELECT * FROM groups WHERE group_id = $escaped_group_id");

        if ($this->foundRows() < 1) {
            exit_no_group();
        }

        return $group_info;
    }

    public function createGroupDescription(
        $group_id,
        $group_desc_id,
        $current_form
    ) {
        $escaped_current_form  = $this->da->quoteSmart($current_form);
        $escaped_group_id      = $this->da->escapeInt($group_id);
        $escaped_group_desc_id = $this->da->escapeInt($group_desc_id);

        $sql = "INSERT INTO group_desc_value (group_id, group_desc_id, value)
                    VALUES ($escaped_group_id, $escaped_group_desc_id, $escaped_current_form)";

        return $this->updateAndGetLastId($sql);
    }

    public function updateGroupDescription(
        $group_id,
        $group_desc_id,
        $current_form
    ) {
        $escaped_current_form  = $this->da->quoteSmart($current_form);
        $escaped_group_id      = $this->da->escapeInt($group_id);
        $escaped_group_desc_id = $this->da->escapeInt($group_desc_id);

        $sql = "UPDATE group_desc_value
                SET     value         = $escaped_current_form
                WHERE   group_id      = $escaped_group_id
                    AND group_desc_id = $escaped_group_desc_id";

        return $this->update($sql);
    }

    public function updateGroupNameAndDescription($form_group_name, $form_shortdesc, $group_id)
    {
        $escaped_form_group_name = $this->da->quoteSmart($form_group_name);
        $escaped_form_shortdesc  = $this->da->quoteSmart($form_shortdesc);
        $escaped_group_id        = $this->da->escapeInt($group_id);

        $sql = "UPDATE groups
                SET   group_name        = $escaped_form_group_name,
                      short_description = $escaped_form_shortdesc
                WHERE group_id          = $escaped_group_id";

        return $this->update($sql);
    }

    public function deleteDescriptionForGroup($group_id, $group_desc_id)
    {
        $escaped_group_id      = $this->da->escapeInt($group_id);
        $escaped_group_desc_id = $this->da->escapeInt($group_desc_id);

        $sql = "DELETE FROM group_desc_value
                WHERE   group_id      = $escaped_group_id
                    AND group_desc_id = $escaped_group_desc_id";

        return $this->update($sql);
    }
}
