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

namespace Tuleap\Tracker\Artifact\CrossReference;

use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\CrossReferenceSectionPresenter;
use Tuleap\Reference\TitleBadgePresenter;
use Tuleap\Tracker\Artifact\Artifact;

class CrossReferenceArtifactOrganizer
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;

    public function __construct(
        \ProjectManager $project_manager,
        ProjectAccessChecker $project_access_checker,
        \Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->project_manager        = $project_manager;
        $this->artifact_factory       = $artifact_factory;
        $this->project_access_checker = $project_access_checker;
    }

    public function organizeArtifactReferences(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        foreach ($by_nature_organizer->getCrossReferencePresenters() as $cross_reference_presenter) {
            if ($cross_reference_presenter->type !== Artifact::REFERENCE_NATURE) {
                continue;
            }

            $user    = $by_nature_organizer->getCurrentUser();
            $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);
            try {
                $this->project_access_checker->checkUserCanAccessProject($user, $project);
            } catch (\Project_AccessException $e) {
                $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
                continue;
            }

            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, (int) $cross_reference_presenter->target_value);
            if (! $artifact) {
                $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
                continue;
            }

            $by_nature_organizer->moveCrossReferenceToSection(
                $this->addTitleBadgeOnCrossReference($cross_reference_presenter, $artifact),
                CrossReferenceSectionPresenter::UNLABELLED
            );
        }
    }

    private function addTitleBadgeOnCrossReference(CrossReferencePresenter $cross_reference_presenter, Artifact $artifact): CrossReferencePresenter
    {
        return $cross_reference_presenter
            ->withTitle(
                (string) $artifact->getTitle(),
                new TitleBadgePresenter(
                    $artifact->getXRef(),
                    $artifact->getTracker()->getColor()->getName(),
                )
            );
    }
}
