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

namespace Tuleap\Git\Reference;

use Git;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;

class CrossReferenceGitOrganizer
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \Git_ReferenceManager
     */
    private $git_reference_manager;

    public function __construct(\ProjectManager $project_manager, \Git_ReferenceManager $git_reference_manager)
    {
        $this->project_manager       = $project_manager;
        $this->git_reference_manager = $git_reference_manager;
    }

    public function organizeGitReferences(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        foreach ($by_nature_organizer->getCrossReferences() as $cross_reference) {
            if ($cross_reference->type !== Git::REFERENCE_NATURE) {
                continue;
            }

            $project    = $this->project_manager->getProject($cross_reference->target_gid);
            $repository = $this->git_reference_manager->getRepositoryFromCrossReferenceValue(
                $project,
                $cross_reference->target_value
            );
            if ($repository) {
                $by_nature_organizer->moveCrossReferenceToSection(
                    $cross_reference,
                    $project->getUnixNameLowerCase() . '/' . $repository->getFullName()
                );
            }
        }
    }
}
