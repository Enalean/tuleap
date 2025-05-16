<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace integration\Artidoc\Document\Field;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\SaveSectionDao;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldDao;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Field\ArtifactSectionField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfiguredFieldDaoTest extends TestIntegrationTestCase
{
    private const ITEM_ID_1 = 123;
    private const ITEM_ID_2 = 124;
    private const ITEM_ID_3 = 125;

    private SectionIdentifier $section_in_item_1_id;
    private SectionIdentifier $section_in_item_2_id;
    private SectionIdentifier $section_in_item_3_id;

    protected function setUp(): void
    {
        $dao = new ConfiguredFieldDao();
        $dao->saveFields(
            self::ITEM_ID_2,
            [
                new ArtifactSectionField(456, DisplayType::COLUMN),
            ]
        );
        $dao->saveFields(
            self::ITEM_ID_3,
            [
                new ArtifactSectionField(457, DisplayType::COLUMN),
                new ArtifactSectionField(456, DisplayType::COLUMN),
            ]
        );

        $dao = new SaveSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory());
        $dao->saveSectionAtTheEnd(
            new ArtidocWithContext(new ArtidocDocument(['item_id' => self::ITEM_ID_1])),
            ContentToInsert::fromArtifactId(1001, Level::One),
        )->map(function (SectionIdentifier $id) {
            $this->section_in_item_1_id = $id;
        });
        $dao->saveSectionAtTheEnd(
            new ArtidocWithContext(new ArtidocDocument(['item_id' => self::ITEM_ID_2])),
            ContentToInsert::fromArtifactId(1001, Level::One),
        )->map(function (SectionIdentifier $id) {
            $this->section_in_item_2_id = $id;
        });
        $dao->saveSectionAtTheEnd(
            new ArtidocWithContext(new ArtidocDocument(['item_id' => self::ITEM_ID_3])),
            ContentToInsert::fromArtifactId(1001, Level::One),
        )->map(function (SectionIdentifier $id) {
            $this->section_in_item_3_id = $id;
        });
    }

    public function testRetrieveConfiguredFieldsFromItemId(): void
    {
        $dao = new ConfiguredFieldDao();

        $this->assertConfiguredFields(
            $dao->retrieveConfiguredFieldsFromItemId(self::ITEM_ID_1),
            $dao->retrieveConfiguredFieldsFromItemId(self::ITEM_ID_2),
            $dao->retrieveConfiguredFieldsFromItemId(self::ITEM_ID_3),
        );
    }

    public function testRetrieveConfiguredFieldsFromSectionId(): void
    {
        $dao = new ConfiguredFieldDao();

        $this->assertConfiguredFields(
            $dao->retrieveConfiguredFieldsFromSectionId($this->section_in_item_1_id),
            $dao->retrieveConfiguredFieldsFromSectionId($this->section_in_item_2_id),
            $dao->retrieveConfiguredFieldsFromSectionId($this->section_in_item_3_id),
        );
    }

    public function testDeleteConfiguredFieldById(): void
    {
        $dao = new ConfiguredFieldDao();

        $dao->deleteConfiguredFieldById(456);

        $this->assertConfiguredFieldsAfterDeletingField456(
            $dao->retrieveConfiguredFieldsFromItemId(self::ITEM_ID_1),
            $dao->retrieveConfiguredFieldsFromItemId(self::ITEM_ID_2),
            $dao->retrieveConfiguredFieldsFromItemId(self::ITEM_ID_3),
        );
    }

    public function testDeleteConfiguredFieldsByDocumentId(): void
    {
        $dao = new ConfiguredFieldDao();

        $dao->deleteConfiguredFieldByArtidocId(self::ITEM_ID_3);

        self::assertEmpty($dao->retrieveConfiguredFieldsFromItemId(self::ITEM_ID_3));
    }

    /**
     * @param list<ArtifactSectionField> $fields_in_item_1
     * @param list<ArtifactSectionField> $fields_in_item_2
     * @param list<ArtifactSectionField> $fields_in_item_3
     */
    private function assertConfiguredFields(
        array $fields_in_item_1,
        array $fields_in_item_2,
        array $fields_in_item_3,
    ): void {
        self::assertEmpty($fields_in_item_1);

        self::assertCount(1, $fields_in_item_2);
        self::assertSame(456, $fields_in_item_2[0]->field_id);
        self::assertSame(DisplayType::COLUMN, $fields_in_item_2[0]->display_type);

        self::assertCount(2, $fields_in_item_3);
        self::assertSame(457, $fields_in_item_3[0]->field_id);
        self::assertSame(DisplayType::COLUMN, $fields_in_item_3[0]->display_type);
        self::assertSame(456, $fields_in_item_3[1]->field_id);
        self::assertSame(DisplayType::COLUMN, $fields_in_item_3[1]->display_type);
    }

    /**
     * @param list<ArtifactSectionField> $fields_in_item_1
     * @param list<ArtifactSectionField> $fields_in_item_2
     * @param list<ArtifactSectionField> $fields_in_item_3
     */
    private function assertConfiguredFieldsAfterDeletingField456(
        array $fields_in_item_1,
        array $fields_in_item_2,
        array $fields_in_item_3,
    ): void {
        self::assertEmpty($fields_in_item_1);

        self::assertEmpty($fields_in_item_2);

        self::assertCount(1, $fields_in_item_3);
        self::assertSame(457, $fields_in_item_3[0]->field_id);
        self::assertSame(DisplayType::COLUMN, $fields_in_item_3[0]->display_type);
    }

    private function getSectionIdentifierFactory(): UUIDSectionIdentifierFactory
    {
        return new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
    }

    private function getFreetextIdentifierFactory(): UUIDFreetextIdentifierFactory
    {
        return new UUIDFreetextIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
    }
}
