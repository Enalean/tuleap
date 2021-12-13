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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class FeatureHasUserStoriesVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;

    private SearchChildrenOfFeature $search_children_of_feature;

    protected function setUp(): void
    {
        $this->artifact_factory           = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withoutChildren();
    }

    private function hasAtLeastOneStory(): bool
    {
        $user    = UserIdentifierStub::buildGenericUser();
        $feature = FeatureIdentifierBuilder::withId(101);

        $verifier = new FeatureHasUserStoriesVerifier(
            $this->artifact_factory,
            RetrieveUserStub::withGenericUser(),
            $this->search_children_of_feature
        );

        return $verifier->hasStoryLinked($feature, $user);
    }

    public function testHasNotALinkedUserStoryToFeature(): void
    {
        self::assertFalse($this->hasAtLeastOneStory());
    }

    public function testHasNotALinkedUserStoryToFeatureThatUserCanSee(): void
    {
        $user_story = ['children_id' => 666];

        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withChildren([$user_story]);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with(self::isInstanceOf(\PFUser::class), 666)
            ->willReturn(null);

        self::assertFalse($this->hasAtLeastOneStory());
    }

    public function testHasALinkedUserStoryToFeature(): void
    {
        $user_story                       = ['children_id' => 236];
        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withChildren([$user_story]);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with(self::isInstanceOf(\PFUser::class), 236)
            ->willReturn(ArtifactTestBuilder::anArtifact(236)->build());

        self::assertTrue($this->hasAtLeastOneStory());
    }
}
