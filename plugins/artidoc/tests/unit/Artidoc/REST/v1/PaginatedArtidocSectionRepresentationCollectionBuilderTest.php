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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Document\ArtidocDocument;
use Tuleap\Artidoc\Document\ArtidocDocumentInformation;
use Tuleap\Artidoc\Document\PaginatedRawSections;
use Tuleap\Artidoc\Stubs\Document\RetrieveArtidocStub;
use Tuleap\Artidoc\Stubs\Document\SearchPaginatedRawSectionsStub;
use Tuleap\Artidoc\Stubs\Document\TransformRawSectionsToRepresentationStub;
use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\ArtifactTextFieldValueRepresentation;

final class PaginatedArtidocSectionRepresentationCollectionBuilderTest extends TestCase
{
    public function testHappyPath(): void
    {
        $collection = new PaginatedArtidocSectionRepresentationCollection(
            [
                new ArtidocSectionRepresentation(
                    $this->createMock(ArtifactReference::class),
                    'title 1',
                    $this->createMock(ArtifactTextFieldValueRepresentation::class),
                    true,
                ),
                new ArtidocSectionRepresentation(
                    $this->createMock(ArtifactReference::class),
                    'title 2',
                    $this->createMock(ArtifactTextFieldValueRepresentation::class),
                    true,
                ),
                new ArtidocSectionRepresentation(
                    $this->createMock(ArtifactReference::class),
                    'title 3',
                    $this->createMock(ArtifactTextFieldValueRepresentation::class),
                    true,
                ),
                new ArtidocSectionRepresentation(
                    $this->createMock(ArtifactReference::class),
                    'title 4',
                    $this->createMock(ArtifactTextFieldValueRepresentation::class),
                    true,
                ),
            ],
            10,
        );

        $builder = new PaginatedArtidocSectionRepresentationCollectionBuilder(
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => 123]),
                    $this->createMock(ServiceDocman::class),
                )
            ),
            SearchPaginatedRawSectionsStub::withSections(
                new PaginatedRawSections(
                    123,
                    [
                        ['artifact_id' => 1],
                        ['artifact_id' => 2],
                        ['artifact_id' => 3],
                        ['artifact_id' => 4],
                    ],
                    10,
                ),
            ),
            TransformRawSectionsToRepresentationStub::withCollection($collection),
        );

        $result = $builder->build(123, 4, 0, UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isOk($result));
        self::assertSame($collection, $result->value);
    }

    public function testFaultWhenArtidocDocumentCannotBeRetrieved(): void
    {
        $builder = new PaginatedArtidocSectionRepresentationCollectionBuilder(
            RetrieveArtidocStub::withoutDocument(),
            SearchPaginatedRawSectionsStub::shouldNotBeCalled(),
            TransformRawSectionsToRepresentationStub::shouldNotBeCalled(),
        );

        $result = $builder->build(123, 4, 0, UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }

    public function testFaultWhenPaginatedSectionsCannotBeTransformedIntoRepresentation(): void
    {
        $builder = new PaginatedArtidocSectionRepresentationCollectionBuilder(
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => 123]),
                    $this->createMock(ServiceDocman::class),
                )
            ),
            SearchPaginatedRawSectionsStub::withSections(
                new PaginatedRawSections(
                    123,
                    [
                        ['artifact_id' => 1],
                        ['artifact_id' => 2],
                        ['artifact_id' => 3],
                        ['artifact_id' => 4],
                    ],
                    10,
                ),
            ),
            TransformRawSectionsToRepresentationStub::withoutCollection(),
        );

        $result = $builder->build(123, 4, 0, UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }
}
