<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata;

use LogicException;
use Tuleap\CrossTracker\Query\Advanced\AllowedMetadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class MetadataUsageChecker implements CheckMetadataUsage
{
    public function checkMetadataIsUsedByAllTrackers(
        Metadata $metadata,
    ): void {
        match ($metadata->getName()) {
            AllowedMetadata::TITLE, AllowedMetadata::PROJECT_NAME, AllowedMetadata::TRACKER_NAME, AllowedMetadata::ID, AllowedMetadata::LAST_UPDATE_BY, AllowedMetadata::SUBMITTED_BY, AllowedMetadata::LAST_UPDATE_DATE, AllowedMetadata::SUBMITTED_ON, AllowedMetadata::ASSIGNED_TO, AllowedMetadata::STATUS, AllowedMetadata::DESCRIPTION, AllowedMetadata::PRETTY_TITLE, AllowedMetadata::LINK_TYPE => null,
            default                           => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }
}
