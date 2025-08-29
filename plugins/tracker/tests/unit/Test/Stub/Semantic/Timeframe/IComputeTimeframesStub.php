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
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\IRepresentSemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

final class IComputeTimeframesStub implements IComputeTimeframes
{
    private function __construct(
        private readonly DatePeriodWithOpenDays $date_period,
        private readonly ?\Tuleap\Tracker\FormElement\Field\Date\DateField $start,
        private readonly ?\Tuleap\Tracker\FormElement\Field\Date\DateField $end,
        private readonly ?\Tuleap\Tracker\FormElement\Field\NumericField $duration,
    ) {
    }

    public static function fromStartAndEndDates(
        DatePeriodWithOpenDays $date_period,
        \Tuleap\Tracker\FormElement\Field\Date\DateField $start,
        \Tuleap\Tracker\FormElement\Field\Date\DateField $end,
    ): self {
        return new self($date_period, $start, $end, null);
    }

    public static function fromStartAndDuration(
        DatePeriodWithOpenDays $date_period,
        \Tuleap\Tracker\FormElement\Field\Date\DateField $start,
        \Tuleap\Tracker\FormElement\Field\NumericField $duration,
    ): self {
        return new self($date_period, $start, null, $duration);
    }

    #[\Override]
    public function getName(): string
    {
        return '';
    }

    #[\Override]
    public function getConfigDescription(): string
    {
        return '';
    }

    #[\Override]
    public function getStartDateField(): ?\Tuleap\Tracker\FormElement\Field\Date\DateField
    {
        return $this->start;
    }

    #[\Override]
    public function getEndDateField(): ?\Tuleap\Tracker\FormElement\Field\Date\DateField
    {
        return $this->end;
    }

    #[\Override]
    public function getDurationField(): ?\Tuleap\Tracker\FormElement\Field\NumericField
    {
        return $this->duration;
    }

    #[\Override]
    public function getTrackerFromWhichTimeframeIsImplied(): ?\Tuleap\Tracker\Tracker
    {
        return null;
    }

    #[\Override]
    public function buildDatePeriodWithoutWeekendForChangesetForREST(
        ?\Tracker_Artifact_Changeset $changeset,
        \PFUser $user,
        LoggerInterface $logger,
    ): DatePeriodWithOpenDays {
        return $this->date_period;
    }

    #[\Override]
    public function buildDatePeriodWithoutWeekendForChangeset(
        ?\Tracker_Artifact_Changeset $changeset,
        \PFUser $user,
        LoggerInterface $logger,
    ): DatePeriodWithOpenDays {
        return $this->date_period;
    }

    #[\Override]
    public function buildDatePeriodWithoutWeekendForChangesetChartRendering(
        ?\Tracker_Artifact_Changeset $changeset,
        \PFUser $user,
        LoggerInterface $logger,
    ): DatePeriodWithOpenDays {
        return $this->date_period;
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
    public function save(\Tuleap\Tracker\Tracker $tracker, SemanticTimeframeDao $dao): bool
    {
        return true;
    }

    #[\Override]
    public function isFieldUsed(\Tuleap\Tracker\FormElement\Field\TrackerField $field): bool
    {
        return true;
    }

    #[\Override]
    public function isDefined(): bool
    {
        return true;
    }

    #[\Override]
    public function userCanReadTimeframeFields(\PFUser $user): bool
    {
        return (! $this->start || $this->start->userCanRead($user)) &&
               (! $this->end || $this->end->userCanRead($user)) &&
               (! $this->duration || $this->duration->userCanRead($user));
    }

    #[\Override]
    public function isAllSetToZero(\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): bool
    {
        return $this->date_period->getDuration() === 0 &&
               $this->date_period->getStartDate() === 0 &&
               $this->date_period->getEndDate() === 0;
    }

    #[\Override]
    public function isTimeDisplayedForEvent(): bool
    {
        return false;
    }
}
