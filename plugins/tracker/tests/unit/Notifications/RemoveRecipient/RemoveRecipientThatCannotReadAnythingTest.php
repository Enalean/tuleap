<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications\RemoveRecipient;

use Psr\Log\NullLogger;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Notifications\Recipient;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RemoveRecipientThatCannotReadAnythingTest extends TestCase
{
    public function testKeepARecipientWhoCanSeeANonEmptyComment(): void
    {
        $strategy = new RemoveRecipientThatCannotReadAnything();

        $recipient = Recipient::fromUser(UserTestBuilder::anActiveUser()->build());

        $artifact  = ArtifactTestBuilder::anArtifact(101)->userCanView($recipient->user)->build();
        $changeset = ChangesetTestBuilder::aChangeset(2000)->ofArtifact($artifact)->withTextComment('my comment')->build();
        $changeset->setNoFieldValue(TextFieldBuilder::aTextField(120)->build());

        $expected_recipients = ['recipient' => $recipient];

        $remaining_recipients = $strategy->removeRecipient(
            new NullLogger(),
            $changeset,
            $expected_recipients,
            true
        );

        self::assertEquals($expected_recipients, $remaining_recipients);
    }

    public function testKeepARecipientWhoCanSeeAFieldChange(): void
    {
        $strategy = new RemoveRecipientThatCannotReadAnything();

        $recipient = Recipient::fromUser(UserTestBuilder::anActiveUser()->build());

        $artifact  = ArtifactTestBuilder::anArtifact(101)->userCanView($recipient->user)->build();
        $changeset = ChangesetTestBuilder::aChangeset(2000)->ofArtifact($artifact)->withTextComment('')->build();
        $field     = TextFieldBuilder::aTextField(120)->withReadPermission($recipient->user, true)->build();
        $changeset->setFieldValue(
            $field,
            new Tracker_Artifact_ChangesetValue_Text(
                777,
                $changeset,
                $field,
                true,
                'some text',
                Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
            )
        );

        $expected_recipients = ['recipient' => $recipient];

        $remaining_recipients = $strategy->removeRecipient(
            new NullLogger(),
            $changeset,
            $expected_recipients,
            true
        );

        self::assertEquals($expected_recipients, $remaining_recipients);
    }

    public function testRemoveRecipientThatCannotViewTheArtifact(): void
    {
        $strategy = new RemoveRecipientThatCannotReadAnything();

        $recipient = Recipient::fromUser(UserTestBuilder::anActiveUser()->build());

        $artifact = ArtifactTestBuilder::anArtifact(101)->userCannotView($recipient->user)->build();

        $remaining_recipients = $strategy->removeRecipient(
            new NullLogger(),
            ChangesetTestBuilder::aChangeset(2000)->ofArtifact($artifact)->withTextComment('my comment')->build(),
            ['recipient' => $recipient],
            true
        );

        $this->assertEmpty($remaining_recipients);
    }

    public function testRemoveRecipientThatCannotSeeAnyChangesInTheArtifact(): void
    {
        $strategy = new RemoveRecipientThatCannotReadAnything();

        $recipient = Recipient::fromUser(UserTestBuilder::anActiveUser()->build());

        $artifact  = ArtifactTestBuilder::anArtifact(101)->userCanView($recipient->user)->build();
        $changeset = ChangesetTestBuilder::aChangeset(2000)->ofArtifact($artifact)->withTextComment('')->build();
        $field     = TextFieldBuilder::aTextField(120)->withReadPermission($recipient->user, false)->build();
        $changeset->setFieldValue(
            $field,
            new Tracker_Artifact_ChangesetValue_Text(
                777,
                $changeset,
                $field,
                true,
                'some text',
                Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
            )
        );

        $expected_recipients = ['recipient' => $recipient];

        $remaining_recipients = $strategy->removeRecipient(
            new NullLogger(),
            $changeset,
            $expected_recipients,
            true
        );

        self::assertEmpty($remaining_recipients);
    }
}
