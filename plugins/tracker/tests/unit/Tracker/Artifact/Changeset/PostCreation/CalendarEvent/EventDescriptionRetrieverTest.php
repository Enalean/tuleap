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

use ColinODell\PsrTestLogger\TestLogger;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\ForgeConfigSandbox;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class EventDescriptionRetrieverTest extends TestCase
{
    use ForgeConfigSandbox;

    private const USER_CANNOT_READ = false;

    private readonly Tracker_Artifact_Changeset $changeset;
    private readonly PFUser $recipient;
    private \Tracker_Semantic_Description|MockObject $semantic_description;

    protected function setUp(): void
    {
        $this->changeset = ChangesetTestBuilder::aChangeset("1001")->build();
        $this->recipient = UserTestBuilder::buildWithDefaults();

        $this->semantic_description = $this->createMock(\Tracker_Semantic_Description::class);
        \Tracker_Semantic_Description::setInstance($this->semantic_description, $this->changeset->getTracker());

        \ForgeConfig::set('sys_default_domain', 'example.com');
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Description::clearInstances();
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function testDescriptionContainsOnlyLinkToArtifactWhenTrackerDoesNotHaveDescriptionSemantic(bool $should_check_permissions): void
    {
        $this->semantic_description->method('getField')->willReturn(null);

        $logger    = new TestLogger();
        $retriever = new EventDescriptionRetriever();

        $result = $retriever->retrieveEventDescription(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $logger,
            $should_check_permissions,
        );
        self::assertTrue(Result::isOk($result));
        assert($result->value instanceof CalendarEventData);
        self::assertSame(
            <<<EOS
            https://example.com/plugins/tracker/?aid=171
            EOS,
            $result->value->description
        );
        self::assertTrue($logger->hasDebug('No semantic description for this tracker'));
    }

    public function testDescriptionContainsOnlyLinkToArtifactWhenDescriptionIsNotReadable(): void
    {
        $this->setDescriptionValue('Ho ho ho, Merry Christmas!', self::USER_CANNOT_READ);

        $logger    = new TestLogger();
        $retriever = new EventDescriptionRetriever();

        $should_check_permissions = true;

        $result = $retriever->retrieveEventDescription(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $logger,
            $should_check_permissions,
        );
        self::assertTrue(Result::isOk($result));
        assert($result->value instanceof CalendarEventData);
        self::assertSame(
            <<<EOS
            https://example.com/plugins/tracker/?aid=171
            EOS,
            $result->value->description
        );
        self::assertTrue($logger->hasDebug('User cannot read description'));
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     *           [true, true]
     */
    public function testDescriptionContainsOnlyLinkToArtifactWhenNoValueForDescription(bool $user_can_read, bool $should_check_permissions): void
    {
        $description_field = $this->getDescriptionField($user_can_read);
        $this->semantic_description->method('getField')->willReturn($description_field);

        $this->changeset->setFieldValue($description_field, null);

        $logger    = new TestLogger();
        $retriever = new EventDescriptionRetriever();

        $result = $retriever->retrieveEventDescription(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $logger,
            $should_check_permissions,
        );
        self::assertTrue(Result::isOk($result));
        assert($result->value instanceof CalendarEventData);
        self::assertEquals(
            'Christmas Party',
            $result->value->summary,
        );
        self::assertSame(
            <<<EOS
            https://example.com/plugins/tracker/?aid=171
            EOS,
            $result->value->description
        );
        self::assertTrue($logger->hasDebug('No value for description'));
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     *           [true, true]
     */
    public function testDescriptionContainsLinkToArtifactPlusArtifactDescription(bool $user_can_read, bool $should_check_permissions): void
    {
        $this->setDescriptionValue('Ho ho ho, Merry Christmas!', $user_can_read);

        $logger    = new TestLogger();
        $retriever = new EventDescriptionRetriever();

        $result = $retriever->retrieveEventDescription(
            CalendarEventData::fromSummary('Christmas Party'),
            $this->changeset,
            $this->recipient,
            $logger,
            $should_check_permissions,
        );
        self::assertTrue(Result::isOk($result));
        assert($result->value instanceof CalendarEventData);
        self::assertEquals(
            'Christmas Party',
            $result->value->summary,
        );
        self::assertSame(
            <<<EOS
            https://example.com/plugins/tracker/?aid=171
            Ho ho ho, Merry Christmas!
            EOS,
            $result->value->description
        );
        self::assertFalse($logger->hasDebugRecords());
    }

    private function setDescriptionValue(string $description, bool $user_can_read): void
    {
        $description_field = $this->getDescriptionField($user_can_read);
        $this->semantic_description->method('getField')->willReturn($description_field);

        $description_field_value = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);
        $description_field_value->method('getValue')->willReturn($description);

        $this->changeset->setFieldValue($description_field, $description_field_value);
    }

    private function getDescriptionField(bool $user_can_read): \Tracker_FormElement_Field_Text
    {
        $description_field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $description_field->method('userCanRead')->willReturn($user_can_read);
        $description_field->method('getId')->willReturn(1);

        return $description_field;
    }
}
