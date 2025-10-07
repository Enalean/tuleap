<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Tracker_Artifact_Changeset;
use DateTime;
use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_ChangesetValue_Date;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\TrackerField;

class ArtifactTimeframeHelper
{
    /**
     * @var BuildSemanticTimeframe
     */
    private $semantic_builder;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        BuildSemanticTimeframe $semantic_builder,
        LoggerInterface $logger,
    ) {
        $this->semantic_builder = $semantic_builder;
        $this->logger           = $logger;
    }

    public function artifactHelpShouldBeShownToUser(PFUser $user, TrackerField $field, Artifact $artifact, Tracker_Artifact_Changeset $changeset): bool
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

        /** @var ?Tracker_Artifact_ChangesetValue_Date $start_date_changeset_value */
        $start_date_changeset_value = $changeset->getValue($start_date_field);
        $start_timestamp            = $start_date_changeset_value?->getTimestamp() ?? 0;

        if ($start_date_field->isEmpty($start_timestamp, $artifact) || $start_timestamp === 0) {
            return false;
        }

        return $field->getId() !== $start_date_field->getId() && $timeframe_semantic->isUsedInSemantics($field);
    }

    public function getEndDateArtifactHelperForReadOnlyView(PFUser $user, Artifact $artifact): string
    {
        $timeframe_semantic = $this->semantic_builder->getSemantic($artifact->getTracker());
        $date_period        = $timeframe_semantic
            ->getTimeframeCalculator()
            ->buildDatePeriodWithoutWeekendForChangeset(
                $artifact->getLastChangeset(),
                $user,
                $this->logger
            );

        $end_date = new DateTime();
        $end_date->setTimestamp((int) $date_period->getEndDate());

        return $end_date->format($GLOBALS['Language']->getText('system', 'datefmt'));
    }

    public function getDurationArtifactHelperForReadOnlyView(PFUser $user, Artifact $artifact): string
    {
        $timeframe_semantic = $this->semantic_builder->getSemantic($artifact->getTracker());
        $duration           = (int) $timeframe_semantic
            ->getTimeframeCalculator()
            ->buildDatePeriodWithoutWeekendForChangeset($artifact->getLastChangeset(), $user, $this->logger)
            ->getDuration();

        return sprintf(dngettext('tuleap-tracker', '%s working day', '%s working days', $duration), $duration);
    }
}
