<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Team;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveOpenFeatureCount;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchOpenFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisibleByProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class PossibleParentHandler
{
    public function __construct(
        private VerifyFeatureIsVisibleByProgram $visible_verifier,
        private BuildProgram $program_builder,
        private SearchProgramsOfTeam $programs_searcher,
        private SearchOpenFeatures $search_open_features,
        private RetrieveOpenFeatureCount $retrieve_open_feature_count,
    ) {
    }

    public function handle(PossibleParentSelectorEvent $possible_parent_selector): void
    {
        if (! $possible_parent_selector->trackerIsInRootPlanning()) {
            return;
        }

        $program_ids = $this->programs_searcher->searchProgramIdsOfTeam($possible_parent_selector->getProjectId());
        if (count($program_ids) === 0) {
            return;
        }

        $programs = [];
        foreach ($program_ids as $program_id) {
            try {
                $programs[$program_id] = ProgramIdentifier::fromId(
                    $this->program_builder,
                    $program_id,
                    $possible_parent_selector->getUser(),
                    null
                );
            } catch (ProgramAccessException) {
                continue;
            }
        }

        $features = [];
        foreach ($this->search_open_features->searchOpenFeatures($possible_parent_selector->getOffset(), $possible_parent_selector->getLimit(), ...$programs) as $feature) {
            $feature_identifier = FeatureIdentifier::fromIdAndProgram(
                $this->visible_verifier,
                $feature['artifact_id'],
                $possible_parent_selector->getUser(),
                $programs[$feature['program_id']],
                null,
            );

            if (! $feature_identifier) {
                continue;
            }

            $features[] = new FeatureReference(
                $feature_identifier,
                $feature['title']
            );
        }

        $possible_parent_selector->disableCreate();
        $possible_parent_selector->setPossibleParents($this->retrieve_open_feature_count->retrieveOpenFeaturesCount(...$programs), ...$features);
    }
}
