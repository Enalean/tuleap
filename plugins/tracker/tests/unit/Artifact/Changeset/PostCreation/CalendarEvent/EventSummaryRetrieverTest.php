<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent;

use PFUser;
use PHPUnit\Framework\Attributes\TestWith;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EventSummaryRetrieverTest extends TestCase
{
    private const USER_CANNOT_READ = false;

    private readonly Tracker_Artifact_Changeset $changeset;
    private readonly Tracker $tracker;
    private readonly PFUser $recipient;
    private RetrieveSemanticTitleField $title_field_retriever;

    protected function setUp(): void
    {
        $this->tracker   = TrackerTestBuilder::aTracker()->withId(852)->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(1001)
            ->ofArtifact(ArtifactTestBuilder::anArtifact(963)->inTracker($this->tracker)->build())
            ->build();
        $this->recipient = UserTestBuilder::buildWithDefaults();

        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build();
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testErrorWhenTrackerDoesNotHaveTitleSemantic(bool $should_check_permissions): void
    {
        $retriever = new EventSummaryRetriever($this->title_field_retriever);

        $result = $retriever->retrieveEventSummary($this->changeset, $this->recipient, $should_check_permissions);
        self::assertTrue(Result::isErr($result));
        self::assertSame(
            'The tracker does not have title semantic, we cannot build calendar event',
            (string) $result->error,
        );
    }

    public function testErrorWhenTitleIsNotReadable(): void
    {
        $title_field = $this->getTitleField(self::USER_CANNOT_READ);
        $this->setTitleValue('Christmas Party', $title_field);

        $retriever = new EventSummaryRetriever($this->title_field_retriever->withTitleField($title_field));

        $should_check_permissions = true;

        $result = $retriever->retrieveEventSummary($this->changeset, $this->recipient, $should_check_permissions);
        self::assertTrue(Result::isErr($result));
        self::assertSame(
            'The user #110 (john@example.com) cannot read the title, we cannot build calendar event',
            (string) $result->error,
        );
    }

    #[TestWith([false, false, ''])]
    #[TestWith([true, false, ''])]
    #[TestWith([true, true, ''])]
    #[TestWith([true, true, ' '])]
    public function testErrorWhenTitleIsEmpty(bool $user_can_read, bool $should_check_permissions, string $empty_text): void
    {
        $title_field = $this->getTitleField($user_can_read);
        $this->setTitleValue($empty_text, $title_field);

        $retriever = new EventSummaryRetriever($this->title_field_retriever->withTitleField($title_field));

        $result = $retriever->retrieveEventSummary($this->changeset, $this->recipient, $should_check_permissions);
        self::assertTrue(Result::isErr($result));
        self::assertSame(
            'Title is empty, we cannot build calendar event',
            (string) $result->error,
        );
    }

    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([true, true])]
    public function testSummaryIsTitleWhenEverythingIsFine(bool $user_can_read, bool $should_check_permissions): void
    {
        $title_field = $this->getTitleField($user_can_read);
        $this->setTitleValue('Christmas Party', $title_field);

        $retriever = new EventSummaryRetriever($this->title_field_retriever->withTitleField($title_field));

        $result = $retriever->retrieveEventSummary($this->changeset, $this->recipient, $should_check_permissions);
        self::assertTrue(Result::isOk($result));
        assert($result->value instanceof CalendarEventData);
        self::assertSame(
            'Christmas Party',
            $result->value->summary,
        );
    }

    private function setTitleValue(string $title, StringField $title_field): void
    {
        $title_field_value = new Tracker_Artifact_ChangesetValue_Text(1, $this->changeset, $title_field, false, $title, 'text');
        $this->changeset->setFieldValue($title_field, $title_field_value);
    }

    private function getTitleField(bool $user_can_read): StringField
    {
        return StringFieldBuilder::aStringField(1)
            ->inTracker($this->tracker)
            ->withReadPermission($this->recipient, $user_can_read)
            ->build();
    }
}
