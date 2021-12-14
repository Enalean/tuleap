<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureHasAtLeastOneUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class FeatureRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FEATURE_ID         = 49;
    private const TITLE              = 'Collatitious subdisjunctive';
    private const BACKGROUND_COLOR   = 'chrome-silver';
    private const URI                = '/plugins/tracker/?aid=' . self::FEATURE_ID;
    private const TRACKER_ID         = 36;
    private const TRACKER_SHORT_NAME = 'feature';
    private const PROGRAM_ID         = 101;
    private RetrieveFeatureTitleStub $title_retriever;

    protected function setUp(): void
    {
        $this->title_retriever = RetrieveFeatureTitleStub::withTitle(self::TITLE);
    }

    private function getRepresentation(): FeatureRepresentation
    {
        $project  = ProjectTestBuilder::aProject()
            ->withId(self::PROGRAM_ID)
            ->withPublicName('My project')
            ->build();
        $tracker  = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withProject($project)
            ->withName(self::TRACKER_SHORT_NAME)
            ->build();
        $artifact = ArtifactTestBuilder::anArtifact(self::FEATURE_ID)->inTracker($tracker)->build();

        $builder = new FeatureRepresentationBuilder(
            RetrieveFullArtifactStub::withArtifact($artifact),
            $this->title_retriever,
            RetrieveBackgroundColorStub::withColor(self::BACKGROUND_COLOR),
            VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
            VerifyFeatureHasAtLeastOneUserStoryStub::withStories()
        );

        $feature_identifier = FeatureIdentifierBuilder::withId(self::FEATURE_ID);
        return $builder->buildFeatureRepresentation($feature_identifier, UserIdentifierStub::buildGenericUser());
    }

    public function testRepresentationHasNullTitleWhenUserCantReadIt(): void
    {
        $this->title_retriever = RetrieveFeatureTitleStub::withNotVisibleTitle();
        self::assertNull($this->getRepresentation()->title);
    }

    public function testItBuildsRepresentation(): void
    {
        $representation = $this->getRepresentation();
        self::assertNotNull($representation);
        self::assertSame(self::FEATURE_ID, $representation->id);
        self::assertSame(self::TITLE, $representation->title);
        self::assertSame('feature #49', $representation->xref);
        self::assertSame(self::URI, $representation->uri);
        self::assertSame(self::BACKGROUND_COLOR, $representation->background_color);
        self::assertSame(self::TRACKER_ID, $representation->tracker->id);
        self::assertTrue($representation->has_user_story_linked);
        self::assertFalse($representation->has_user_story_planned);
    }
}
