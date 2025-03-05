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

namespace Tuleap\Artidoc\Adapter\Document;

use Tuleap\Artidoc\Stubs\Document\SearchArtidocDocumentStub;
use Tuleap\Docman\Stubs\GetItemFromRowStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocRetrieverTest extends TestCase
{
    private const ITEM_ID = 12;

    public function testFaultWhenIdIsNotFound(): void
    {
        $retriever = new ArtidocRetriever(
            SearchArtidocDocumentStub::withoutResults(),
            GetItemFromRowStub::withVoid(),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123)));
    }

    public function testFaultWhenItemIsVoid(): void
    {
        $retriever = new ArtidocRetriever(
            SearchArtidocDocumentStub::withResults(['item_id' => self::ITEM_ID]),
            GetItemFromRowStub::withVoid(),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123)));
    }

    public function testFaultWhenItemIsNull(): void
    {
        $retriever = new ArtidocRetriever(
            SearchArtidocDocumentStub::withResults(['item_id' => self::ITEM_ID]),
            GetItemFromRowStub::withNull(),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123)));
    }

    public function testFaultWhenItemIsNotAnArtidoc(): void
    {
        $row = ['item_id' => self::ITEM_ID];

        $retriever = new ArtidocRetriever(
            SearchArtidocDocumentStub::withResults($row),
            GetItemFromRowStub::withItem(new \Docman_File($row)),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123)));
    }

    public function testHappyPath(): void
    {
        $row     = ['item_id' => self::ITEM_ID];
        $artidoc = new ArtidocDocument($row);

        $retriever = new ArtidocRetriever(
            SearchArtidocDocumentStub::withResults($row),
            GetItemFromRowStub::withItem($artidoc),
        );

        $result = $retriever->retrieveArtidoc(123);
        self::assertTrue(Result::isOk($result));
        self::assertSame($artidoc, $result->value);
    }
}
