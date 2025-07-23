<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldStub;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ListFields\RetrieveUsedListFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactMappedFieldValueRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID = 188;
    private \Cardwall_FieldProviders_SemanticStatusFieldRetriever&Stub $status_provider;
    private RetrieveUsedListFieldStub $field_retriever;
    private Artifact $user_story_artifact;
    private \PFUser $user;
    private \Tuleap\Tracker\Tracker $user_story_tracker;

    #[\Override]
    protected function setUp(): void
    {
        $this->user_story_tracker  = TrackerTestBuilder::aTracker()->withId(92)->build();
        $this->user_story_artifact = ArtifactTestBuilder::anArtifact(775)
            ->inTracker($this->user_story_tracker)->build();

        $this->user            = UserTestBuilder::aUser()->build();
        $this->field_retriever = RetrieveUsedListFieldStub::withField(
            ListFieldBuilder::aListField(self::FIELD_ID)->withReadPermission($this->user, true)->build(),
        );
        $this->status_provider = $this->createStub(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
    }

    /** @return Option<\Tracker_FormElement_Field_List_BindValue> */
    private function getValue(): Option
    {
        $release_tracker   = TrackerTestBuilder::aTracker()->withId(68)->build();
        $taskboard_tracker = new TaskboardTracker($release_tracker, $this->user_story_tracker);

        $retriever = new ArtifactMappedFieldValueRetriever(
            new MappedFieldRetriever(
                $this->status_provider,
                new FreestyleMappedFieldRetriever(
                    SearchMappedFieldStub::withMappedField($taskboard_tracker, self::FIELD_ID),
                    $this->field_retriever
                )
            )
        );
        return $retriever->getFirstValueAtLastChangeset($release_tracker, $this->user_story_artifact, $this->user);
    }

    public function testItReturnsNothingWhenNoMappedField(): void
    {
        $this->field_retriever = RetrieveUsedListFieldStub::withNoField();
        $this->status_provider->method('getField')->willReturn(null);

        self::assertTrue($this->getValue()->isNothing());
    }

    public function testItReturnsNothingWhenUserCannotReadMappedField(): void
    {
        $this->field_retriever = RetrieveUsedListFieldStub::withField(
            ListFieldBuilder::aListField(self::FIELD_ID)->withReadPermission($this->user, false)->build(),
        );

        self::assertTrue($this->getValue()->isNothing());
    }

    public function testItReturnsNothingWhenNoLastChangeset(): void
    {
        $this->user_story_artifact->setChangesets([]);

        self::assertTrue($this->getValue()->isNothing());
    }

    public function testItReturnsNothingWhenValueIsNotListValue(): void
    {
        $mapped_field          = ListFieldBuilder::aListField(self::FIELD_ID)->withReadPermission($this->user, true)->build();
        $this->field_retriever = RetrieveUsedListFieldStub::withField($mapped_field);

        $last_changeset            = ChangesetTestBuilder::aChangeset(677)->build();
        $this->user_story_artifact = ArtifactTestBuilder::anArtifact(78)
            ->inTracker($this->user_story_tracker)
            ->withChangesets($last_changeset)
            ->build();
        $last_changeset->setFieldValue($mapped_field, null);

        self::assertTrue($this->getValue()->isNothing());
    }

    public function testItReturnsNothingWhenValueIsEmpty(): void
    {
        $mapped_field          = ListFieldBuilder::aListField(self::FIELD_ID)->withReadPermission($this->user, true)->build();
        $this->field_retriever = RetrieveUsedListFieldStub::withField($mapped_field);

        $last_changeset            = ChangesetTestBuilder::aChangeset(677)->build();
        $this->user_story_artifact = ArtifactTestBuilder::anArtifact(78)
            ->inTracker($this->user_story_tracker)
            ->withChangesets($last_changeset)
            ->build();
        ChangesetValueListTestBuilder::aListOfValue(892, $last_changeset, $mapped_field)
            ->withValues([])
            ->build();

        self::assertTrue($this->getValue()->isNothing());
    }

    public function testItReturnsFirstValueOfMappedField(): void
    {
        $mapped_field          = ListFieldBuilder::aListField(self::FIELD_ID)->withReadPermission($this->user, true)->build();
        $list_bind             = ListStaticBindBuilder::aStaticBind($mapped_field)
            ->withStaticValues([9074 => 'On Going', 9086 => 'Blocked'])
            ->build();
        $this->field_retriever = RetrieveUsedListFieldStub::withField($mapped_field);

        $last_changeset            = ChangesetTestBuilder::aChangeset(677)->build();
        $this->user_story_artifact = ArtifactTestBuilder::anArtifact(78)
            ->inTracker($this->user_story_tracker)
            ->withChangesets($last_changeset)
            ->build();

        $first_list_value = $list_bind->getValue(9074);
        ChangesetValueListTestBuilder::aListOfValue(8608, $last_changeset, $mapped_field)
            ->withValues([$first_list_value, $list_bind->getValue(9086)])
            ->build();

        self::assertSame($first_list_value, $this->getValue()->unwrapOr(null));
    }
}
