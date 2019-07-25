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

require_once __DIR__ . '/../../../www/news/news_utils.php';

class NewsRetriever
{
    public const NEWS_STATUS_WAITING_PUBLICATION   = '0';
    public const NEWS_STATUS_PUBLISHED             = '1';
    public const NEWS_STATUS_REJECTED              = '2';
    public const NEWS_STATUS_REQUESTED_PUBLICATION = '3';

    /**
     * @var AdminNewsDao
     */
    private $dao;

    public function __construct(AdminNewsDao $dao)
    {
        $this->dao = $dao;
    }

    public function countPendingNews()
    {
        return count($this->getWaitingPublicationNews());
    }

    public function getRejectedNews($old_date)
    {
        return $this->dao->getRejectedNews($old_date);
    }

    public function getPublishedNews($old_date)
    {
        return $this->dao->getPublishedNews($old_date);
    }

    public function getWaitingPublicationNews()
    {
        $result          = $this->dao->getWaitingPublicationNews();
        $filtered_result = array();

        foreach ($result as $row) {
            $forum_id = $row['forum_id'];
            $res      = \news_read_permissions($forum_id);
            if ((db_numrows($res) < 1) || (db_result($res, 0, 'ugroup_id') == $GLOBALS['UGROUP_ANONYMOUS'])) {
                $filtered_result[] = $row;
            }
        }

        return $filtered_result;
    }
}
