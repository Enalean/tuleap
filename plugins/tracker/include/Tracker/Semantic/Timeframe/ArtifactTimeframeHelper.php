<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Semantic\Timeframe;

use DateTime;
use PFUser;
use Tracker_Artifact;
use Tracker_FormElement_Field;

class ArtifactTimeframeHelper
{
    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_builder;

    /**
     * @var TimeframeBuilder
     */
    private $time_frame_builder;

    public function __construct(
        SemanticTimeframeBuilder $semantic_builder,
        TimeframeBuilder $time_frame_builder
    ) {
        $this->semantic_builder   = $semantic_builder;
        $this->time_frame_builder = $time_frame_builder;
    }

    public function artifactHelpShouldBeShownToUser(PFUser $user, Tracker_FormElement_Field $field): bool
    {
        $tracker = $field->getTracker();

        if (! $tracker) {
            return false;
        }

        $timeframe_semantic = $this->semantic_builder->getSemantic($tracker);

        if (! $timeframe_semantic->isDefined()) {
            return false;
        }

        $start_date_field = $timeframe_semantic->getStartDateField();

        if (! $start_date_field) {
            return false;
        }

        if (! $start_date_field->userCanRead($user)) {
            return false;
        }

        return $timeframe_semantic->isDurationField($field) || $timeframe_semantic->isEndDateField($field);
    }

    public function getEndDateArtifactHelperForReadOnlyView(PFUser $user, Tracker_Artifact $artifact): string
    {
        $time_period = $this->time_frame_builder->buildTimePeriodWithoutWeekendForArtifact($artifact, $user);

        $end_date = new DateTime();
        $end_date->setTimestamp((int) $time_period->getEndDate());

        return $end_date->format($GLOBALS['Language']->getText('system', 'datefmt_short'));
    }

    public function getDurationArtifactHelperForReadOnlyView(PFUser $user, Tracker_Artifact $artifact): string
    {
        $duration = (int) $this->time_frame_builder
            ->buildTimePeriodWithoutWeekendForArtifact($artifact, $user)
            ->getDuration();

        return sprintf(dngettext('tuleap-tracker', '%s working day', '%s working days', $duration), $duration);
    }
}
