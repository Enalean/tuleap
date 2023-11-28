<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Semantic\Timeframe;

use Psr\Log\LoggerInterface;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\IRepresentSemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

final class IComputeTimeframesStub implements IComputeTimeframes
{
    private function __construct(
        private readonly DatePeriodWithoutWeekEnd $date_period,
        private readonly ?\Tracker_FormElement_Field_Date $start,
        private readonly ?\Tracker_FormElement_Field_Date $end,
        private readonly ?\Tracker_FormElement_Field_Numeric $duration,
    ) {
    }

    public static function fromStartAndEndDates(
        DatePeriodWithoutWeekEnd $date_period,
        \Tracker_FormElement_Field_Date $start,
        \Tracker_FormElement_Field_Date $end,
    ): self {
        return new self($date_period, $start, $end, null);
    }

    public static function fromStartAndDuration(
        DatePeriodWithoutWeekEnd $date_period,
        \Tracker_FormElement_Field_Date $start,
        \Tracker_FormElement_Field_Numeric $duration,
    ): self {
        return new self($date_period, $start, null, $duration);
    }

    public static function getName(): string
    {
        return '';
    }

    public function getConfigDescription(): string
    {
        return '';
    }

    public function getStartDateField(): ?\Tracker_FormElement_Field_Date
    {
        return $this->start;
    }

    public function getEndDateField(): ?\Tracker_FormElement_Field_Date
    {
        return $this->end;
    }

    public function getDurationField(): ?\Tracker_FormElement_Field_Numeric
    {
        return $this->duration;
    }

    public function getTrackerFromWhichTimeframeIsImplied(): ?\Tracker
    {
        return null;
    }

    public function buildDatePeriodWithoutWeekendForChangesetForREST(
        ?\Tracker_Artifact_Changeset $changeset,
        \PFUser $user,
        LoggerInterface $logger,
    ): DatePeriodWithoutWeekEnd {
        return $this->date_period;
    }

    public function buildDatePeriodWithoutWeekendForChangeset(
        ?\Tracker_Artifact_Changeset $changeset,
        \PFUser $user,
        LoggerInterface $logger,
    ): DatePeriodWithoutWeekEnd {
        return $this->date_period;
    }

    public function buildDatePeriodWithoutWeekendForChangesetChartRendering(
        ?\Tracker_Artifact_Changeset $changeset,
        \PFUser $user,
        LoggerInterface $logger,
    ): DatePeriodWithoutWeekEnd {
        return $this->date_period;
    }

    public function exportToXML(\SimpleXMLElement $root, array $xml_mapping): void
    {
    }

    public function exportToREST(\PFUser $user): ?IRepresentSemanticTimeframe
    {
        return null;
    }

    public function save(\Tracker $tracker, SemanticTimeframeDao $dao): bool
    {
        return true;
    }

    public function isFieldUsed(\Tracker_FormElement_Field $field): bool
    {
        return true;
    }

    public function isDefined(): bool
    {
        return true;
    }

    public function userCanReadTimeframeFields(\PFUser $user): bool
    {
        return (! $this->start || $this->start->userCanRead($user)) &&
               (! $this->end || $this->end->userCanRead($user)) &&
               (! $this->duration || $this->duration->userCanRead($user));
    }

    public function isAllSetToZero(\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): bool
    {
        return $this->date_period->getDuration() === 0 &&
               $this->date_period->getStartDate() === 0 &&
               $this->date_period->getEndDate() === 0;
    }

    public function isTimeDisplayedForEvent(): bool
    {
        return false;
    }
}
