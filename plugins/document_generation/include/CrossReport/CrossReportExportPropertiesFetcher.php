<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\DocumentGeneration\CrossReport;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\RetrieveCurrentlyUsedArtifactLinkTypesInTracker;

class CrossReportExportPropertiesFetcher
{
    public function __construct(
        private RetrieveCurrentlyUsedArtifactLinkTypesInTracker $artlink_used_types_retriever,
    ) {
    }

    public function fetchExportProperties(\Tracker $tracker, \Tracker_Report $tracker_report): CrossReportExportProperties
    {
        return new CrossReportExportProperties(
            $tracker->getId(),
            $tracker->getName(),
            (int) $tracker_report->getId(),
            $this->artlink_used_types_retriever->getAllCurrentlyUsedTypePresentersByTracker($tracker),
        );
    }
}
