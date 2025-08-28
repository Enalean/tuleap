<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement\Burnup\Calculator;

use AgileDashboard_Semantic_InitialEffortFactory;
use Override;
use Tracker_Artifact_ChangesetFactory;
use Tuleap\AgileDashboard\FormElement\BurnupEffort;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;

final class BurnupEffortCalculatorForArtifact implements RetrieveBurnupEffortForArtifact
{
    public function __construct(
        private Tracker_Artifact_ChangesetFactory $changeset_factory,
        private AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory,
        private SemanticDoneFactory $semantic_done_factory,
    ) {
    }

    #[Override]
    public function getEffort(
        Artifact $artifact,
        int $timestamp,
    ): BurnupEffort {
        $semantic_initial_effort = $this->initial_effort_factory->getByTracker($artifact->getTracker());
        $semantic_done           = $this->semantic_done_factory->getInstanceByTracker($artifact->getTracker());

        $initial_effort_field = $semantic_initial_effort->getField();
        $changeset            = $this->changeset_factory->getChangesetAtTimestamp($artifact, $timestamp);

        $total_effort = 0;
        $team_effort  = 0;
        if ($changeset === null || $initial_effort_field === null) {
            return new BurnupEffort((float) $team_effort, (float) $total_effort);
        }

        \assert($initial_effort_field instanceof \Tuleap\Tracker\FormElement\Field\TrackerField);
        if ($artifact->getValue($initial_effort_field, $changeset)) {
            $total_effort = $artifact->getValue($initial_effort_field, $changeset)?->getValue();
        }

        if ($semantic_done !== null && $semantic_done->isDone($changeset)) {
            $team_effort = $total_effort;
        }
        return new BurnupEffort((float) $team_effort, (float) $total_effort);
    }
}
