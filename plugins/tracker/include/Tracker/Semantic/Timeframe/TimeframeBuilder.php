<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Psr\Log\LoggerInterface;
use PFUser;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_Artifact_ChangesetValue_Numeric;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field_Date;

class TimeframeBuilder
{
    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        LoggerInterface $logger
    ) {
        $this->semantic_timeframe_builder = $semantic_timeframe_builder;
        $this->logger                     = $logger;
    }

    public function buildTimePeriodWithoutWeekendForArtifact(Tracker_Artifact $artifact, PFUser $user): TimePeriodWithoutWeekEnd
    {
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($artifact->getTracker());

        try {
            $start_date = $this->getTimestamp($user, $artifact, $semantic_timeframe);
        } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
            $start_date = 0;
        }

        if ($semantic_timeframe->getEndDateField() !== null) {
            try {
                $end_date = $this->getEndDateFieldValue($user, $artifact, $semantic_timeframe);
            } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
                $end_date = 0;
            }
            return TimePeriodWithoutWeekEnd::buildFromEndDate($start_date, $end_date, $this->logger);
        }

        try {
            $duration = $this->getDurationFieldValue($user, $artifact, $semantic_timeframe);
        } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
            $duration = 0;
        }

        return TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);
    }

    public function buildTimePeriodWithoutWeekendForArtifactForREST(Tracker_Artifact $artifact, PFUser $user): TimePeriodWithoutWeekEnd
    {
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($artifact->getTracker());

        try {
            $start_date = $this->getTimestamp($user, $artifact, $semantic_timeframe);
        } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
            $start_date = null;
        }

        if ($semantic_timeframe->getEndDateField() !== null) {
            try {
                $end_date = $this->getEndDateFieldValue($user, $artifact, $semantic_timeframe);
            } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
                $end_date = null;
            }
            return TimePeriodWithoutWeekEnd::buildFromEndDate($start_date, $end_date, $this->logger);
        }

        try {
            $duration = $this->getDurationFieldValue($user, $artifact, $semantic_timeframe);
        } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
            $duration = null;
        }

        return TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);
    }

    /**
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function buildTimePeriodWithoutWeekendForArtifactChartRendering(Tracker_Artifact $artifact, PFUser $user): TimePeriodWithoutWeekEnd
    {
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($artifact->getTracker());

        try {
            $start_date = $this->getTimestamp($user, $artifact, $semantic_timeframe);

            if (! $start_date) {
                throw new Tracker_FormElement_Chart_Field_Exception(
                    dgettext('tuleap-tracker', '"start date" field is empty or invalid')
                );
            }
        } catch (TimeframeFieldNotFoundException $exception) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', 'The tracker doesn\'t have a "start_date" Date field or you don\'t have the permission to access it.')
            );
        } catch (TimeframeFieldNoValueException $exception) {
            $start_date = null;
        }

        if ($semantic_timeframe->getEndDateField() !== null) {
            try {
                $end_date = $this->getEndDateFieldValue($user, $artifact, $semantic_timeframe);

                if (! $end_date) {
                    throw new Tracker_FormElement_Chart_Field_Exception(
                        dgettext('tuleap-tracker', '"end date" field is empty or invalid')
                    );
                }
            } catch (TimeframeFieldNotFoundException $exception) {
                throw new Tracker_FormElement_Chart_Field_Exception(
                    dgettext('tuleap-tracker', 'The tracker doesn\'t have a "end_date" Date field or you don\'t have the permission to access it.')
                );
            } catch (TimeframeFieldNoValueException $exception) {
                throw new Tracker_FormElement_Chart_Field_Exception(
                    dgettext('tuleap-tracker', '"end date" field is empty or invalid')
                );
            }

            return TimePeriodWithoutWeekEnd::buildFromEndDate($start_date, $end_date, $this->logger);
        }

        try {
            $duration = $this->getDurationFieldValue($user, $artifact, $semantic_timeframe);

            if ($duration === null) {
                throw new Tracker_FormElement_Chart_Field_Exception(
                    dgettext('tuleap-tracker', '"duration" field is empty or invalid')
                );
            }

            if ($duration <= 0) {
                throw new Tracker_FormElement_Chart_Field_Exception(
                    dgettext('tuleap-tracker', '"duration" field is empty or invalid')
                );
            }

            if ($duration === 1) {
                throw new Tracker_FormElement_Chart_Field_Exception(
                    dgettext('tuleap-tracker', '"duration" must be greater than 1 to display burndown graph.')
                );
            }
        } catch (TimeframeFieldNotFoundException $exception) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', 'The tracker doesn\'t have a "duration" Integer field or you don\'t have the permission to access it.')
            );
        } catch (TimeframeFieldNoValueException $exception) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', '"duration" field is empty or invalid')
            );
        }

        return TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);
    }

    /**
     * @throws TimeframeFieldNotFoundException
     * @throws TimeframeFieldNoValueException
     */
    private function getTimestamp(PFUser $user, Tracker_Artifact $artifact, SemanticTimeframe $semantic_timeframe): int
    {
        $field = $semantic_timeframe->getStartDateField();
        if ($field === null || ! $field->userCanRead($user)) {
            throw new TimeframeFieldNotFoundException();
        }

        assert($field instanceof Tracker_FormElement_Field_Date);

        $value = $field->getLastChangesetValue($artifact);
        if ($value === null) {
            throw new TimeframeFieldNoValueException();
        }

        assert($value instanceof Tracker_Artifact_ChangesetValue_Date);

        return (int) $value->getTimestamp();
    }

    /**
     * @throws TimeframeFieldNotFoundException
     * @throws TimeframeFieldNoValueException
     */
    private function getDurationFieldValue(PFUser $user, Tracker_Artifact $milestone_artifact, SemanticTimeframe $semantic_timeframe)
    {
        $field = $semantic_timeframe->getDurationField();

        if ($field === null || ! $field->userCanRead($user)) {
            throw new TimeframeFieldNotFoundException();
        }

        $last_changeset_value = $field->getLastChangesetValue($milestone_artifact);
        if ($last_changeset_value === null) {
            throw new TimeframeFieldNoValueException();
        }

        assert($last_changeset_value instanceof Tracker_Artifact_ChangesetValue_Numeric);

        return $last_changeset_value->getNumeric();
    }

    /**
     * @throws TimeframeFieldNotFoundException
     * @throws TimeframeFieldNoValueException
     */
    private function getEndDateFieldValue(PFUser $user, Tracker_Artifact $milestone_artifact, SemanticTimeframe $semantic_timeframe): int
    {
        $field = $semantic_timeframe->getEndDateField();

        if ($field === null || ! $field->userCanRead($user)) {
            throw new TimeframeFieldNotFoundException();
        }

        $last_changeset_value = $field->getLastChangesetValue($milestone_artifact);
        if ($last_changeset_value === null) {
            throw new TimeframeFieldNoValueException();
        }

        assert($last_changeset_value instanceof Tracker_Artifact_ChangesetValue_Date);

        return (int) $last_changeset_value->getTimestamp();
    }
}
