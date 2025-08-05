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
use Tuleap\Artidoc\Stubs\Domain\Document\Section\SearchPaginatedRetrievedSectionsStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PaginatedRetrievedSectionsRetrieverTest extends TestCase
{
    public function testHappyPath(): void
    {
        $document = new ArtidocWithContext(
            new ArtidocDocument(['item_id' => 123]),
        );

        $sections = new PaginatedRetrievedSections(
            $document,
            [
                RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
                RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
            ],
            10,
        );

        $retriever = new PaginatedRetrievedSectionsRetriever(
            RetrieveArtidocWithContextStub::withDocumentUserCanRead($document),
            SearchPaginatedRetrievedSectionsStub::withSections($sections),
        );

        $result = $retriever->retrievePaginatedRetrievedSections(123, 4, 0);
        self::assertTrue(Result::isOk($result));
        self::assertSame($sections, $result->value);
    }

    public function testFaultWhenArtidocDocumentCannotBeRetrieved(): void
    {
        $retriever = new PaginatedRetrievedSectionsRetriever(
            RetrieveArtidocWithContextStub::withoutDocument(),
            SearchPaginatedRetrievedSectionsStub::shouldNotBeCalled(),
        );

        $result = $retriever->retrievePaginatedRetrievedSections(123, 4, 0);
        self::assertTrue(Result::isErr($result));
    }
}
