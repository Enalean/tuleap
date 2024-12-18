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
use Tuleap\Artidoc\Adapter\Document\Section\AlreadyExistingSectionWithSameArtifactFault;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\UnableToFindSiblingSectionFault;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\CollectRequiredSectionInformationStub;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\SaveOneSectionStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Test\PHPUnit\TestCase;

final class SectionCreatorTest extends TestCase
{
    public const DUMMY_SECTION_ID   = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b5';
    public const ANOTHER_SECTION_ID = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b6';
    public const NEW_SECTION_ID     = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b7';

    private const PROJECT_ID = 101;

    private SectionIdentifierFactory $identifier_factory;

    protected function setUp(): void
    {
        $this->identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    public function testHappyPathAtTheEnd(): void
    {
        $saver     = SaveOneSectionStub::withGeneratedSectionId($this->identifier_factory, self::NEW_SECTION_ID);
        $collector = CollectRequiredSectionInformationStub::withRequiredInformation();

        $creator = new SectionCreator(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            $saver,
            $collector,
        );

        $result = $creator->create(
            1,
            101,
            Option::nothing(SectionIdentifier::class),
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($saver->isSaved(1));
        self::assertTrue($collector->isCalled());
        self::assertSame(101, $saver->getSavedEndForId(1)->artifact_id->unwrapOr(null));
        self::assertSame(self::NEW_SECTION_ID, $result->value->toString());
    }

    public function testHappyPathBeforeSection(): void
    {
        $saver     = SaveOneSectionStub::withGeneratedSectionId($this->identifier_factory, self::NEW_SECTION_ID);
        $collector = CollectRequiredSectionInformationStub::withRequiredInformation();

        $creator = new SectionCreator(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            $saver,
            $collector,
        );

        $result = $creator->create(
            1,
            101,
            Option::fromValue($this->identifier_factory->buildFromHexadecimalString(self::ANOTHER_SECTION_ID)),
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($saver->isSaved(1));
        self::assertTrue($collector->isCalled());
        self::assertSame(101, $saver->getSavedBeforeForId(1)->artifact_id->unwrapOr(null));
        self::assertSame(self::NEW_SECTION_ID, $result->value->toString());
    }

    public function testFaultWhenUnableToFindSiblingSection(): void
    {
        $saver     = SaveOneSectionStub::withUnableToFindSiblingSection(self::NEW_SECTION_ID);
        $collector = CollectRequiredSectionInformationStub::withRequiredInformation();

        $creator = new SectionCreator(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            $saver,
            $collector,
        );

        $result = $creator->create(
            1,
            101,
            Option::fromValue($this->identifier_factory->buildFromHexadecimalString(self::ANOTHER_SECTION_ID)),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnableToFindSiblingSectionFault::class, $result->error);
        self::assertFalse($saver->isSaved(1));
    }

    private function provideArtidocPOSTSectionRepresentation(): array
    {
        return [
            [101, null],
            [102, self::ANOTHER_SECTION_ID],
        ];
    }

    /**
     * @dataProvider provideArtidocPOSTSectionRepresentation
     */
    public function testFaultWhenArtifactIsAlreadyReferencedInTheDocumentByAnotherSection(
        int $artifact_id,
        ?string $before_section_id,
    ): void {
        $saver     = SaveOneSectionStub::withAlreadyExistingSectionWithSameArtifact(self::NEW_SECTION_ID);
        $collector = CollectRequiredSectionInformationStub::withRequiredInformation();

        $creator = new SectionCreator(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            $saver,
            $collector,
        );

        $result = $creator->create(
            1,
            $artifact_id,
            $before_section_id === null
                ? Option::nothing(SectionIdentifier::class)
            : Option::fromValue($this->identifier_factory->buildFromHexadecimalString(self::ANOTHER_SECTION_ID))
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(AlreadyExistingSectionWithSameArtifactFault::class, $result->error);
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenArtifactDoesNotHaveRequiredInformation(): void
    {
        $saver     = SaveOneSectionStub::withAlreadyExistingSectionWithSameArtifact(self::NEW_SECTION_ID);
        $collector = CollectRequiredSectionInformationStub::withoutRequiredInformation();

        $creator = new SectionCreator(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            $saver,
            $collector,
        );

        $result = $creator->create(
            1,
            101,
            Option::fromValue($this->identifier_factory->buildFromHexadecimalString(self::ANOTHER_SECTION_ID)),
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenDocumentCannotBeRetrieved(): void
    {
        $saver     = SaveOneSectionStub::withGeneratedSectionId($this->identifier_factory, self::NEW_SECTION_ID);
        $collector = CollectRequiredSectionInformationStub::withRequiredInformation();

        $creator = new SectionCreator(
            RetrieveArtidocWithContextStub::withoutDocument(),
            $saver,
            $collector,
        );

        $result = $creator->create(
            1,
            101,
            Option::fromValue($this->identifier_factory->buildFromHexadecimalString(self::ANOTHER_SECTION_ID)),
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenDocumentIsNotWritable(): void
    {
        $saver     = SaveOneSectionStub::withGeneratedSectionId($this->identifier_factory, self::NEW_SECTION_ID);
        $collector = CollectRequiredSectionInformationStub::withRequiredInformation();

        $creator = new SectionCreator(
            RetrieveArtidocWithContextStub::withDocumentUserCanRead(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            $saver,
            $collector,
        );

        $result = $creator->create(
            1,
            101,
            Option::fromValue($this->identifier_factory->buildFromHexadecimalString(self::ANOTHER_SECTION_ID)),
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }
}
