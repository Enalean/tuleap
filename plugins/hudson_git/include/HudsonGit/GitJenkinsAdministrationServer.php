<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\HudsonGit;

use Project;

/**
 * @psalm-immutable
 */
class GitJenkinsAdministrationServer
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $jenkins_server_url;

    /**
     * @var Project
     */
    private $project;

    public function __construct(int $id, string $jenkins_server_url, Project $project)
    {
        $this->id = $id;
        $this->jenkins_server_url = $jenkins_server_url;
        $this->project = $project;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getServerURL(): string
    {
        return $this->jenkins_server_url;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
