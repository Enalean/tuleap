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
use PHPUnit\Framework\Attributes\TestWith;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\ForgeConfigSandbox;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EventDescriptionRetrieverTest extends TestCase
{
    use ForgeConfigSandbox;

    private const bool USER_CANNOT_READ = false;
    private const int ARTIFACT_ID       = 777;

    private readonly Tracker_Artifact_Changeset $changeset;
    private readonly PFUser $recipient;
    private Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker   = TrackerTestBuilder::aTracker()
            ->withId(9)
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(1001)
            ->ofArtifact(
                ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->inTracker($this->tracker)->build(),
            )
            ->build();
        $this->recipient = UserTestBuilder::buildWithDefaults();

        \ForgeConfig::set('sys_default_domain', 'example.com');
    }


    #[TestWith([true])]
    #[TestWith([false])]
    public function testDescriptionContainsOnlyLinkToArtifactWhenTrackerDoesNotHaveDescriptionSemantic(bool $should_check_permissions): void
    {
        $logger    = new TestLogger();
        $retriever = new EventDescriptionRetriever(
            RetrieveSemanticDescriptionFieldStub::build(),
        );

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
            https://example.com/plugins/tracker/?aid=777
            EOS,
            $result->value->description
        );
        self::assertTrue($logger->hasDebug('No semantic description for this tracker'));
    }

    public function testDescriptionContainsOnlyLinkToArtifactWhenDescriptionIsNotReadable(): void
    {
        $logger    = new TestLogger();
        $retriever = new EventDescriptionRetriever(
            RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField(
                $this->buildDescriptionFieldWithValue('Ho ho ho, Merry Christmas!', self::USER_CANNOT_READ),
            ),
        );

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
            https://example.com/plugins/tracker/?aid=777
            EOS,
            $result->value->description
        );
        self::assertTrue($logger->hasDebug('User cannot read description'));
    }

    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([true, true])]
    public function testDescriptionContainsOnlyLinkToArtifactWhenNoValueForDescription(bool $user_can_read, bool $should_check_permissions): void
    {
        $logger    = new TestLogger();
        $retriever = new EventDescriptionRetriever(
            RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField(
                $this->buildDescriptionFieldWithValue(null, $user_can_read),
            ),
        );

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
            https://example.com/plugins/tracker/?aid=777
            EOS,
            $result->value->description
        );
        self::assertTrue($logger->hasDebug('No value for description'));
    }

    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([true, true])]
    public function testDescriptionContainsLinkToArtifactPlusArtifactDescription(bool $user_can_read, bool $should_check_permissions): void
    {
        $logger    = new TestLogger();
        $retriever = new EventDescriptionRetriever(
            RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField(
                $this->buildDescriptionFieldWithValue('Ho ho ho, Merry Christmas!', $user_can_read),
            ),
        );

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
            https://example.com/plugins/tracker/?aid=777
            Ho ho ho, Merry Christmas!
            EOS,
            $result->value->description
        );
        self::assertFalse($logger->hasDebugRecords());
    }

    private function buildDescriptionFieldWithValue(?string $value, bool $user_can_read): \Tuleap\Tracker\FormElement\Field\Text\TextField
    {
        $description_field = TextFieldBuilder::aTextField(1)
            ->inTracker($this->tracker)
            ->withReadPermission($this->recipient, $user_can_read)->build();

        if ($value !== null) {
            $description_field_value = ChangesetValueTextTestBuilder::aValue(
                $this->changeset->id,
                $this->changeset,
                $description_field
            )->withValue(
                $value,
                Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
            );

            $this->changeset->setFieldValue($description_field, $description_field_value->build());

            return $description_field;
        }

        $this->changeset->setFieldValue($description_field, null);

        return $description_field;
    }
}
