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
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueStringTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TitleValueRetrieverTest extends TestCase
{
    private const ARTIFACT_ID = 1;
    private const TITLE       = 'Unawful paramine';
    private \PFUser $user;
    private bool $is_title_semantic_defined = true;
    private bool $can_user_read_title_field = true;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::aUser()->build();
    }

    private function getRetriever(): TitleValueRetriever
    {
        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $tracker   = TrackerTestBuilder::aTracker()->withId(741)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
            ->inTracker($tracker)
            ->withTitle(self::TITLE)
            ->withChangesets($changeset)
            ->build();

        $title_field = StringFieldBuilder::aStringField(1230)->inTracker($artifact->getTracker())->withReadPermission($this->user, $this->can_user_read_title_field)->build();
        $changeset->setFieldValue(
            $title_field,
            ChangesetValueStringTestBuilder::aValue(1, $changeset, $title_field)->withValue(self::TITLE)->build(),
        );
        $title_field_retriever = RetrieveSemanticTitleFieldStub::build();
        if ($this->is_title_semantic_defined) {
            $title_field_retriever->withTitleField($tracker, $title_field);
        }

        return new TitleValueRetriever(
            RetrieveFullArtifactStub::withArtifact($artifact),
            RetrieveUserStub::withUser($this->user),
            $title_field_retriever,
        );
    }

    public function testItReturnsTitleOfTimebox(): void
    {
        $artifact_identifier = TimeboxIdentifierStub::withId(self::ARTIFACT_ID);
        $user_identifier     = UserIdentifierStub::withId((int) $this->user->getId());

        self::assertSame(self::TITLE, $this->getRetriever()->getTitle($artifact_identifier, $user_identifier));
    }

    public function testItReturnsTitleOfUserStory(): void
    {
        $user_story_identifier = UserStoryIdentifierBuilder::withId(self::ARTIFACT_ID);
        $user_identifier       = UserIdentifierStub::withId((int) $this->user->getId());

        self::assertSame(self::TITLE, $this->getRetriever()->getUserStoryTitle($user_story_identifier, $user_identifier));
    }

    public function testItReturnsTitleOfFeature(): void
    {
        $feature_identifier = FeatureIdentifierBuilder::withId(self::ARTIFACT_ID);
        $user_identifier    = UserIdentifierStub::withId((int) $this->user->getId());

        self::assertSame(self::TITLE, $this->getRetriever()->getFeatureTitle($feature_identifier, $user_identifier));
    }

    public function testItReturnsNullWhenUserCannotReadTitleField(): void
    {
        $timebox_identifier = TimeboxIdentifierStub::withId(self::ARTIFACT_ID);
        $user_identifier    = UserIdentifierStub::withId((int) $this->user->getId());

        $this->can_user_read_title_field = false;

        self::assertNull($this->getRetriever()->getTitle($timebox_identifier, $user_identifier));
    }

    public function testItReturnsNullWhenTitleSemanticIsNotDefined(): void
    {
        $timebox_identifier = TimeboxIdentifierStub::withId(self::ARTIFACT_ID);
        $user_identifier    = UserIdentifierStub::withId((int) $this->user->getId());

        $this->is_title_semantic_defined = false;

        self::assertNull($this->getRetriever()->getTitle($timebox_identifier, $user_identifier));
    }
}
