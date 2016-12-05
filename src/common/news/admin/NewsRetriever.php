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

class NewsRetriever
{
    const NEWS_STATUS_WAITING_PUBLICATION   = '0';
    const NEWS_STATUS_PUBLISHED             = '1';
    const NEWS_STATUS_REJECTED              = '2';
    const NEWS_STATUS_REQUESTED_PUBLICATION = '3';

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
        return count($this->dao->getWaitingPublicationNews());
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
        return $this->dao->getWaitingPublicationNews();
    }
}
