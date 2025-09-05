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

class TimeframeNotConfigured implements IComputeTimeframes
{
    public const NAME = 'timeframe-not-configured';

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getConfigDescription(): string
    {
        return dgettext('tuleap-tracker', 'This semantic is not defined yet.');
    }

    #[\Override]
    public function exportToXML(\SimpleXMLElement $root, array $xml_mapping): void
    {
    }

    #[\Override]
    public function exportToREST(\PFUser $user): ?IRepresentSemanticTimeframe
    {
        return null;
    }

    #[\Override]
    public function isFieldUsed(\Tuleap\Tracker\FormElement\Field\TrackerField $field): bool
    {
        return false;
    }

    #[\Override]
    public function isDefined(): bool
    {
        return false;
    }

    #[\Override]
    public function save(\Tuleap\Tracker\Tracker $tracker, SemanticTimeframeDao $dao): bool
    {
        return false;
    }

    #[\Override]
    public function getStartDateField(): ?\Tuleap\Tracker\FormElement\Field\Date\DateField
    {
        return null;
    }

    #[\Override]
    public function getEndDateField(): ?\Tuleap\Tracker\FormElement\Field\Date\DateField
    {
        return null;
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

        return DatePeriodWithOpenDays::buildFromNothingWithErrorMessage(
            $this->getErrorMessage($changeset)
        );
    }

    #[\Override]
    public function buildDatePeriodWithoutWeekendForChangeset(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithOpenDays
    {
        if ($changeset === null) {
            return DatePeriodWithOpenDays::buildWithoutAnyDates();
        }

        return DatePeriodWithOpenDays::buildFromNothingWithErrorMessage(
            $this->getErrorMessage($changeset)
        );
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

        throw new \Tracker_FormElement_Chart_Field_Exception(
            $this->getErrorMessage($changeset)
        );
    }

    private function getErrorMessage(\Tracker_Artifact_Changeset $changeset): string
    {
        return sprintf(
            dgettext('tuleap-tracker', 'Semantic Timeframe is not configured for tracker %s.'),
            $changeset->getTracker()->getName()
        );
    }

    #[\Override]
    public function getTrackerFromWhichTimeframeIsImplied(): ?\Tuleap\Tracker\Tracker
    {
        return null;
    }

    #[\Override]
    public function userCanReadTimeframeFields(\PFUser $user): bool
    {
        return false;
    }

    #[\Override]
    public function isAllSetToZero(\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): bool
    {
        $logger->error('TimeframeNotConfigured::isAllSetToZero -> should not be called');
        return false;
    }

    #[\Override]
    public function isTimeDisplayedForEvent(): bool
    {
        return false;
    }
}
