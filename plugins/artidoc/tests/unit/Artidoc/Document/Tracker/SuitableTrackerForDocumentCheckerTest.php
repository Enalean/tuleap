<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Tracker;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SuitableTrackerForDocumentCheckerTest extends TestCase
{
    protected function tearDown(): void
    {
        TrackerSemanticTitle::clearInstances();
    }

    public function testFaultWhenTrackerIsDeleted(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withDeletionDate(1)
            ->withId(101)
            ->build();

        $title       = StringFieldBuilder::aStringField(1001)->inTracker($tracker)->thatIsRequired()->build();
        $description = TextFieldBuilder::aTextField(1002)->inTracker($tracker)->build();
        $status      = ListFieldBuilder::aListField(1003)->inTracker($tracker)->build();

        $checker = new SuitableTrackerForDocumentChecker(
            RetrieveUsedFieldsStub::withFields($title, $description, $status),
            RetrieveSemanticDescriptionFieldStub::withTextField($description),
        );

        $result = $checker->checkTrackerIsSuitableForDocument(
            $tracker,
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(TrackerNotFoundFault::class, $result->error);
    }

    public function testFaultWhenTrackerIsNotViewableByUser(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(false)
            ->withId(101)
            ->build();

        $title       = StringFieldBuilder::aStringField(1001)->inTracker($tracker)->thatIsRequired()->build();
        $description = TextFieldBuilder::aTextField(1002)->inTracker($tracker)->build();
        $status      = ListFieldBuilder::aListField(1003)->inTracker($tracker)->build();

        $checker = new SuitableTrackerForDocumentChecker(
            RetrieveUsedFieldsStub::withFields($title, $description, $status),
            RetrieveSemanticDescriptionFieldStub::withTextField($description),
        );

        $result = $checker->checkTrackerIsSuitableForDocument(
            $tracker,
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(TrackerNotFoundFault::class, $result->error);
    }

    public function testFaultWhenTrackerDoesNotHaveSemanticTitle(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withId(101)
            ->build();

        $title       = StringFieldBuilder::aStringField(1001)->inTracker($tracker)->thatIsRequired()->build();
        $description = TextFieldBuilder::aTextField(1002)->inTracker($tracker)->build();
        $status      = ListFieldBuilder::aListField(1003)->inTracker($tracker)->build();

        $checker = new SuitableTrackerForDocumentChecker(
            RetrieveUsedFieldsStub::withFields($title, $description, $status),
            RetrieveSemanticDescriptionFieldStub::withTextField($description),
        );

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($tracker, null),
            $tracker,
        );

        $result = $checker->checkTrackerIsSuitableForDocument(
            $tracker,
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(NoSemanticTitleFault::class, $result->error);
    }

    public function testFaultWhenSemanticTitleIsNotAStringField(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withId(101)
            ->build();

        $title       = StringFieldBuilder::aStringField(1001)->inTracker($tracker)->thatIsRequired()->build();
        $description = TextFieldBuilder::aTextField(1002)->inTracker($tracker)->build();
        $status      = ListFieldBuilder::aListField(1003)->inTracker($tracker)->build();

        $checker = new SuitableTrackerForDocumentChecker(
            RetrieveUsedFieldsStub::withFields($title, $description, $status),
            RetrieveSemanticDescriptionFieldStub::withTextField($description),
        );

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($tracker, TextFieldBuilder::aTextField(1004)->build()),
            $tracker,
        );

        $result = $checker->checkTrackerIsSuitableForDocument(
            $tracker,
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(SemanticTitleIsNotAStringFault::class, $result->error);
    }

    public function testFaultWhenTrackerDoesNotHaveSemanticDescription(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withId(101)
            ->build();

        $title       = StringFieldBuilder::aStringField(1001)->inTracker($tracker)->thatIsRequired()->build();
        $description = TextFieldBuilder::aTextField(1002)->inTracker($tracker)->build();
        $status      = ListFieldBuilder::aListField(1003)->inTracker($tracker)->build();

        $checker = new SuitableTrackerForDocumentChecker(
            RetrieveUsedFieldsStub::withFields($title, $description, $status),
            RetrieveSemanticDescriptionFieldStub::withNoField(),
        );

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($tracker, StringFieldBuilder::aStringField(1001)->thatIsRequired()->build()),
            $tracker,
        );

        $result = $checker->checkTrackerIsSuitableForDocument(
            $tracker,
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(NoSemanticDescriptionFault::class, $result->error);
    }

    public function testFaultWhenStatusFieldIsRequired(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withId(101)
            ->build();

        $title       = StringFieldBuilder::aStringField(1001)->inTracker($tracker)->thatIsRequired()->build();
        $description = TextFieldBuilder::aTextField(1002)->inTracker($tracker)->build();
        $status      = ListFieldBuilder::aListField(1003)->inTracker($tracker)->thatIsRequired()->build();

        $checker = new SuitableTrackerForDocumentChecker(
            RetrieveUsedFieldsStub::withFields($title, $description, $status),
            RetrieveSemanticDescriptionFieldStub::withTextField($description),
        );

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($tracker, StringFieldBuilder::aStringField(1001)->thatIsRequired()->build()),
            $tracker,
        );

        $result = $checker->checkTrackerIsSuitableForDocument(
            $tracker,
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(TooManyRequiredFieldsFault::class, $result->error);
    }

    public function testReturnsTrackerWhenEverythingIsOk(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withId(101)
            ->build();

        $title       = StringFieldBuilder::aStringField(1001)->inTracker($tracker)->thatIsRequired()->build();
        $description = TextFieldBuilder::aTextField(1002)->inTracker($tracker)->build();
        $status      = ListFieldBuilder::aListField(1003)->inTracker($tracker)->build();

        $checker = new SuitableTrackerForDocumentChecker(
            RetrieveUsedFieldsStub::withFields($title, $description, $status),
            RetrieveSemanticDescriptionFieldStub::withTextField($description),
        );

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($tracker, StringFieldBuilder::aStringField(1001)->thatIsRequired()->build()),
            $tracker,
        );

        $result = $checker->checkTrackerIsSuitableForDocument(
            $tracker,
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame($tracker, $result->value);
    }
}
