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

namespace Tuleap\Tracker\Creation;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeImpliedFromAnotherTracker;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Semantic\TimeframeConfigInvalid;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerCreationNotificationsSettingsFromXmlBuilderTest extends TestCase
{
    public function testNoCalendarEventIfAttributeIsNotPresent(): void
    {
        $xml_input = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T101">
            </tracker>'
        );

        $tracker = TrackerTestBuilder::aTracker()->build();

        $result = (new TrackerCreationNotificationsSettingsFromXmlBuilder())
            ->getCreationNotificationsSettings($xml_input->attributes(), $tracker);

        self::assertTrue(Result::isOk($result));
        assert($result->value instanceof TrackerCreationNotificationsSettings);
        self::assertFalse($result->value->should_send_event_in_notification);
    }

    public function testNoCalendarEventIfAttributeIsFalse(): void
    {
        $xml_input = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T101" should_send_event_in_notification="0">
            </tracker>'
        );

        $tracker = TrackerTestBuilder::aTracker()->build();

        $result = (new TrackerCreationNotificationsSettingsFromXmlBuilder())
            ->getCreationNotificationsSettings($xml_input->attributes(), $tracker);

        self::assertTrue(Result::isOk($result));
        assert($result->value instanceof TrackerCreationNotificationsSettings);
        self::assertFalse($result->value->should_send_event_in_notification);
    }

    public function testErrorIfTitleSemanticIsNotSet(): void
    {
        $xml_input = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T101" should_send_event_in_notification="1">
            </tracker>'
        );

        $tracker = TrackerTestBuilder::aTracker()->build();

        $result = (new TrackerCreationNotificationsSettingsFromXmlBuilder())
            ->getCreationNotificationsSettings($xml_input->attributes(), $tracker);

        self::assertTrue(Result::isErr($result));
        self::assertSame(
            'Cannot activate calendar event for tracker without title semantic',
            $result->error,
        );
    }

    public function testErrorIfTitleSemanticIsSetWithoutField(): void
    {
        $xml_input = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T101" should_send_event_in_notification="1">
            </tracker>'
        );

        $tracker              = TrackerTestBuilder::aTracker()->build();
        $tracker->semantics[] = new \Tracker_Semantic_Title($tracker, null);

        $result = (new TrackerCreationNotificationsSettingsFromXmlBuilder())
            ->getCreationNotificationsSettings($xml_input->attributes(), $tracker);

        self::assertTrue(Result::isErr($result));
        self::assertSame(
            'Cannot activate calendar event for tracker without title semantic',
            $result->error,
        );
    }

    public function testErrorIfTimeframeSemanticIsNotSet(): void
    {
        $xml_input = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T101" should_send_event_in_notification="1">
            </tracker>'
        );

        $tracker              = TrackerTestBuilder::aTracker()->build();
        $tracker->semantics[] = new \Tracker_Semantic_Title(
            $tracker,
            TextFieldBuilder::aTextField(1)->build(),
        );

        $result = (new TrackerCreationNotificationsSettingsFromXmlBuilder())
            ->getCreationNotificationsSettings($xml_input->attributes(), $tracker);

        self::assertTrue(Result::isErr($result));
        self::assertSame(
            'Cannot activate calendar event for tracker without timeframe semantic',
            $result->error,
        );
    }

    public function testErrorIfTimeframeSemanticIsInvalid(): void
    {
        $xml_input = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T101" should_send_event_in_notification="1">
            </tracker>'
        );

        $tracker              = TrackerTestBuilder::aTracker()->build();
        $tracker->semantics[] = new \Tracker_Semantic_Title(
            $tracker,
            TextFieldBuilder::aTextField(1)->build(),
        );
        $tracker->semantics[] = new SemanticTimeframe(
            $tracker,
            new TimeframeConfigInvalid(),
        );

        $result = (new TrackerCreationNotificationsSettingsFromXmlBuilder())
            ->getCreationNotificationsSettings($xml_input->attributes(), $tracker);

        self::assertTrue(Result::isErr($result));
        self::assertSame(
            'Cannot activate calendar event for tracker without timeframe semantic',
            $result->error,
        );
    }

    public function testErrorIfTimeframeSemanticIsInherited(): void
    {
        $xml_input = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T101" should_send_event_in_notification="1">
            </tracker>'
        );

        $tracker              = TrackerTestBuilder::aTracker()->build();
        $tracker->semantics[] = new \Tracker_Semantic_Title(
            $tracker,
            TextFieldBuilder::aTextField(1)->build(),
        );
        $tracker->semantics[] = new SemanticTimeframe(
            $tracker,
            $this->createMock(TimeframeImpliedFromAnotherTracker::class),
        );

        $result = (new TrackerCreationNotificationsSettingsFromXmlBuilder())
            ->getCreationNotificationsSettings($xml_input->attributes(), $tracker);

        self::assertTrue(Result::isErr($result));
        self::assertSame(
            'Cannot activate calendar event for tracker with timeframe semantic inherited from another tracker',
            $result->error,
        );
    }

    public function testCalendarEventIfAttributeIsTrue(): void
    {
        $xml_input = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T101" should_send_event_in_notification="1">
            </tracker>'
        );

        $tracker              = TrackerTestBuilder::aTracker()->build();
        $tracker->semantics[] = new \Tracker_Semantic_Title(
            $tracker,
            TextFieldBuilder::aTextField(1)->build(),
        );
        $tracker->semantics[] = new SemanticTimeframe(
            $tracker,
            new TimeframeWithEndDate(
                DateFieldBuilder::aDateField(2)->build(),
                DateFieldBuilder::aDateField(3)->build(),
            )
        );

        $result = (new TrackerCreationNotificationsSettingsFromXmlBuilder())
            ->getCreationNotificationsSettings($xml_input->attributes(), $tracker);

        self::assertTrue(Result::isOk($result));
        assert($result->value instanceof TrackerCreationNotificationsSettings);
        self::assertTrue($result->value->should_send_event_in_notification);
    }
}
