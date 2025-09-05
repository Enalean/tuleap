<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Tracker\REST\SemanticTimeframeWithEndDateRepresentation;

class TimeframeWithEndDate implements IComputeTimeframes
{
    private const NAME = 'timeframe-with-end-date';

    /**
     * @var \Tuleap\Tracker\FormElement\Field\Date\DateField
     */
    private $start_date_field;
    /**
     * @var \Tuleap\Tracker\FormElement\Field\Date\DateField
     */
    private $end_date_field;

    public function __construct(
        \Tuleap\Tracker\FormElement\Field\Date\DateField $start_date_field,
        \Tuleap\Tracker\FormElement\Field\Date\DateField $end_date_field,
    ) {
        $this->start_date_field = $start_date_field;
        $this->end_date_field   = $end_date_field;
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function isFieldUsed(\Tuleap\Tracker\FormElement\Field\TrackerField $field): bool
    {
        $field_id = $field->getId();

        return $field_id === $this->start_date_field->getId() ||
            $field_id === $this->end_date_field->getId();
    }

    #[\Override]
    public function getConfigDescription(): string
    {
        return sprintf(
            dgettext('tuleap-tracker', 'Timeframe is based on start date field "%s" and end date field "%s".'),
            $this->start_date_field->getLabel(),
            $this->end_date_field->getLabel()
        );
    }

    #[\Override]
    public function isDefined(): bool
    {
        return true;
    }

    #[\Override]
    public function exportToXML(\SimpleXMLElement $root, array $xml_mapping): void
    {
        $start_date_field_id = $this->start_date_field->getId();
        $start_date_ref      = array_search($start_date_field_id, $xml_mapping);
        $end_date_field_id   = $this->end_date_field->getId();
        $end_date_ref        = array_search($end_date_field_id, $xml_mapping);

        if (! $start_date_ref || ! $end_date_ref) {
            return;
        }

        $semantic = $root->addChild('semantic');
        $semantic->addAttribute('type', SemanticTimeframe::NAME);
        $semantic->addChild('start_date_field')->addAttribute('REF', $start_date_ref);
        $semantic->addChild('end_date_field')->addAttribute('REF', $end_date_ref);
    }

    #[\Override]
    public function exportToREST(\PFUser $user): ?IRepresentSemanticTimeframe
    {
        if (
            ! $this->start_date_field->userCanRead($user) ||
            ! $this->end_date_field->userCanRead($user)
        ) {
            return null;
        }

        return new SemanticTimeframeWithEndDateRepresentation(
            $this->start_date_field->getId(),
            $this->end_date_field->getId()
        );
    }

    #[\Override]
    public function save(\Tuleap\Tracker\Tracker $tracker, SemanticTimeframeDao $dao): bool
    {
        return $dao->save(
            $tracker->getId(),
            $this->start_date_field->getId(),
            null,
            $this->end_date_field->getId(),
            null
        );
    }

    #[\Override]
    public function getStartDateField(): ?\Tuleap\Tracker\FormElement\Field\Date\DateField
    {
        return $this->start_date_field;
    }

    #[\Override]
    public function getEndDateField(): \Tuleap\Tracker\FormElement\Field\Date\DateField
    {
        return $this->end_date_field;
    }

    #[\Override]
    public function getDurationField(): ?\Tuleap\Tracker\FormElement\Field\NumericField
    {
        return null;
    }

    #[\Override]
    public function buildDatePeriodWithoutWeekendForChangesetForREST(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithOpenDays
    {
        if ($changeset === null) {
            return DatePeriodWithOpenDays::buildWithoutAnyDates();
        }

        try {
            $start_date = TimeframeChangesetFieldsValueRetriever::getTimestamp($this->start_date_field, $user, $changeset);
        } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
            $start_date = null;
        }

        try {
            $end_date = TimeframeChangesetFieldsValueRetriever::getTimestamp($this->end_date_field, $user, $changeset);
        } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
            $end_date = null;
        }
        return DatePeriodWithOpenDays::buildFromEndDate($start_date, $end_date, $logger);
    }

