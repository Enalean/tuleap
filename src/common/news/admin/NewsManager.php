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

use CSRFSynchronizerToken;
use DateHelper;
use ProjectManager;
use UserManager;

class NewsManager
{
    const NEWS_STATUS_WAITING_PUBLICATION   = '0';
    const NEWS_STATUS_PUBLISHED             = '1';
    const NEWS_STATUS_REJECTED              = '2';
    const NEWS_STATUS_REQUESTED_PUBLICATION = '3';

    protected $_dao;

    private static $_instance;

    private function __construct(AdminNewsDao $dao = null)
    {
        $this->_dao = $dao;
    }

    public static function instance()
    {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    public static function setInstance($instance)
    {
        self::$_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_instance = null;
    }

    public function _getDao() {
        if (!isset($this->_dao)) {
            $this->_dao = new AdminNewsDao();
        }
        return $this->_dao;
    }

    public function countPendingNews()
    {
        return count($this->_getDao()->getWaitingPublicationNews());
    }

    public function getRejectedNews($old_date)
    {
        return $this->_getDao()->getRejectedNews($old_date);
    }

    public function getPublishedNews($old_date)
    {
        return $this->_getDao()->getPublishedNews($old_date);
    }

    public function getWaitingPublicationNews()
    {
        return $this->_getDao()->getWaitingPublicationNews();
    }

    public function getNewsById($id)
    {
        return $this->_getDao()->getNewsById($id);
    }

    public function updateNews($id, $title, $content, $status, $date)
    {
        return $this->_getDao()->updateNews($id, $title, $content, $status, $date);
    }
}