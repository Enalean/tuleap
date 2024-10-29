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
use Tuleap\Artidoc\Document\RawSection;
use Tuleap\Artidoc\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Stubs\Document\RetrieveArtidocStub;
use Tuleap\Artidoc\Stubs\Document\SearchOneSectionStub;
use Tuleap\Artidoc\Stubs\Document\SectionIdentifierStub;
use Tuleap\Artidoc\Stubs\Document\TransformRawSectionsToRepresentationStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFileFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\ArtifactTextFieldValueRepresentation;
use Tuleap\Tracker\REST\Artifact\FileInfoRepresentation;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;

final class ArtidocSectionRepresentationBuilderTest extends TestCase
{
    public const SECTION_ID  = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b5';
    public const ITEM_ID     = 123;
    public const ARTIFACT_ID = 1001;
    private SectionIdentifierFactory $identifier_factory;

    protected function setUp(): void
    {
        $this->identifier_factory = new SectionIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    public function testHappyPath(): void
    {
        $attachments_representation = ArtifactFieldValueFileFullRepresentation::fromValues(
            FileFieldBuilder::aFileField(1)->build(),
            [
                new FileInfoRepresentation(
                    107,
                    103,
                    '',
                    'maraiste.jpg',
                    5910,
                    '/plugins/tracker/attachments/107-maraiste.jpg',
                    '/plugins/tracker/attachments/preview/107-maraiste.jpg',
                    'artifact_files/107',
                ),
            ],
        );

        $section_representation = new ArtidocSectionRepresentation(
            self::SECTION_ID,
            $this->createMock(ArtifactReference::class),
            $this->createMock(ArtifactFieldValueFullRepresentation::class),
            $this->createMock(ArtifactTextFieldValueRepresentation::class),
            true,
            $attachments_representation
        );

        $builder = new ArtidocSectionRepresentationBuilder(
            SearchOneSectionStub::withResults($this->getMatchingRawSection()),
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

        $result = $builder->build($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID), UserTestBuilder::buildWithDefaults());
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

        $result = $builder->build($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID), UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }

    public function testWhenDocumentIsNotFound(): void
    {
        $builder = new ArtidocSectionRepresentationBuilder(
            SearchOneSectionStub::withResults($this->getMatchingRawSection()),
            RetrieveArtidocStub::withoutDocument(),
            TransformRawSectionsToRepresentationStub::shouldNotBeCalled(),
        );

        $result = $builder->build($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID), UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }

    public function testWhenTransformerReturnsNoRepresentationForMatchingSection(): void
    {
        $builder = new ArtidocSectionRepresentationBuilder(
            SearchOneSectionStub::withResults($this->getMatchingRawSection()),
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

        $result = $builder->build($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID), UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }

    public function testWhenTransformerReturnsTooManyRepresentationsForMatchingSection(): void
    {
        $section_representation = new ArtidocSectionRepresentation(
            self::SECTION_ID,
            $this->createMock(ArtifactReference::class),
            $this->createMock(ArtifactFieldValueFullRepresentation::class),
            $this->createMock(ArtifactTextFieldValueRepresentation::class),
            true,
            null
        );

        $builder = new ArtidocSectionRepresentationBuilder(
            SearchOneSectionStub::withResults($this->getMatchingRawSection()),
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

        $result = $builder->build($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID), UserTestBuilder::buildWithDefaults());
        self::assertTrue(Result::isErr($result));
    }

    private function getMatchingRawSection(): RawSection
    {
        return RawSection::fromRow([
            'id' => SectionIdentifierStub::create(),
            'item_id' => self::ITEM_ID,
            'artifact_id' => self::ARTIFACT_ID,
            'rank' => 0,
        ]);
    }
}
