<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\AccessControl;

use Tuleap\SVN\Repository\Repository;

class AccessFileHistory
{

    private $id;
    private $version_number;
    private $content;
    private $version_date;
    private $repository;

    public function __construct(Repository $repository, $id, $version_number, $content, $version_date)
    {
        $this->id             = $id;
        $this->version_number = $version_number;
        $this->content        = $content;
        $this->version_date   = $version_date;
        $this->repository     = $repository;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setVersionNumber($version_number)
    {
        $this->version_number = $version_number;
    }

    public function getVersionNumber()
    {
        return (int) $this->version_number;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setVersionDate($version_date)
    {
        $this->version_date = $version_date;
    }

    public function getVersionDate()
    {
        return $this->version_date;
    }

    public function getRepository()
    {
        return $this->repository;
    }
}
