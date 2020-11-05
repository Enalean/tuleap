<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN;

use ForgeConfig;
use Project;

/**
 * @psalm-immutable
 */
final class CoreApacheConfRepository implements ApacheConfRepository
{
    /**
     * @var Project
     * @psalm-readonly
     */
    private $project;

    /**
     * @var string
     * @psalm-readonly
     */
    private $filesystem_path;

    /**
     * @var string
     * @psalm-readonly
     */
    private $url_path;

    public function __construct(Project $project)
    {
        $this->project = $project;
        $this->filesystem_path = rtrim(ForgeConfig::get('svn_prefix'), '/') . '/' . $project->getUnixNameMixedCase();
        $this->url_path = '/svnroot/' . $project->getUnixNameMixedCase();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getFilesystemPath(): string
    {
        return $this->filesystem_path;
    }

    public function getURLPath(): string
    {
        return $this->url_path;
    }
}
