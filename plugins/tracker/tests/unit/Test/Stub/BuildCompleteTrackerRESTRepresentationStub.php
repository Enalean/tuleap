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
    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function getTrackerRepresentationInTrackerContext(PFUser $user, Tracker $tracker): CompleteTrackerRepresentation
    {
        return $this->buildRepresentation($tracker);
    }

    public function getTrackerRepresentationInArtifactContext(PFUser $user, Artifact $artifact): CompleteTrackerRepresentation
    {
        return $this->buildRepresentation(
            TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build()
        );
    }

    private function buildRepresentation(Tracker $tracker): CompleteTrackerRepresentation
    {
        return CompleteTrackerRepresentation::build(
            $tracker,
            [],
            [],
            []
        );
    }
}
