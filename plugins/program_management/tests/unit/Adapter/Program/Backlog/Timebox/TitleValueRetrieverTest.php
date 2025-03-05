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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox;

use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\UserStoryIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TitleValueRetrieverTest extends TestCase
{
    private const ARTIFACT_ID = 1;
    private const TITLE       = 'Unawful paramine';

    private function getRetriever(): TitleValueRetriever
    {
        $artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
            ->withTitle(self::TITLE)
            ->build();

        return new TitleValueRetriever(RetrieveFullArtifactStub::withArtifact($artifact));
    }

    public function testItReturnsTitleOfTimebox(): void
    {
        $artifact_identifier = TimeboxIdentifierStub::withId(self::ARTIFACT_ID);
        self::assertSame(self::TITLE, $this->getRetriever()->getTitle($artifact_identifier));
    }

    public function testItReturnsTitleOfUserStory(): void
    {
        $user_story_identifier = UserStoryIdentifierBuilder::withId(self::ARTIFACT_ID);
        self::assertSame(self::TITLE, $this->getRetriever()->getUserStoryTitle($user_story_identifier));
    }

    public function testItReturnsTitleOfFeature(): void
    {
        $feature_identifier = FeatureIdentifierBuilder::withId(self::ARTIFACT_ID);
        self::assertSame(self::TITLE, $this->getRetriever()->getFeatureTitle($feature_identifier));
    }
}
