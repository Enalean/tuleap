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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Hudson\Reference;

use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferenceSectionPresenter;
use ProjectManager;

class HudsonCrossReferenceOrganizer
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(\ProjectManager $project_manager)
    {
        $this->project_manager = $project_manager;
    }

    public function organizeHudsonReferences(CrossReferenceByNatureOrganizer $organizer_by_nature): void
    {
        foreach ($organizer_by_nature->getCrossReferencePresenters() as $cross_reference_presenter) {
            if (
                $cross_reference_presenter->type === \hudsonPlugin::HUDSON_BUILD_NATURE ||
                $cross_reference_presenter->type === \hudsonPlugin::HUDSON_JOB_NATURE
            ) {
                $this->moveHudsonCrossReferenceToUnlabelledSection($organizer_by_nature, $cross_reference_presenter);
                continue;
            }
        }
    }

    private function moveHudsonCrossReferenceToUnlabelledSection(
        CrossReferenceByNatureOrganizer $organizer_by_nature,
        CrossReferencePresenter $cross_reference_presenter,
    ): void {
        $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

        if (! $organizer_by_nature->getCurrentUser()->isMember((int) $project->getID())) {
            $organizer_by_nature->removeUnreadableCrossReference($cross_reference_presenter);
            return;
        }

        $organizer_by_nature->moveCrossReferenceToSection(
            $cross_reference_presenter,
            CrossReferenceSectionPresenter::UNLABELLED,
        );
    }
}