    #[\Override]
    public function buildDatePeriodWithoutWeekendForChangeset(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithOpenDays
    {
        if ($changeset === null) {
            return DatePeriodWithOpenDays::buildWithoutAnyDates();
        }

        try {
            $start_date = TimeframeChangesetFieldsValueRetriever::getTimestamp($this->start_date_field, $user, $changeset);
        } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
            $start_date = 0;
        }

        try {
            $end_date = TimeframeChangesetFieldsValueRetriever::getTimestamp($this->end_date_field, $user, $changeset);
        } catch (TimeframeFieldNotFoundException | TimeframeFieldNoValueException $exception) {
            $end_date = 0;
        }
        return DatePeriodWithOpenDays::buildFromEndDate($start_date, $end_date, $logger);
    }

    /**
     * @throws \Tracker_FormElement_Chart_Field_Exception
     */
    #[\Override]
    public function buildDatePeriodWithoutWeekendForChangesetChartRendering(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithOpenDays
    {
        if ($changeset === null) {
            return DatePeriodWithOpenDays::buildWithoutAnyDates();
        }

        try {
            try {
                $start_date = TimeframeChangesetFieldsValueRetriever::getTimestamp($this->start_date_field, $user, $changeset);
            } catch (TimeframeFieldNoValueException $exception) {
                $start_date = null;
            }

            if (! $start_date) {
                throw new \Tracker_FormElement_Chart_Field_Exception(
                    dgettext('tuleap-tracker', '"start date" field is empty or invalid')
                );
            }
        } catch (TimeframeFieldNotFoundException $exception) {
            throw new \Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', 'The tracker doesn\'t have a "start_date" Date field or you don\'t have the permission to access it.')
            );
        }

        try {
            $end_date = TimeframeChangesetFieldsValueRetriever::getTimestamp($this->end_date_field, $user, $changeset);

            if (! $end_date) {
                throw new \Tracker_FormElement_Chart_Field_Exception(
                    dgettext('tuleap-tracker', '"end date" field is empty or invalid')
                );
            }
        } catch (TimeframeFieldNotFoundException $exception) {
            throw new \Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', 'The tracker doesn\'t have a "end_date" Date field or you don\'t have the permission to access it.')
            );
        } catch (TimeframeFieldNoValueException $exception) {
            throw new \Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', '"end date" field is empty or invalid')
            );
        }

        $logger->debug(
            'Checking timeframe for artifact #' . $changeset->getArtifact()->getId()
        );
        return DatePeriodWithOpenDays::buildFromEndDate($start_date, $end_date, $logger);
    }

    #[\Override]
    public function getTrackerFromWhichTimeframeIsImplied(): ?\Tuleap\Tracker\Tracker
    {
        return null;
    }

    #[\Override]
    public function userCanReadTimeframeFields(\PFUser $user): bool
    {
        return $this->start_date_field->userCanRead($user) && $this->end_date_field->userCanRead($user);
    }

    #[\Override]
    public function isAllSetToZero(\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): bool
    {
        try {
            $start_date = TimeframeChangesetFieldsValueRetriever::getTimestamp($this->start_date_field, $user, $changeset);
        } catch (TimeframeFieldNotFoundException) {
            $start_date = null;
            $logger->debug('TimeframeWithDuration::isAllSetToZero -> Override start_date to null');
        } catch (TimeframeFieldNoValueException) {
            $start_date = 0;
            $logger->debug('TimeframeWithDuration::isAllSetToZero -> Override start_date to 0');
        }

        try {
            $end_date = TimeframeChangesetFieldsValueRetriever::getTimestamp($this->end_date_field, $user, $changeset);
        } catch (TimeframeFieldNotFoundException) {
            $end_date = null;
            $logger->debug('TimeframeWithDuration::isAllSetToZero -> Override end_date to null');
        } catch (TimeframeFieldNoValueException) {
            $end_date = 0;
            $logger->debug('TimeframeWithDuration::isAllSetToZero -> Override end_date to 0');
        }

        $logger->debug("TimeframeWithEndDate::isAllSetToZero -> start = {$start_date}, end = {$end_date}");

        return $start_date === 0 && $end_date === 0;
    }

    #[\Override]
    public function isTimeDisplayedForEvent(): bool
    {
        return $this->start_date_field->isTimeDisplayed() &&
               $this->end_date_field->isTimeDisplayed();
    }
}
