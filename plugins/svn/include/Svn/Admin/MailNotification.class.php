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
namespace Tuleap\Svn\Admin;

use Project;
use Tuleap\Svn\Repository\Repository;

class MailNotification {

    private $mailing_list;
    private $path;
    private $repository;

    public function __construct(Repository $repository, $mailing_list, $path) {
        $this->repository   = $repository;
        $this->mailing_list = $mailing_list;
        $this->path         = $path;
    }

    public function getMailingList() {
        return $this->mailing_list;
    }

    public function getPath() {
        return $this->path;
    }

    public function getRepository(){
        return $this->repository;
    }
}