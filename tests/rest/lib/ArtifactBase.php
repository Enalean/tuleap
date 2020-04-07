<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All rights reserved
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

namespace Tuleap\REST;

use RestBase;
use REST_TestDataBuilder;

class ArtifactBase extends RestBase
{
    protected $level_one_tracker_id;
    protected $level_two_tracker_id;
    protected $level_three_tracker_id;
    protected $level_four_tracker_id;
    protected $niveau_1_tracker_id;
    protected $niveau_2_tracker_id;
    protected $pokemon_tracker_id;
    protected $suspended_tracker_id;

    protected $level_one_artifact_ids          = [];
    protected $level_two_artifact_ids          = [];
    protected $level_three_artifact_ids        = [];
    protected $level_four_artifact_ids         = [];
    protected $niveau_1_artifact_ids           = [];
    protected $niveau_2_artifact_ids           = [];
    protected $pokemon_artifact_ids            = [];
    protected $suspended_tracker_artifacts_ids = [];

    protected $project_computed_fields_id;
    protected $project_burndown_id;
    protected $project_suspended_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->getReleaseArtifactIds();
        $this->getStoryArtifactIds();

        $this->project_computed_fields_id = $this->getProjectId(REST_TestDataBuilder::PROJECT_COMPUTED_FIELDS);
        $this->project_burndown_id        = $this->getProjectId(REST_TestDataBuilder::PROJECT_BURNDOWN);
        $this->project_suspended_id       = $this->getProjectId(REST_TestDataBuilder::PROJECT_SUSPENDED_SHORTNAME);

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

    private function getTrackerIdsForComputedFieldsProject()
    {
        $this->level_one_tracker_id   = $this->tracker_ids[$this->project_computed_fields_id][REST_TestDataBuilder::LEVEL_ONE_TRACKER_SHORTNAME];
        $this->level_two_tracker_id   = $this->tracker_ids[$this->project_computed_fields_id][REST_TestDataBuilder::LEVEL_TWO_TRACKER_SHORTNAME];
        $this->level_three_tracker_id = $this->tracker_ids[$this->project_computed_fields_id][REST_TestDataBuilder::LEVEL_THREE_TRACKER_SHORTNAME];
        $this->level_four_tracker_id  = $this->tracker_ids[$this->project_computed_fields_id][REST_TestDataBuilder::LEVEL_FOUR_TRACKER_SHORTNAME];
    }

    private function getLevelOneArtifactIds()
    {
        $this->getArtifactIds(
            $this->level_one_tracker_id,
            $this->level_one_artifact_ids
        );
    }

    private function getLevelTwoArtifactIds()
    {
        $this->getArtifactIds(
            $this->level_two_tracker_id,
            $this->level_two_artifact_ids
        );
    }

    private function getLevelThreeArtifactIds()
    {
        $this->getArtifactIds(
            $this->level_three_tracker_id,
            $this->level_three_artifact_ids
        );
    }

    private function getLevelFourArtifactIds()
    {
        $this->getArtifactIds(
            $this->level_four_tracker_id,
            $this->level_four_artifact_ids
        );
    }

    private function getTrackerIdsForBurndownProject()
    {
        $this->niveau_1_tracker_id = $this->tracker_ids[$this->project_burndown_id][REST_TestDataBuilder::NIVEAU_1_TRACKER_SHORTNAME];
        $this->niveau_2_tracker_id = $this->tracker_ids[$this->project_burndown_id][REST_TestDataBuilder::NIVEAU_2_TRACKER_SHORTNAME];
        $this->pokemon_tracker_id  = $this->tracker_ids[$this->project_burndown_id][REST_TestDataBuilder::POKEMON_TRACKER_SHORTNAME];
    }

    private function getNiveau1ArtifactIds()
    {
        $this->getArtifactIds(
            $this->niveau_1_tracker_id,
            $this->niveau_1_artifact_ids
        );
    }

    private function getNiveau2ArtifactIds()
    {
        $this->getArtifactIds(
            $this->niveau_2_tracker_id,
            $this->niveau_2_artifact_ids
        );
    }

    private function getPokemonArtifactIds()
    {
        $this->getArtifactIds(
            $this->pokemon_tracker_id,
            $this->pokemon_artifact_ids
        );
    }

    private function getSuspendedTrackerId()
    {
        $this->suspended_tracker_id = $this->tracker_ids[$this->project_suspended_id][REST_TestDataBuilder::SUSPENDED_TRACKER_SHORTNAME];
    }

    private function getSuspendedTrackerArtifactsIds()
    {
        $this->getArtifactIds(
            $this->suspended_tracker_id,
            $this->suspended_tracker_artifacts_ids
        );
    }
}
