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

namespace Tuleap\Artidoc\Document;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\SaveSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\SectionsAsserter;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldDao;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Field\ArtifactSectionField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocDaoTest extends TestIntegrationTestCase
{
    private ArtidocWithContext $artidoc_101;
    private ArtidocWithContext $artidoc_102;
    private ArtidocWithContext $artidoc_103;

    #[\Override]
    protected function setUp(): void
    {
        $this->artidoc_101 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 101]));
        $this->artidoc_102 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 102]));
        $this->artidoc_103 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 103]));
    }

    private function createArtidocSections(ArtidocWithContext $artidoc, array $content): void
    {
        $dao = new SaveSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory());

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run(
            <<<EOS
            DELETE section, section_version
            FROM plugin_artidoc_section AS section
                    INNER JOIN plugin_artidoc_section_version AS section_version
                        ON (section.id = section_version.section_id) WHERE item_id = ?
            EOS,
            $artidoc->document->getId(),
        );

        foreach ($content as $content_to_insert) {
            $dao->saveSectionAtTheEnd($artidoc, $content_to_insert);
        }
    }

    private function getArtifactIdsToInsert(int ...$artifact_ids): array
    {
        return array_map(
            static fn ($id) => ContentToInsert::fromArtifactId($id, Level::One),
            $artifact_ids,
        );
    }

    public function testCloneItem(): void
    {
        $db  = DBFactory::getMainTuleapDBConnection()->getDB();
        $dao = $this->getDao();
        $this->createArtidocSections($this->artidoc_101, $this->getArtifactIdsToInsert(1001));
        $this->createArtidocSections($this->artidoc_102, [
            ContentToInsert::fromFreetext(new FreetextContent('Introduction', 'Lorem ipsum', Level::One)),
            ContentToInsert::fromFreetext(new FreetextContent('Requirements', '', Level::One)),
            ContentToInsert::fromArtifactId(2001, Level::Two),
            ContentToInsert::fromArtifactId(1001, Level::Two),
        ]);
        $dao->saveTracker($this->artidoc_102->document->getId(), 10001);

        (new ConfiguredFieldDao())->saveFields(
            $this->artidoc_102->document->getId(),
            [
                new ArtifactSectionField(457, DisplayType::COLUMN),
                new ArtifactSectionField(456, DisplayType::COLUMN),
            ]
        );
        $this->assertConfiguredFields($this->artidoc_102->document->getId(), 457, 456);

        SectionsAsserter::assertSectionsForDocument($this->artidoc_103, []);
        self::assertSame(2, $db->cell('SELECT count(*) FROM plugin_artidoc_section_freetext'));

        $dao->cloneItem($this->artidoc_102->document->getId(), $this->artidoc_103->document->getId());

        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1001]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, ['Introduction', 'Requirements', 2001, 1001]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_103, ['Introduction', 'Requirements', 2001, 1001]);
        self::assertSame(4, $db->cell('SELECT count(*) FROM plugin_artidoc_section_freetext'));

        self::assertSame(10001, $dao->getTracker($this->artidoc_103->document->getId()));
        $this->assertConfiguredFields($this->artidoc_103->document->getId(), 457, 456);
    }

    private function assertConfiguredFields(int $item_id, int ...$field_ids): void
    {
        $dao    = new ConfiguredFieldDao();
        $fields = $dao->retrieveConfiguredFieldsFromItemId($item_id);

        self::assertCount(count($field_ids), $fields);
        foreach ($field_ids as $index => $field_id) {
            self::assertSame($field_id, $fields[$index]->field_id);
        }
    }

    public function testCloneItemForEmptyDocument(): void
    {
        $dao = $this->getDao();
        $this->createArtidocSections($this->artidoc_101, $this->getArtifactIdsToInsert());
        $dao->saveTracker($this->artidoc_101->document->getId(), 10001);

        $dao->cloneItem($this->artidoc_101->document->getId(), $this->artidoc_103->document->getId());

        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, []);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_103, []);

        self::assertSame(10001, $dao->getTracker($this->artidoc_103->document->getId()));
    }

    public function testSave(): void
    {
        $dao = $this->getDao();
        $this->createArtidocSections($this->artidoc_101, $this->getArtifactIdsToInsert(1001, 1002, 1003));

        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1001, 1002, 1003]);

        $this->createArtidocSections($this->artidoc_102, $this->getArtifactIdsToInsert(1001, 1003));

        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1001, 1002, 1003]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1001, 1003]);

        $this->createArtidocSections($this->artidoc_101, $this->getArtifactIdsToInsert());

        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, []);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1001, 1003]);
    }

    public function testSaveTracker(): void
    {
        $dao = $this->getDao();

        self::assertNull($dao->getTracker($this->artidoc_101->document->getId()));

        $dao->saveTracker($this->artidoc_101->document->getId(), 1001);
        self::assertSame(1001, $dao->getTracker($this->artidoc_101->document->getId()));

        $dao->saveTracker($this->artidoc_101->document->getId(), 1002);
        self::assertSame(1002, $dao->getTracker($this->artidoc_101->document->getId()));
    }

    public function testDeleteSectionsByArtifactId(): void
    {
        $dao = $this->getDao();
        $this->createArtidocSections($this->artidoc_101, $this->getArtifactIdsToInsert(1001, 1002, 1003));
        $this->createArtidocSections($this->artidoc_102, $this->getArtifactIdsToInsert(1002, 1003, 1004));
        $this->createArtidocSections($this->artidoc_103, $this->getArtifactIdsToInsert(1003));

        $dao->deleteSectionsByArtifactId(1003);
        $dao->deleteSectionsByArtifactId(1005);

        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1001, 1002]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1002, 1004]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_103, []);
    }

    public function testDeleteConfiguredTracker(): void
    {
        $dao = $this->getDao();

        $dao->saveTracker($this->artidoc_101->document->getId(), 1001);
        self::assertSame(1001, $dao->getTracker($this->artidoc_101->document->getId()));

        $dao->deleteConfiguredTracker($this->artidoc_101->document->getId());
        self::assertNull($dao->getTracker($this->artidoc_101->document->getId()));
    }

    private function getDao(): ArtidocDao
    {
        return new ArtidocDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory());
    }

    /**
     * @return UUIDSectionIdentifierFactory
     */
    private function getSectionIdentifierFactory(): SectionIdentifierFactory
    {
        return new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
    }

    /**
     * @return UUIDFreetextIdentifierFactory
     */
    private function getFreetextIdentifierFactory(): FreetextIdentifierFactory
    {
        return new UUIDFreetextIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
    }
}
