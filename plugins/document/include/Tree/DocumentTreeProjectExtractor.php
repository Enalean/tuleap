<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

use Project;
use Tuleap\Request\NotFoundException;

class DocumentTreeProjectExtractor
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    public function __construct(\ProjectManager $project_manager)
    {
        $this->project_manager = $project_manager;
    }

    /**
     * @param array       $variables
     *
     * @throws NotFoundException
     */
    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByUnixName($variables['project_name']);
        if (! $project) {
            throw new NotFoundException(dgettext('tuleap-document', "Project not found"));
        }

        if (! $project->usesService(\docmanPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                sprintf(
                    dgettext("tuleap-document", "Documents service is not activated in project %s"),
                    $project->getPublicName()
                )
            );
        }

        return $project;
    }
}
