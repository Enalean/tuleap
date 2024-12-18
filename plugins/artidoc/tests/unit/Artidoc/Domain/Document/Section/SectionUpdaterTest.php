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

use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Stubs\Document\FreetextIdentifierStub;
use Tuleap\Artidoc\Stubs\Document\SectionIdentifierStub;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\Freetext\UpdateFreetextContentStub;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\RetrieveSectionStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

final class SectionUpdaterTest extends TestCase
{
    public const SECTION_ID  = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b5';
    public const ITEM_ID     = 123;
    public const ARTIFACT_ID = 1001;

    private SectionIdentifierFactory $identifier_factory;

    protected function setUp(): void
    {
        $this->identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    public function testHappyPath(): void
    {
        $update = UpdateFreetextContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::witMatchingSectionUserCanWrite(
                $this->getMatchingFreetextSection(),
            ),
            $update,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            'Introduction',
            '',
        );
        self::assertTrue(Result::isOk($result));
        self::assertTrue($update->isCalled());
    }

    /**
     * @testWith [""]
     *           [" "]
     */
    public function testFaultWhenTitleIsEmpty(string $empty): void
    {
        $update = UpdateFreetextContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::witMatchingSectionUserCanWrite(
                $this->getMatchingFreetextSection(),
            ),
            $update,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            $empty,
            '',
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(EmptyTitleFault::class, $result->error);
        self::assertFalse($update->isCalled());
    }

    public function testFaultWhenSectionIsAnArtifactSection(): void
    {
        $update = UpdateFreetextContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::witMatchingSectionUserCanWrite(
                $this->getMatchingArtifactSection(),
            ),
            $update,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            'Introduction',
            '',
        );
        self::assertFalse(Result::isOk($result));
        self::assertFalse($update->isCalled());
    }

    public function testFaultWhenUserCannotWrite(): void
    {
        $update = UpdateFreetextContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::witMatchingSectionUserCanRead(
                $this->getMatchingFreetextSection(),
            ),
            $update,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            'Introduction',
            '',
        );
        self::assertFalse(Result::isOk($result));
        self::assertFalse($update->isCalled());
    }

    public function testFaultWhenNoMatchingSection(): void
    {
        $update = UpdateFreetextContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::withoutMatchingSection(),
            $update,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            'Introduction',
            '',
        );
        self::assertFalse(Result::isOk($result));
        self::assertFalse($update->isCalled());
    }

    private function getMatchingArtifactSection(): RawSection
    {
        return RawSection::fromArtifact([
            'id'          => SectionIdentifierStub::create(),
            'item_id'     => self::ITEM_ID,
            'artifact_id' => self::ARTIFACT_ID,
            'rank'        => 0,
        ]);
    }

    private function getMatchingFreetextSection(): RawSection
    {
        return RawSection::fromFreetext([
            'id'                   => SectionIdentifierStub::create(),
            'freetext_id'          => FreetextIdentifierStub::create(),
            'item_id'              => self::ITEM_ID,
            'freetext_title'       => 'Intro',
            'freetext_description' => '',
            'rank'                 => 0,
        ]);
    }
}
