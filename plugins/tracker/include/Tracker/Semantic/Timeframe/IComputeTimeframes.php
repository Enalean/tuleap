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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Psr\Log\LoggerInterface;
use Tuleap\Date\DatePeriodWithoutWeekEnd;

interface IComputeTimeframes
{
    public static function getName(): string;

    public function getConfigDescription(): string;

    public function getStartDateField(): ?\Tracker_FormElement_Field_Date;

    public function getEndDateField(): ?\Tracker_FormElement_Field_Date;

    public function getDurationField(): ?\Tracker_FormElement_Field_Numeric;

    public function getTrackerFromWhichTimeframeIsImplied(): ?\Tracker;

    public function buildDatePeriodWithoutWeekendForChangesetForREST(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithoutWeekEnd;

    public function buildDatePeriodWithoutWeekendForChangeset(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithoutWeekEnd;

    /**
     * @throws \Tracker_FormElement_Chart_Field_Exception
     */
    public function buildDatePeriodWithoutWeekendForChangesetChartRendering(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithoutWeekEnd;

    public function exportToXML(\SimpleXMLElement $root, array $xml_mapping): void;

    public function exportToREST(\PFUser $user): ?IRepresentSemanticTimeframe;

    public function save(\Tracker $tracker, SemanticTimeframeDao $dao): bool;

    public function isFieldUsed(\Tracker_FormElement_Field $field): bool;

    public function isDefined(): bool;

    public function userCanReadTimeframeFields(\PFUser $user): bool;

    public function isAllSetToZero(\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): bool;
}
