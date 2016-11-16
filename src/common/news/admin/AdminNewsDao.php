<?php
/**
* Copyright (c) Enalean, 2016. All rights reserved
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
namespace Tuealp\News\Admin;

use DataAccessObject;

class AdminNewsDao extends DataAccessObject
{
    public function getDeletedNews($old_date)
    {
        $old_date = $this->da->escapeInt($old_date);

        $sql = "SELECT * FROM news_bytes WHERE is_approved=2 AND date>$old_date";

        return $this->retrieve($sql);
    }

    public function getApprovedNews($old_date)
    {
        $old_date = $this->da->escapeInt($old_date);

        $sql = "SELECT * FROM news_bytes WHERE is_approved=1 AND date>$old_date";

        return $this->retrieve($sql);
    }

    public function getApprovalQueueNews()
    {
        $sql = "SELECT * FROM news_bytes WHERE is_approved=0 OR is_approved=3";

        return $this->retrieve($sql);
    }
}