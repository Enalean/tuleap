<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Stub;

use PFUser;
use Tracker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\v1\BuildCompleteTrackerRESTRepresentation;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class BuildCompleteTrackerRESTRepresentationStub implements BuildCompleteTrackerRESTRepresentation
{
    private function __construct(private CompleteTrackerRepresentation $representation)
    {
    }

    public function getTrackerRepresentationInTrackerContext(PFUser $user, Tracker $tracker): CompleteTrackerRepresentation
    {
        return $this->representation;
    }

    public function getTrackerRepresentationInArtifactContext(PFUser $user, Artifact $artifact): CompleteTrackerRepresentation
    {
        return $this->representation;
    }

    public static function fromTracker(Tracker $tracker): self
    {
        return new self(
            CompleteTrackerRepresentation::build(
                $tracker,
                [],
                [],
                []
            )
        );
    }

    public static function defaultRepresentation(): self
    {
        $tracker = TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build();
        return new self(
            CompleteTrackerRepresentation::build(
                $tracker,
                [],
                [],
                []
            )
        );
    }
}
