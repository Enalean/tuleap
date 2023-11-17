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
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_Semantic_Title;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class EventSummaryRetrieverTest extends TestCase
{
    private const USER_CANNOT_READ = false;

    private readonly Tracker_Artifact_Changeset $changeset;
    private readonly PFUser $recipient;
    private Tracker_Semantic_Title|MockObject $semantic_title;

    protected function setUp(): void
    {
        $this->changeset = ChangesetTestBuilder::aChangeset("1001")->build();
        $this->recipient = UserTestBuilder::buildWithDefaults();

        $this->semantic_title = $this->createMock(Tracker_Semantic_Title::class);
        Tracker_Semantic_Title::setInstance($this->semantic_title, $this->changeset->getTracker());
    }

    protected function tearDown(): void
    {
        Tracker_Semantic_Title::clearInstances();
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function testErrorWhenTrackerDoesNotHaveTitleSemantic(bool $should_check_permissions): void
    {
        $this->semantic_title->method('getField')->willReturn(null);

        $retriever = new EventSummaryRetriever();

        $result = $retriever->getEventSummary($this->changeset, $this->recipient, $should_check_permissions);
        self::assertTrue(Result::isErr($result));
        self::assertEquals(
            'The tracker does not have title semantic, we cannot build calendar event',
            (string) $result->error,
        );
    }

    public function testErrorWhenTitleIsNotReadable(): void
    {
        $this->setTitleValue('Christmas Party', self::USER_CANNOT_READ);

        $retriever = new EventSummaryRetriever();

        $should_check_permissions = true;

        $result = $retriever->getEventSummary($this->changeset, $this->recipient, $should_check_permissions);
        self::assertTrue(Result::isErr($result));
        self::assertEquals(
            'The user #110 (john@example.com) cannot read the title, we cannot build calendar event',
            (string) $result->error,
        );
    }

    /**
     * @testWith [false, false, ""]
     *           [true, false, ""]
     *           [true, true, ""]
     *           [true, true, " "]
     */
    public function testErrorWhenTitleIsEmpty(bool $user_can_read, bool $should_check_permissions, string $empty_text): void
    {
        $this->setTitleValue($empty_text, $user_can_read);

        $retriever = new EventSummaryRetriever();

        $result = $retriever->getEventSummary($this->changeset, $this->recipient, $should_check_permissions);
        self::assertTrue(Result::isErr($result));
        self::assertEquals(
            'Title is empty, we cannot build calendar event',
            (string) $result->error,
        );
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     *           [true, true]
     */
    public function testSummaryIsTitleWhenEverythingIsFine(bool $user_can_read, bool $should_check_permissions): void
    {
        $this->setTitleValue('Christmas Party', $user_can_read);

        $retriever = new EventSummaryRetriever();

        $result = $retriever->getEventSummary($this->changeset, $this->recipient, $should_check_permissions);
        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            'Christmas Party',
            (string) $result->value,
        );
    }

    private function setTitleValue(string $title, bool $user_can_read): void
    {
        $title_field = $this->getTitleField($user_can_read);
        $this->semantic_title->method('getField')->willReturn($title_field);

        $title_field_value = new Tracker_Artifact_ChangesetValue_Text(1, $this->changeset, $title_field, false, $title, 'text');
        $this->changeset->setFieldValue($title_field, $title_field_value);
    }

    private function getTitleField(bool $user_can_read): \Tracker_FormElement_Field_Text
    {
        $title_field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $title_field->method('userCanRead')->willReturn($user_can_read);
        $title_field->method('getId')->willReturn(1);

        return $title_field;
    }
}
