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
 */

declare(strict_types=1);

namespace Tuleap\Request;

use ProjectManager;

class ProjectRetriever
{
    /** @var ProjectManager */
    private $project_manager;

    public function __construct(ProjectManager $project_manager)
    {
        $this->project_manager = $project_manager;
    }

    public static function buildSelf(): self
    {
        return new ProjectRetriever(ProjectManager::instance());
    }

    /**
     * @throws NotFoundException
     */
    public function getProjectFromId(string $project_id): \Project
    {
        $project = $this->project_manager->getProject($project_id);
        if (! $project || $project->isError()) {
            throw new NotFoundException(gettext('Project does not exist'));
        }
        return $project;
    }
}
