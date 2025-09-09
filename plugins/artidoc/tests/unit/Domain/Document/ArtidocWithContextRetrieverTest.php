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

namespace Tuleap\Artidoc\Domain\Document;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Stubs\Domain\Document\CheckCurrentUserHasArtidocPermissionsStub;
use Tuleap\Artidoc\Stubs\Domain\Document\DecorateArtidocWithContextStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocWithContextRetrieverTest extends TestCase
{
    private const int ITEM_ID = 12;

    public function testFaultWhenArtidocCannotBeRetrieved(): void
    {
        $retriever = new ArtidocWithContextRetriever(
            RetrieveArtidocStub::withoutDocument(),
            CheckCurrentUserHasArtidocPermissionsStub::shouldNotBeCalled(),
            DecorateArtidocWithContextStub::shouldNotBeCalled(),
        );

        $result = $retriever->retrieveArtidocUserCanRead(self::ITEM_ID);
        self::assertTrue(Result::isErr($result));

        $result = $retriever->retrieveArtidocUserCanWrite(self::ITEM_ID);
        self::assertTrue(Result::isErr($result));
    }

    public function testFaultWhenArtidocCannotBeReadNorWrittenByCurrentUser(): void
    {
        $artidoc = new ArtidocDocument(['item_id' => self::ITEM_ID]);

        $retriever = new ArtidocWithContextRetriever(
            RetrieveArtidocStub::withDocument($artidoc),
            CheckCurrentUserHasArtidocPermissionsStub::withCurrentUserCannotReadNorWrite(),
            DecorateArtidocWithContextStub::shouldNotBeCalled(),
        );

        $result = $retriever->retrieveArtidocUserCanRead(self::ITEM_ID);
        self::assertTrue(Result::isErr($result));

        $result = $retriever->retrieveArtidocUserCanWrite(self::ITEM_ID);
        self::assertTrue(Result::isErr($result));
    }

    public function testFaultWhenArtidocCanBeReadButContextIsInvalid(): void
    {
        $artidoc = new ArtidocDocument(['item_id' => self::ITEM_ID]);

        $retriever = new ArtidocWithContextRetriever(
            RetrieveArtidocStub::withDocument($artidoc),
            CheckCurrentUserHasArtidocPermissionsStub::withCurrentUserCanRead(),
            DecorateArtidocWithContextStub::withoutValidContext(),
        );

        $result = $retriever->retrieveArtidocUserCanRead(self::ITEM_ID);
        self::assertTrue(Result::isErr($result));
    }

    public function testFaultWhenArtidocCanBeWrittenButContextIsInvalid(): void
    {
        $artidoc = new ArtidocDocument(['item_id' => self::ITEM_ID]);

        $retriever = new ArtidocWithContextRetriever(
            RetrieveArtidocStub::withDocument($artidoc),
            CheckCurrentUserHasArtidocPermissionsStub::withCurrentUserCanWrite(),
            DecorateArtidocWithContextStub::withoutValidContext(),
        );

        $result = $retriever->retrieveArtidocUserCanRead(self::ITEM_ID);
        self::assertTrue(Result::isErr($result));
    }

    public function testOkWhenArtidocCanBeReadAndContextIsValid(): void
    {
        $artidoc = new ArtidocDocument(['item_id' => self::ITEM_ID]);

        $retriever = new ArtidocWithContextRetriever(
            RetrieveArtidocStub::withDocument($artidoc),
            CheckCurrentUserHasArtidocPermissionsStub::withCurrentUserCanRead(),
            DecorateArtidocWithContextStub::withValidContext(),
        );

        $result = $retriever->retrieveArtidocUserCanRead(self::ITEM_ID);
        self::assertTrue(Result::isOk($result));
        self::assertEquals($artidoc, $result->value->document);
    }

    public function testOkWhenArtidocCanBeWrittenAndContextIsValid(): void
    {
        $artidoc = new ArtidocDocument(['item_id' => self::ITEM_ID]);

        $retriever = new ArtidocWithContextRetriever(
            RetrieveArtidocStub::withDocument($artidoc),
            CheckCurrentUserHasArtidocPermissionsStub::withCurrentUserCanWrite(),
            DecorateArtidocWithContextStub::withValidContext(),
        );

        $result = $retriever->retrieveArtidocUserCanRead(self::ITEM_ID);
        self::assertTrue(Result::isOk($result));
        self::assertEquals($artidoc, $result->value->document);
    }
}
