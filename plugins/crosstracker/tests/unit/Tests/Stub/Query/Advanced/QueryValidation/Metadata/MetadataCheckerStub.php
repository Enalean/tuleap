<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Tests\Stub\Query\Advanced\QueryValidation\Metadata;

use PFUser;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\CheckMetadataUsage;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\TitleIsMissingInAllTrackersException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final readonly class MetadataCheckerStub implements CheckMetadataUsage
{
    private function __construct(private bool $is_valid)
    {
    }

    public static function withValidMetadata(): self
    {
        return new self(true);
    }

    public static function withInvalidMetadata(): self
    {
        return new self(false);
    }

    public function checkMetadataIsUsedByAllTrackers(Metadata $metadata, array $trackers, PFUser $user): void
    {
        if (! $this->is_valid) {
            throw new TitleIsMissingInAllTrackersException();
        }
    }
}
