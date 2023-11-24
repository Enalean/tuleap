<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Tracker\Artifact\Changeset\PostCreation\CalendarEvent;

use Psr\Log\NullLogger;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\ForgeConfigSandbox;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\CalendarEventData;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\EventOrganizerRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class EventOrganizerRetrieverTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testItCanGetOrganizer(): void
    {
        $project   = ProjectTestBuilder::aProject()->withPublicName('Project 1')->build();
        $tracker   = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset('1')->ofArtifact($artifact)->build();

        $calendar_event_data = CalendarEventData::fromSummary('');
        $user                = UserTestBuilder::anActiveUser()->build();

        \ForgeConfig::set(ConfigurationVariables::NOREPLY, '"Noreply" <noreply@example.com>');
        \ForgeConfig::set(ConfigurationVariables::NAME, 'Platform');

        $retriever = new EventOrganizerRetriever();

        $result = $retriever->retrieveEventOrganizer($calendar_event_data, $changeset, $user, new NullLogger(), true);

        self::assertTrue(Result::isOk($result));
        self::assertNotNull($result->value->organizer);
        self::assertEquals('noreply@example.com', $result->value->organizer->email);
        self::assertEquals('Platform - Project 1', $result->value->organizer->name);
    }

    public function testItCannotGetOrganizer(): void
    {
        $project   = ProjectTestBuilder::aProject()->withPublicName('Project 1')->build();
        $tracker   = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset('1')->ofArtifact($artifact)->build();

        $calendar_event_data = CalendarEventData::fromSummary('');
        $user                = UserTestBuilder::anActiveUser()->build();

        \ForgeConfig::set(ConfigurationVariables::NOREPLY, '');
        \ForgeConfig::set(ConfigurationVariables::NAME, 'Platform');

        $retriever = new EventOrganizerRetriever();

        $result = $retriever->retrieveEventOrganizer($calendar_event_data, $changeset, $user, new NullLogger(), true);

        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value->organizer);
    }
}
