<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\HudsonSvn\Job;

class Job
{

    private $token;
    private $url;
    private $path;
    private $repository_id;
    private $id;

    public function __construct($id, $repository_id, $path, $url, $token)
    {
        $this->id            = $id;
        $this->repository_id = $repository_id;
        $this->path          = $path;
        $this->url           = $url;
        $this->token         = $token;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRepositoryId()
    {
        return $this->repository_id;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getToken()
    {
        return $this->token;
    }
}
