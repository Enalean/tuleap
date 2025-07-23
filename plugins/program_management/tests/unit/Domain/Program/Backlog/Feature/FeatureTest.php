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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Tests\Builder\FeatureHasUserStoriesVerifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureCrossReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureURIStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsOpenStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FEATURE_ID         = 243;
    private const TRACKER_ID         = 38;
    private const URI                = '/plugins/tracker/?aid=' . self::FEATURE_ID;
    private const TRACKER_SHORT_NAME = 'feature';
    private const BACKGROUND_COLOR   = 'fiesta-red';
    private const TITLE              = 'Satiable Ovovitellin';
    private RetrieveFeatureTitleStub $title_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->title_retriever = RetrieveFeatureTitleStub::withTitle(self::TITLE);
    }

    private function getFeature(): Feature
    {
        $feature_identifier = FeatureIdentifierBuilder::withId(self::FEATURE_ID);

        return Feature::fromFeatureIdentifier(
            $this->title_retriever,
            new RetrieveFeatureURIStub(),
            RetrieveFeatureCrossReferenceStub::withShortname(self::TRACKER_SHORT_NAME),
            VerifyFeatureIsOpenStub::withOpen(),
            VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
            FeatureHasUserStoriesVerifierBuilder::buildWithUserStories(),
            RetrieveBackgroundColorStub::withColor(self::BACKGROUND_COLOR),
            RetrieveTrackerOfFeatureStub::withId(self::TRACKER_ID),
            $feature_identifier,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsFromFeatureIdentifier(): void
    {
        $feature = $this->getFeature();
        self::assertSame(self::FEATURE_ID, $feature->feature_identifier->getId());
        self::assertSame(self::TITLE, $feature->title);
        self::assertSame(self::URI, $feature->uri);
        self::assertSame('feature #243', $feature->cross_reference);
        self::assertTrue($feature->is_open);
        self::assertFalse($feature->is_linked_to_at_least_one_planned_user_story);
        self::assertTrue($feature->has_at_least_one_story);
        self::assertSame(self::BACKGROUND_COLOR, $feature->background_color->getBackgroundColorName());
        self::assertSame(self::TRACKER_ID, $feature->feature_tracker_identifier->getId());
    }

    public function testItBuildsFeatureWithTitleNotVisible(): void
    {
        $this->title_retriever = RetrieveFeatureTitleStub::withNotVisibleTitle();
        $feature               = $this->getFeature();
        self::assertNull($feature->title);
    }
}
