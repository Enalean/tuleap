<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\REST\v1;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Feature;
use Tuleap\ProgramManagement\Tests\Builder\FeatureHasUserStoriesVerifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureCrossReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureURIStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsOpenStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FEATURE_ID        = 673;
    private const TRACKER_ID        = 80;
    private const TRACKER_SHORTNAME = 'feature';
    private const URI               = '/plugins/tracker/?aid=' . self::FEATURE_ID;
    private const BACKGROUND_COLOR  = 'chrome-silver';
    private const TITLE             = 'Upscale Recti';
    private const PROJECT_ID        = 296;

    private function getRepresentation(): FeatureRepresentation
    {
        $program_project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $feature_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withProject($program_project)
            ->build();

        return FeatureRepresentation::fromFeature(
            RetrieveFullTrackerStub::withTracker($feature_tracker),
            Feature::fromFeatureIdentifier(
                RetrieveFeatureTitleStub::withTitle(self::TITLE),
                new RetrieveFeatureURIStub(),
                RetrieveFeatureCrossReferenceStub::withShortname(self::TRACKER_SHORTNAME),
                VerifyFeatureIsOpenStub::withClosed(),
                VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
                FeatureHasUserStoriesVerifierBuilder::buildWithUserStories(),
                RetrieveBackgroundColorStub::withColor(self::BACKGROUND_COLOR),
                RetrieveTrackerOfFeatureStub::withId(self::TRACKER_ID),
                FeatureIdentifierBuilder::withId(self::FEATURE_ID),
                UserIdentifierStub::buildGenericUser()
            )
        );
    }

    public function testItBuildsFromFeature(): void
    {
        $representation = $this->getRepresentation();
        self::assertSame(self::FEATURE_ID, $representation->id);
        self::assertSame(self::TITLE, $representation->title);
        self::assertSame(self::URI, $representation->uri);
        self::assertSame('feature #' . self::FEATURE_ID, $representation->xref);
        self::assertFalse($representation->has_user_story_planned);
        self::assertTrue($representation->has_user_story_linked);
        self::assertSame(self::BACKGROUND_COLOR, $representation->background_color);
        self::assertSame(self::TRACKER_ID, $representation->tracker->id);
        self::assertSame(self::PROJECT_ID, $representation->tracker->project->id);
        self::assertFalse($representation->is_open);
    }
}
