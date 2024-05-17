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
use Tuleap\Artidoc\Stubs\Document\RetrieveArtidocStub;
use Tuleap\Artidoc\Stubs\Document\SearchOneSectionStub;
use Tuleap\Artidoc\Stubs\Document\TransformRawSectionsToRepresentationStub;
use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\ArtifactTextFieldValueRepresentation;

final class ArtidocSectionRepresentationBuilderTest extends TestCase
{
    public const SECTION_ID  = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b5';
    public const ITEM_ID     = 123;
    public const ARTIFACT_ID = 1001;

    public function testHappyPath(): void
    {
        $section_representation = new ArtidocSectionRepresentation(
            self::SECTION_ID,
            $this->createMock(ArtifactReference::class),
            'title 4',
            $this->createMock(ArtifactTextFieldValueRepresentation::class),
            true,
        );

        $builder = new ArtidocSectionRepresentationBuilder(
            SearchOneSectionStub::withResults([
                'item_id'     => self::ITEM_ID,
                'artifact_id' => self::ARTIFACT_ID,
            ]),
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => self::ITEM_ID]),
                    $this->createMock(ServiceDocman::class),
                )
            ),
            TransformRawSectionsToRepresentationStub::withCollection(
                new PaginatedArtidocSectionRepresentationCollection([$section_representation], 1),
            ),
        );

        $result = $builder->build(self::SECTION_ID, UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isOk($result));
        self::assertSame($section_representation, $result->value);
    }

    public function testWhenSectionIsNotFound(): void
    {
        $builder = new ArtidocSectionRepresentationBuilder(
            SearchOneSectionStub::withoutResults(),
            RetrieveArtidocStub::shouldNotBeCalled(),
            TransformRawSectionsToRepresentationStub::shouldNotBeCalled(),
        );

        $result = $builder->build(self::SECTION_ID, UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }

    public function testWhenDocumentIsNotFound(): void
    {
        $builder = new ArtidocSectionRepresentationBuilder(
            SearchOneSectionStub::withResults([
                'item_id'     => self::ITEM_ID,
                'artifact_id' => self::ARTIFACT_ID,
            ]),
            RetrieveArtidocStub::withoutDocument(),
            TransformRawSectionsToRepresentationStub::shouldNotBeCalled(),
        );

        $result = $builder->build(self::SECTION_ID, UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }

    public function testWhenTransformerReturnsNoRepresentationForMatchingSection(): void
    {
        $builder = new ArtidocSectionRepresentationBuilder(
            SearchOneSectionStub::withResults([
                'item_id'     => self::ITEM_ID,
                'artifact_id' => self::ARTIFACT_ID,
            ]),
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => self::ITEM_ID]),
                    $this->createMock(ServiceDocman::class),
                )
            ),
            TransformRawSectionsToRepresentationStub::withCollection(
                new PaginatedArtidocSectionRepresentationCollection([], 1),
            ),
        );

        $result = $builder->build(self::SECTION_ID, UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }

    public function testWhenTransformerReturnsTwoManyRepresentationsForMatchingSection(): void
    {
        $section_representation = new ArtidocSectionRepresentation(
            self::SECTION_ID,
            $this->createMock(ArtifactReference::class),
            'title 4',
            $this->createMock(ArtifactTextFieldValueRepresentation::class),
            true,
        );

        $builder = new ArtidocSectionRepresentationBuilder(
            SearchOneSectionStub::withResults([
                'item_id'     => self::ITEM_ID,
                'artifact_id' => self::ARTIFACT_ID,
            ]),
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => self::ITEM_ID]),
                    $this->createMock(ServiceDocman::class),
                )
            ),
            TransformRawSectionsToRepresentationStub::withCollection(
                new PaginatedArtidocSectionRepresentationCollection(
                    [
                        $section_representation,
                        $section_representation,
                    ],
                    2
                ),
            ),
        );

        $result = $builder->build(self::SECTION_ID, UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }
}
