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

namespace Tuleap\Project\ProjectBackground;

use Project;

class ProjectBackgroundRetriever
{
    /**
     * @var ProjectBackgroundDao
     */
    private $dao;

    public function __construct(ProjectBackgroundDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return ProjectBackground[]
     */
    public function getBackgrounds(Project $project): array
    {
        $current_background_identifier = $this->dao->getBackground((int) $project->getID());

        return [
            ProjectBackground::buildNoBackground($current_background_identifier === null),
            $this->instantiateBackground('bridge', 'David Emrich', $current_background_identifier),
            $this->instantiateBackground('beach', 'Joschko Hammermann', $current_background_identifier),
        ];
    }

    private function instantiateBackground(
        string $identifier,
        string $author,
        ?string $current_background_identifier
    ): ProjectBackground {
        return ProjectBackground::buildFromIdentifier($identifier, $author, $current_background_identifier === $identifier);
    }
}
