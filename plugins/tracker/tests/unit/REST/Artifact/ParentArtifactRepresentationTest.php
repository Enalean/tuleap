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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\Color\ItemColor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ParentArtifactRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID               = 119;
    private const PROJECT_ICON             = '🐨';
    private const PROJECT_LABEL            = 'Casimiroa';
    private const TRACKER_ID               = 25;
    private const TRACKER_LABEL            = 'Hortation';
    private const TRACKER_SHORTNAME        = 'hortation';
    private const TRACKER_COLOR            = 'clockwork-orange';
    private const ARTIFACT_ID              = 251;
    private const ARTIFACT_TITLE           = 'irisroot';
    private const STATUS_VALUE             = 'On going';
    private const STATUS_COLOR             = 'flamingo-pink';
    private const ARTIFACT_CROSS_REFERENCE = self::TRACKER_SHORTNAME . ' #' . self::ARTIFACT_ID;

    public function testItBuildsFromArtifact(): void
    {
        $project  = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withPublicName(self::PROJECT_LABEL)
            ->withIcon(self::PROJECT_ICON)
            ->build();
        $tracker  = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withName(self::TRACKER_LABEL)
            ->withShortName(self::TRACKER_SHORTNAME)
            ->withColor(ItemColor::fromName(self::TRACKER_COLOR))
            ->withProject($project)
            ->build();
        $artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
            ->inTracker($tracker)
            ->withTitle(self::ARTIFACT_TITLE)
            ->build();

        $representation = ParentArtifactRepresentation::build($artifact, StatusValueRepresentation::buildFromValues(self::STATUS_VALUE, self::STATUS_COLOR));

        self::assertSame(self::ARTIFACT_ID, $representation->id);
        self::assertSame(self::ARTIFACT_TITLE, $representation->title);
        self::assertSame(self::ARTIFACT_CROSS_REFERENCE, $representation->xref);
        self::assertSame('artifacts/' . self::ARTIFACT_ID, $representation->uri);

        $tracker_representation = $representation->tracker;
        self::assertSame(self::TRACKER_ID, $tracker_representation->id);
        self::assertSame(self::TRACKER_LABEL, $tracker_representation->label);
        self::assertSame(self::TRACKER_COLOR, $tracker_representation->color_name);
        self::assertSame('trackers/' . self::TRACKER_ID, $tracker_representation->uri);

        $project_representation = $tracker_representation->project;
        self::assertSame(self::PROJECT_ID, $project_representation->id);
        self::assertSame(self::PROJECT_LABEL, $project_representation->label);
        self::assertSame(self::PROJECT_ICON, $project_representation->icon);
        self::assertSame('projects/' . self::PROJECT_ID, $project_representation->uri);

        $status_representation = $representation->full_status;
        self::assertSame(self::STATUS_VALUE, $status_representation->value);
        self::assertSame(self::STATUS_COLOR, $status_representation->color);
    }
}
