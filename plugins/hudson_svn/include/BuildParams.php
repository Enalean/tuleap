<?php
/**
 * Copyright (c) STMicroelectronics, 2016. All Rights Reserved.
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

namespace Tuleap\HudsonSvn;

use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVNCore\Repository;

class BuildParams
{
    public const BUILD_PARAMETER_PROJECT    = "Project";
    public const BUILD_PARAMETER_USER       = "User";
    public const BUILD_PARAMETER_REPOSITORY = "Repository";
    public const BUILD_PARAMETER_PATH       = "Path";

    public function getAdditionalSvnParameters(Repository $repository, CommitInfo $commit_info)
    {
        return [
            self::BUILD_PARAMETER_PROJECT    => $repository->getProject()->getUnixName(),
            self::BUILD_PARAMETER_USER       => $commit_info->getUser(),
            self::BUILD_PARAMETER_REPOSITORY => $repository->getName(),
            self::BUILD_PARAMETER_PATH       => implode("\n", $commit_info->getChangedDirectories()),
        ];
    }
}
