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

namespace Tuleap\Artidoc\Domain\Document\Section;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Stubs\Document\SectionIdentifierStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\SearchPaginatedRawSectionsStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

final class PaginatedRawSectionsRetrieverTest extends TestCase
{
    public function testHappyPath(): void
    {
        $document = new ArtidocWithContext(
            new ArtidocDocument(['item_id' => 123]),
        );

        $sections = new PaginatedRawSections(
            $document,
            [
                RawSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0]),
                RawSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1]),
                RawSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2]),
                RawSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3]),
            ],
            10,
        );

        $retriever = new PaginatedRawSectionsRetriever(
            RetrieveArtidocWithContextStub::withDocumentUserCanRead($document),
            SearchPaginatedRawSectionsStub::withSections($sections),
        );

        $result = $retriever->retrievePaginatedRawSections(123, 4, 0);
        self::assertTrue(Result::isOk($result));
        self::assertSame($sections, $result->value);
    }

    public function testFaultWhenArtidocDocumentCannotBeRetrieved(): void
    {
        $retriever = new PaginatedRawSectionsRetriever(
            RetrieveArtidocWithContextStub::withoutDocument(),
            SearchPaginatedRawSectionsStub::shouldNotBeCalled(),
        );

        $result = $retriever->retrievePaginatedRawSections(123, 4, 0);
        self::assertTrue(Result::isErr($result));
    }
}
