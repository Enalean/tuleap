<?php
/**
 * Copyright (c) Enalean SAS, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\ReferenceAliasGit;

use GitRepository;
use GitPlugin;

class Reference extends \Reference
{
    public function __construct(GitRepository $repository, $keyword, $sha1)
    {
        $base_id       = 0;
        $visibility    = 'P';
        $is_used       = 1;
        $project_id    = (int) $repository->getProjectId();
        $repository_id = (int) $repository->getId();
        $sha1          = urlencode($sha1);

        parent::__construct(
            $base_id,
            $keyword,
            '',
            "/plugins/git/index.php/$project_id/view/$repository_id/?a=commit&h=$sha1",
            $visibility,
            GitPlugin::SERVICE_SHORTNAME,
            GitPlugin::SYSTEM_NATURE_NAME,
            $is_used,
            $project_id
        );
    }
}
