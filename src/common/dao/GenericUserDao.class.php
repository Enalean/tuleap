<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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

require_once('include/DataAccessObject.class.php');

class GenericUserDao extends DataAccessObject
{
    public function __construct($da = null)
    {
        parent::__construct($da);
    }

    public function save($group_id, $user_id)
    {
        $group_id = $this->da->escapeInt($group_id);
        $user_id  = $this->da->escapeInt($user_id);

        $sql = "INSERT INTO generic_user (group_id, user_id) VALUES ($group_id, $user_id)";

        return $this->update($sql);
    }

    public function fetch($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "SELECT * FROM generic_user WHERE group_id = $group_id";

        return $this->retrieve($sql);
    }
}
