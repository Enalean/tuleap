<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTrackerOfArtifact;

/**
 * @psalm-immutable
 */
final class PlannableFeatureIdentifier implements ArtifactIdentifier
{
    private function __construct(private FeatureIdentifier $feature_identifier)
    {
    }

    /**
     * @throws FeatureIsNotPlannableException
     */
    public static function build(
        VerifyIsPlannable $verify_is_plannable,
        RetrieveTrackerOfArtifact $retrieve_tracker,
        FeatureIdentifier $feature_identifier,
    ): self {
        $tracker                      = $retrieve_tracker->getTrackerOfArtifact($feature_identifier);
        $feature_tracker_is_plannable = $verify_is_plannable->isPlannable($tracker->getId());

        if (! $feature_tracker_is_plannable) {
            throw new FeatureIsNotPlannableException($tracker->getId());
        }

        return new self($feature_identifier);
    }

    public function getId(): int
    {
        return $this->feature_identifier->getId();
    }
}
