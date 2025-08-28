<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\REST;

class ArtifactBase extends RestBase
{
    protected int $level_one_tracker_id;
    protected int $level_two_tracker_id;
    protected int $level_three_tracker_id;
    protected int $level_four_tracker_id;
    protected int $niveau_1_tracker_id;
    protected int $niveau_2_tracker_id;
    protected int $pokemon_tracker_id;
    protected int $suspended_tracker_id;

    protected array $level_one_artifact_ids          = [];
    protected array $level_two_artifact_ids          = [];
    protected array $level_three_artifact_ids        = [];
    protected array $level_four_artifact_ids         = [];
    protected array $niveau_1_artifact_ids           = [];
    protected array $niveau_2_artifact_ids           = [];
    protected array $pokemon_artifact_ids            = [];
    protected array $suspended_tracker_artifacts_ids = [];

    protected int $project_computed_fields_id;
    protected int $project_burndown_id;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->getReleaseArtifactIds();
        $this->getStoryArtifactIds();

        $this->project_computed_fields_id = $this->getProjectId(BaseTestDataBuilder::PROJECT_COMPUTED_FIELDS);
        $this->project_burndown_id        = $this->getProjectId(BaseTestDataBuilder::PROJECT_BURNDOWN);

        $this->getTrackerIdsForComputedFieldsProject();
        $this->getLevelOneArtifactIds();
        $this->getLevelTwoArtifactIds();
        $this->getLevelThreeArtifactIds();
        $this->getLevelFourArtifactIds();
        $this->getSuspendedTrackerId();

        $this->getTrackerIdsForBurndownProject();
        $this->getNiveau1ArtifactIds();
        $this->getNiveau2ArtifactIds();
        $this->getPokemonArtifactIds();
        $this->getSuspendedTrackerArtifactsIds();
    }

    private function getTrackerIdsForComputedFieldsProject(): void
    {
        $this->level_one_tracker_id   = $this->tracker_ids[$this->project_computed_fields_id][RESTTestDataBuilder::LEVEL_ONE_TRACKER_SHORTNAME];
        $this->level_two_tracker_id   = $this->tracker_ids[$this->project_computed_fields_id][RESTTestDataBuilder::LEVEL_TWO_TRACKER_SHORTNAME];
        $this->level_three_tracker_id = $this->tracker_ids[$this->project_computed_fields_id][RESTTestDataBuilder::LEVEL_THREE_TRACKER_SHORTNAME];
        $this->level_four_tracker_id  = $this->tracker_ids[$this->project_computed_fields_id][RESTTestDataBuilder::LEVEL_FOUR_TRACKER_SHORTNAME];
    }

    private function getLevelOneArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->level_one_tracker_id,
            $this->level_one_artifact_ids
        );
    }

    private function getLevelTwoArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->level_two_tracker_id,
            $this->level_two_artifact_ids
        );
    }

    private function getLevelThreeArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->level_three_tracker_id,
            $this->level_three_artifact_ids
        );
    }

    private function getLevelFourArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->level_four_tracker_id,
            $this->level_four_artifact_ids
        );
    }

    private function getTrackerIdsForBurndownProject(): void
    {
        $this->niveau_1_tracker_id = $this->tracker_ids[$this->project_burndown_id][RESTTestDataBuilder::NIVEAU_1_TRACKER_SHORTNAME];
        $this->niveau_2_tracker_id = $this->tracker_ids[$this->project_burndown_id][RESTTestDataBuilder::NIVEAU_2_TRACKER_SHORTNAME];
        $this->pokemon_tracker_id  = $this->tracker_ids[$this->project_burndown_id][RESTTestDataBuilder::POKEMON_TRACKER_SHORTNAME];
    }

    private function getNiveau1ArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->niveau_1_tracker_id,
            $this->niveau_1_artifact_ids
        );
    }

    private function getNiveau2ArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->niveau_2_tracker_id,
            $this->niveau_2_artifact_ids
        );
    }

    private function getPokemonArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->pokemon_tracker_id,
            $this->pokemon_artifact_ids
        );
    }

    private function getSuspendedTrackerId(): void
    {
        $this->suspended_tracker_id = $this->tracker_ids[$this->project_suspended_id][RESTTestDataBuilder::SUSPENDED_TRACKER_SHORTNAME];
    }

    private function getSuspendedTrackerArtifactsIds(): void
    {
        $this->getArtifactIds(
            $this->suspended_tracker_id,
            $this->suspended_tracker_artifacts_ids
        );
    }
}
