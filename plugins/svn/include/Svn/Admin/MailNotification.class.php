<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All rights reserved
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

namespace Tuleap\Svn\Admin;

use Tuleap\Svn\Repository\Repository;

class MailNotification
{
    private $notified_mails;
    private $path;
    private $repository;
    private $id;

    public function __construct($id, Repository $repository, $notified_mails, $path)
    {
        $this->id             = $id;
        $this->repository     = $repository;
        $this->notified_mails = $notified_mails;
        $this->path           = $path;
    }

    public function getNotifiedMails()
    {
        return $this->notified_mails;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getId()
    {
        return $this->id;
    }
}
