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

namespace Tuleap\Tracker\Semantic;

use Psr\Log\LoggerInterface;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\IRepresentSemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

class TimeframeConfigInvalid implements IComputeTimeframes
{
    private const NAME = 'timeframe-config-invalid';

    public static function getName(): string
    {
        return self::NAME;
    }

    public function getConfigDescription(): string
    {
        return dgettext('tuleap-tracker', 'This semantic has invalid configuration: ' . $this->getInvalidConfigDifferentProjectsMessage());
    }

    public function exportToXML(\SimpleXMLElement $root, array $xml_mapping): void
    {
    }

    public function exportToREST(\PFUser $user): ?IRepresentSemanticTimeframe
    {
        return null;
    }

    public function isFieldUsed(\Tracker_FormElement_Field $field): bool
    {
        return false;
    }

    public function isDefined(): bool
    {
        return false;
    }

    public function save(\Tracker $tracker, SemanticTimeframeDao $dao): bool
    {
        return false;
    }

    public function getStartDateField(): ?\Tracker_FormElement_Field_Date
    {
        return null;
    }

    public function getEndDateField(): ?\Tracker_FormElement_Field_Date
    {
        return null;
    }

    public function getDurationField(): ?\Tracker_FormElement_Field_Numeric
    {
        return null;
    }

    public function buildDatePeriodWithoutWeekendForChangesetForREST(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithoutWeekEnd
    {
        return DatePeriodWithoutWeekEnd::buildFromNothingWithErrorMessage(
            $this->getInvalidConfigDifferentProjectsMessage()
        );
    }

    public function buildDatePeriodWithoutWeekendForChangeset(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithoutWeekEnd
    {
        return DatePeriodWithoutWeekEnd::buildFromNothingWithErrorMessage(
            $this->getInvalidConfigDifferentProjectsMessage()
        );
    }

    /**
     * @throws \Tracker_FormElement_Chart_Field_Exception
     */
    public function buildDatePeriodWithoutWeekendForChangesetChartRendering(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithoutWeekEnd
    {
        throw new \Tracker_FormElement_Chart_Field_Exception(
            $this->getInvalidConfigDifferentProjectsMessage()
        );
    }

    private function getInvalidConfigDifferentProjectsMessage(): string
    {
        return dgettext('tuleap-tracker', "It is inherited from a tracker of another project, this is not allowed");
    }

    public function getTrackerFromWhichTimeframeIsImplied(): ?\Tracker
    {
        return null;
    }

    public function userCanReadTimeframeFields(\PFUser $user): bool
    {
        return false;
    }

    public function isAllSetToZero(\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): bool
    {
        return false;
    }
}
