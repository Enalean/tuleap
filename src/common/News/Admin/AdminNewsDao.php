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
namespace Tuleap\News\Admin;

use DataAccessObject;
use Tuleap\News\NewsItem;

class AdminNewsDao extends DataAccessObject
{
    public function getRejectedNews($old_date)
    {
        $old_date = $this->da->escapeInt($old_date);

        $sql = "SELECT * FROM news_bytes WHERE is_approved=2 AND date>$old_date";

        return $this->retrieve($sql);
    }

    public function getPublishedNews($old_date)
    {
        $old_date = $this->da->escapeInt($old_date);

        $sql = "SELECT * FROM news_bytes WHERE is_approved=1 AND date>$old_date";

        return $this->retrieve($sql);
    }

    public function getWaitingPublicationNews()
    {
        $sql = "SELECT * FROM news_bytes WHERE is_approved=0 OR is_approved=3";

        return $this->retrieve($sql);
    }

    public function getNewsById($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT groups.unix_group_name,news_bytes.*
                FROM news_bytes,groups WHERE id=$id
                AND news_bytes.group_id=groups.group_id";

        return $this->retrieveFirstRow($sql);
    }

    public function updateNews($id, $title, $content, $status, $date)
    {
        $id      = $this->da->escapeInt($id);
        $title   = $this->da->quoteSmart($title);
        $content = $this->da->quoteSmart($content);
        $status  = $this->da->escapeInt($status);
        $date    = $this->da->escapeInt($date);

        $sql = "UPDATE news_bytes SET is_approved=$status, date=$date,
                summary=$title, details=$content WHERE id=$id";

        return $this->update($sql);
    }

    public function searchAllPublishedNewsFromProject($project_id)
    {
        $news_deleted_value = $this->da->escapeInt(NewsItem::NEWS_DELETED);
        $group_id           = $this->da->escapeInt($project_id);

        $sql = "SELECT *
             FROM news_bytes
             WHERE group_id = $group_id
               AND is_approved != $news_deleted_value";

        return $this->retrieve($sql);
    }
}
