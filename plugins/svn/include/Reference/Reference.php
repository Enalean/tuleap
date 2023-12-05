<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\SVN\Reference;

use Tuleap\SVNCore\Repository;
use Project;
use SvnPlugin;

class Reference extends \Reference
{
    public function __construct(Project $project, Repository $repository, $keyword, $revision_id)
    {
        $base_id         = 0;
        $visibility      = 'P';
        $is_used         = 1;
        $project_id      = $project->getId();
        $repository_path = urlencode($repository->getFullName());

        parent::__construct(
            $base_id,
            $keyword,
            '',
            SVN_BASE_URL . "?roottype=svn&view=rev&root=$repository_path&revision=$revision_id",
            $visibility,
            SvnPlugin::SERVICE_SHORTNAME,
            SvnPlugin::SYSTEM_NATURE_NAME,
            $is_used,
            $project_id
        );
    }
}
