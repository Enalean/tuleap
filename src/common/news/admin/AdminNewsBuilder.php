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

class AdminNewsBuilder
{
    /**
     * @var AdminNewsDao
     */
    private $admin_news_dao;

    public function __construct(AdminNewsDao $admin_news_dao)
    {
        $this->admin_news_dao = $admin_news_dao;
    }

    public function getRejectedNews($old_date)
    {
        return $this->build($this->admin_news_dao->getRejectedNews($old_date));
    }

    public function getApprovedNews($old_date)
    {
        return $this->build($this->admin_news_dao->getApprovedNews($old_date));
    }

    public function getApprovalQueueNews()
    {
        $result          = $this->admin_news_dao->getApprovalQueueNews();
        $filtered_result = array();

        foreach ($result as $row) {
            //if the news is private, not display it in the list of news to be approved
            $forum_id = $row['forum_id'];
            $res      = news_read_permissions($forum_id);
            // check on db_result($res,0,'ugroup_id') == $UGROUP_ANONYMOUS only to be consistent
            // with ST DB state
            if ((db_numrows($res) < 1) || (db_result($res, 0, 'ugroup_id') == $GLOBALS['UGROUP_ANONYMOUS'])) {
                $filtered_result[] = $row;
            }
        }

        return $this->build($filtered_result);
    }

    public function build($result)
    {
        $news_list = array();

        foreach ($result as $row) {
            $news_list[] = new AdminNewsPresenter(
                $row['id'],
                $row['summary'],
                $row['details']
            );
        }

        return $news_list;
    }
}