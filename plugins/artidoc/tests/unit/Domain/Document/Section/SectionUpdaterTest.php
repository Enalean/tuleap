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
use Tuleap\Artidoc\Stubs\Domain\Document\Section\Artifact\UpdateArtifactContentStub;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\Freetext\UpdateFreetextContentStub;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\RetrieveSectionStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SectionUpdaterTest extends TestCase
{
    private const string SECTION_ID = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b5';
    private const int ITEM_ID       = 123;
    private const int ARTIFACT_ID   = 1001;

    private SectionIdentifierFactory $identifier_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    public function testHappyPathFreetext(): void
    {
        $update_freetext = UpdateFreetextContentStub::build();
        $update_artifact = UpdateArtifactContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::witMatchingSectionUserCanWrite(
                $this->getMatchingFreetextSection(),
            ),
            $update_freetext,
            $update_artifact,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            'Introduction',
            '',
            [],
            Level::One,
        );
        self::assertTrue(Result::isOk($result));
        self::assertTrue($update_freetext->isCalled());
        self::assertFalse($update_artifact->isCalled());
    }

    public function testHappyPathArtifact(): void
    {
        $update_freetext = UpdateFreetextContentStub::build();
        $update_artifact = UpdateArtifactContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::witMatchingSectionUserCanWrite(
                $this->getMatchingArtifactSection(),
            ),
            $update_freetext,
            $update_artifact,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            'Introduction',
            '',
            [],
            Level::One,
        );
        self::assertTrue(Result::isOk($result));
        self::assertFalse($update_freetext->isCalled());
        self::assertTrue($update_artifact->isCalled());
    }

    #[\PHPUnit\Framework\Attributes\TestWith([''])]
    #[\PHPUnit\Framework\Attributes\TestWith([' '])]
    public function testFaultWhenTitleIsEmpty(string $empty): void
    {
        $update_freetext = UpdateFreetextContentStub::build();
        $update_artifact = UpdateArtifactContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::witMatchingSectionUserCanWrite(
                $this->getMatchingFreetextSection(),
            ),
            $update_freetext,
            $update_artifact,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            $empty,
            '',
            [],
            Level::One,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(EmptyTitleFault::class, $result->error);
        self::assertFalse($update_freetext->isCalled());
        self::assertFalse($update_artifact->isCalled());
    }

    public function testFaultWhenUserCannotWrite(): void
    {
        $update_freetext = UpdateFreetextContentStub::build();
        $update_artifact = UpdateArtifactContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::witMatchingSectionUserCanRead(
                $this->getMatchingFreetextSection(),
            ),
            $update_freetext,
            $update_artifact,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            'Introduction',
            '',
            [],
            Level::One,
        );
        self::assertFalse(Result::isOk($result));
        self::assertFalse($update_freetext->isCalled());
        self::assertFalse($update_artifact->isCalled());
    }

    public function testFaultWhenNoMatchingSection(): void
    {
        $update_freetext = UpdateFreetextContentStub::build();
        $update_artifact = UpdateArtifactContentStub::build();

        $updater = new SectionUpdater(
            RetrieveSectionStub::withoutMatchingSection(),
            $update_freetext,
            $update_artifact,
        );

        $result = $updater->update(
            $this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID),
            'Introduction',
            '',
            [],
            Level::One,
        );
        self::assertFalse(Result::isOk($result));
        self::assertFalse($update_freetext->isCalled());
        self::assertFalse($update_artifact->isCalled());
    }

    private function getMatchingArtifactSection(): RetrievedSection
    {
        return RetrievedSection::fromArtifact([
            'id'          => SectionIdentifierStub::create(),
            'item_id'     => self::ITEM_ID,
            'artifact_id' => self::ARTIFACT_ID,
            'rank'        => 0,
            'level'       => 1,
        ]);
    }

    private function getMatchingFreetextSection(): RetrievedSection
    {
        return RetrievedSection::fromFreetext([
            'id'                   => SectionIdentifierStub::create(),
            'freetext_id'          => FreetextIdentifierStub::create(),
            'item_id'              => self::ITEM_ID,
            'freetext_title'       => 'Intro',
            'freetext_description' => '',
            'rank'                 => 0,
            'level'                => 1,
        ]);
    }
}
