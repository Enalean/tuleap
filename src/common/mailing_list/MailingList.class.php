<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *
 * MailingList object
 *
 */
class MailingList
{

    protected $id;
    protected $group_id;
    protected $list_name;
    protected $is_public;
    protected $password;
    protected $description;
    protected $list_admin; // user_id

    protected $_mailinglistdao;


    public function __construct($row = null)
    {
        $this->id            = isset($row['group_list_id'])  ? $row['group_list_id'] : 0;
        $this->group_id      = isset($row['group_id'])       ? $row['group_id']      : 0;
        $this->list_name     = isset($row['list_name'])      ? $row['list_name']     : null;
        $this->is_public     = isset($row['is_public'])      ? $row['is_public']     : 0;
        $this->password      = isset($row['password'])       ? $row['password']      : null;
        $this->description   = isset($row['description'])    ? $row['description']   : null;
        $this->list_admin    = isset($row['list_admin'])     ? $row['list_admin']    : 0;
    }

    protected function _getMailingListDao()
    {
        if (! $this->_mailinglistdao) {
            $this->_mailinglistdao = new MailingListDao(CodendiDataAccess::instance());
        }
        return $this->_ugroupdao;
    }


    public function getId()
    {
        return $this->id;
    }
    public function getListName()
    {
        return $this->list_name;
    }
    public function getListPassword()
    {
        return $this->password;
    }
    public function getListAdmin()
    {
        return $this->list_admin;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function getIsPublic()
    {
        return $this->is_public;
    }
}
