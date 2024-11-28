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

use PFUser;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Stubs\Document\SaveOneSectionStub;
use Tuleap\Artidoc\Stubs\Document\TransformRawSectionsToRepresentationStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\ArtifactTextFieldValueRepresentation;

final class POSTSectionHandlerTest extends TestCase
{
    public const DUMMY_SECTION_ID   = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b5';
    public const ANOTHER_SECTION_ID = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b6';
    public const NEW_SECTION_ID     = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b7';

    private const PROJECT_ID = 101;

    private PFUser $user;
    private SectionIdentifierFactory $identifier_factory;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    public function testHappyPathAtTheEnd(): void
    {
        $saver = SaveOneSectionStub::withGeneratedSectionId($this->identifier_factory, self::NEW_SECTION_ID);

        $section_representation = new ArtidocSectionRepresentation(
            self::DUMMY_SECTION_ID,
            $this->createMock(ArtifactReference::class),
            $this->createMock(ArtifactFieldValueFullRepresentation::class),
            $this->createMock(ArtifactTextFieldValueRepresentation::class),
            true,
            null,
        );

        $handler = new POSTSectionHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            TransformRawSectionsToRepresentationStub::withCollection(
                new PaginatedArtidocSectionRepresentationCollection([$section_representation], 1),
            ),
            $saver,
            $this->identifier_factory,
        );

        $result = $handler->handle(
            1,
            new ArtidocPOSTSectionRepresentation(
                new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                null,
            ),
            $this->user,
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($saver->isSaved(1));
        self::assertSame(101, $saver->getSavedEndForId(1));
        self::assertInstanceOf(ArtidocSectionRepresentation::class, $result->value);
        self::assertSame(self::NEW_SECTION_ID, $result->value->id);
    }

    public function testHappyPathBeforeSection(): void
    {
        $saver = SaveOneSectionStub::withGeneratedSectionId($this->identifier_factory, self::NEW_SECTION_ID);

        $section_representation = new ArtidocSectionRepresentation(
            self::DUMMY_SECTION_ID,
            $this->createMock(ArtifactReference::class),
            $this->createMock(ArtifactFieldValueFullRepresentation::class),
            $this->createMock(ArtifactTextFieldValueRepresentation::class),
            true,
            null,
        );

        $handler = new POSTSectionHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            TransformRawSectionsToRepresentationStub::withCollection(
                new PaginatedArtidocSectionRepresentationCollection([$section_representation], 1),
            ),
            $saver,
            $this->identifier_factory,
        );

        $result = $handler->handle(
            1,
            new ArtidocPOSTSectionRepresentation(
                new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                new ArtidocPOSTSectionPositionBeforeRepresentation(self::ANOTHER_SECTION_ID),
            ),
            $this->user,
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($saver->isSaved(1));
        self::assertSame(101, $saver->getSavedBeforeForId(1));
        self::assertInstanceOf(ArtidocSectionRepresentation::class, $result->value);
        self::assertSame(self::NEW_SECTION_ID, $result->value->id);
    }

    public function testFaultWhenUnableToFindSiblingSection(): void
    {
        $saver = SaveOneSectionStub::withUnableToFindSiblingSection(self::NEW_SECTION_ID);

        $section_representation = new ArtidocSectionRepresentation(
            self::DUMMY_SECTION_ID,
            $this->createMock(ArtifactReference::class),
            $this->createMock(ArtifactFieldValueFullRepresentation::class),
            $this->createMock(ArtifactTextFieldValueRepresentation::class),
            true,
            null,
        );

        $handler = new POSTSectionHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            TransformRawSectionsToRepresentationStub::withCollection(
                new PaginatedArtidocSectionRepresentationCollection([$section_representation], 1),
            ),
            $saver,
            $this->identifier_factory,
        );

        $result = $handler->handle(
            1,
            new ArtidocPOSTSectionRepresentation(
                new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                new ArtidocPOSTSectionPositionBeforeRepresentation(self::ANOTHER_SECTION_ID),
            ),
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnableToFindSiblingSectionFault::class, $result->error);
        self::assertFalse($saver->isSaved(1));
    }

    private function provideArtidocPOSTSectionRepresentation(): array
    {
        return [
            [
                new ArtidocPOSTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                    null,
                ),
            ],
            [
                new ArtidocPOSTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                    new ArtidocPOSTSectionPositionBeforeRepresentation(self::ANOTHER_SECTION_ID),
                ),
            ],
        ];
    }

    /**
     * @dataProvider provideArtidocPOSTSectionRepresentation
     */
    public function testFaultWhenArtifactIsAlreadyReferencedInTheDocumentByAnotherSection(
        ArtidocPOSTSectionRepresentation $section,
    ): void {
        $saver = SaveOneSectionStub::withAlreadyExistingSectionWithSameArtifact(self::NEW_SECTION_ID);

        $section_representation = new ArtidocSectionRepresentation(
            self::DUMMY_SECTION_ID,
            $this->createMock(ArtifactReference::class),
            $this->createMock(ArtifactFieldValueFullRepresentation::class),
            $this->createMock(ArtifactTextFieldValueRepresentation::class),
            true,
            null,
        );

        $handler = new POSTSectionHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            TransformRawSectionsToRepresentationStub::withCollection(
                new PaginatedArtidocSectionRepresentationCollection([$section_representation], 1),
            ),
            $saver,
            $this->identifier_factory,
        );

        $result = $handler->handle(1, $section, $this->user);

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(AlreadyExistingSectionWithSameArtifactFault::class, $result->error);
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenDocumentCannotBeRetrieved(): void
    {
        $saver = SaveOneSectionStub::withGeneratedSectionId($this->identifier_factory, self::NEW_SECTION_ID);

        $handler = new POSTSectionHandler(
            RetrieveArtidocWithContextStub::withoutDocument(),
            TransformRawSectionsToRepresentationStub::shouldNotBeCalled(),
            $saver,
            $this->identifier_factory,
        );

        $result = $handler->handle(
            1,
            new ArtidocPOSTSectionRepresentation(
                new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                new ArtidocPOSTSectionPositionBeforeRepresentation(self::ANOTHER_SECTION_ID),
            ),
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenDocumentIsNotWritable(): void
    {
        $saver = SaveOneSectionStub::withGeneratedSectionId($this->identifier_factory, self::NEW_SECTION_ID);

        $handler = new POSTSectionHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanRead(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            TransformRawSectionsToRepresentationStub::shouldNotBeCalled(),
            $saver,
            $this->identifier_factory,
        );

        $result = $handler->handle(
            1,
            new ArtidocPOSTSectionRepresentation(
                new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                new ArtidocPOSTSectionPositionBeforeRepresentation(self::ANOTHER_SECTION_ID),
            ),
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }
}
