<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\FeatureRemoval;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RemoveFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RemoveFeatureException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

final class FeatureRemovalProcessor implements RemoveFeature
{
    /**
     * @var ProgramIncrementsDAO
     */
    private $program_increments_dao;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var ArtifactLinkUpdater
     */
    private $artifact_link_updater;

    public function __construct(
        ProgramIncrementsDAO $program_increments_dao,
        \Tracker_ArtifactFactory $artifact_factory,
        ArtifactLinkUpdater $artifact_link_updater
    ) {
        $this->program_increments_dao = $program_increments_dao;
        $this->artifact_factory       = $artifact_factory;
        $this->artifact_link_updater  = $artifact_link_updater;
    }

    public function removeFromAllProgramIncrements(FeatureRemoval $feature_removal): void
    {
        $program_ids = $this->program_increments_dao->getProgramIncrementsLinkToFeatureId($feature_removal->feature_id);
        foreach ($program_ids as $program_id) {
            $program_increment_artifact = $this->artifact_factory->getArtifactById($program_id['id']);
            if (! $program_increment_artifact) {
                continue;
            }
            try {
                $this->artifact_link_updater->updateArtifactLinks(
                    $feature_removal->user->getFullUser(),
                    $program_increment_artifact,
                    [],
                    [$feature_removal->feature_id],
                    \Tracker_FormElement_Field_ArtifactLink::NO_NATURE
                );
            } catch (\Tracker_NoArtifactLinkFieldException | \Tracker_Exception $e) {
                throw new RemoveFeatureException($feature_removal->feature_id, $e);
            }
        }
    }
}
