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

namespace Tuleap\Artidoc\Adapter\Document\Section;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\RetrievedSectionContentFreetext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RetrieveArtidocSectionDaoTest extends TestIntegrationTestCase
{
    private ArtidocWithContext $artidoc_101;
    private ArtidocWithContext $artidoc_102;
    private ArtidocWithContext $artidoc_103;

    protected function setUp(): void
    {
        $this->artidoc_101 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 101]));
        $this->artidoc_102 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 102]));
        $this->artidoc_103 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 103]));
    }

    public function testSearchPaginatedRetrievedSectionsById(): void
    {
        $identifier_factory = $this->getSectionIdentifierFactory();

        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $introduction_id = $this->getFreetextIdentifierFactory()->buildIdentifier()->getBytes();
        $db->insert(
            'plugin_artidoc_section_freetext',
            [
                'id'          => $introduction_id,
                'title'       => 'Introduction',
                'description' => 'Lorem ipsum',
            ]
        );
        $requirements_id = $this->getFreetextIdentifierFactory()->buildIdentifier()->getBytes();
        $db->insert(
            'plugin_artidoc_section_freetext',
            [
                'id'          => $requirements_id,
                'title'       => 'Requirements',
                'description' => '',
            ]
        );

        $section_1_id = $identifier_factory->buildIdentifier()->getBytes();
        $section_2_id = $identifier_factory->buildIdentifier()->getBytes();
        $section_3_id = $identifier_factory->buildIdentifier()->getBytes();
        $section_4_id = $identifier_factory->buildIdentifier()->getBytes();
        $section_5_id = $identifier_factory->buildIdentifier()->getBytes();
        $section_6_id = $identifier_factory->buildIdentifier()->getBytes();
        $section_7_id = $identifier_factory->buildIdentifier()->getBytes();
        $section_8_id = $identifier_factory->buildIdentifier()->getBytes();

        $db->insertMany('plugin_artidoc_section', [
            [
                'id'          => $section_1_id,
                'item_id'     => $this->artidoc_101->document->getId(),
            ],
            [
                'id'          => $section_2_id,
                'item_id'     => $this->artidoc_102->document->getId(),
            ],
            [
                'id'          => $section_3_id,
                'item_id'     => $this->artidoc_102->document->getId(),
            ],
            [
                'id'          => $section_4_id,
                'item_id'     => $this->artidoc_102->document->getId(),
            ],
            [
                'id'          => $section_5_id,
                'item_id'     => $this->artidoc_102->document->getId(),
            ],
            [
                'id'          => $section_6_id,
                'item_id'     => $this->artidoc_101->document->getId(),
            ],
            [
                'id'          => $section_7_id,
                'item_id'     => $this->artidoc_101->document->getId(),
            ],
            [
                'id'          => $section_8_id,
                'item_id'     => $this->artidoc_101->document->getId(),
            ],
        ]);
        $db->insertMany('plugin_artidoc_section_version', [
            [
                'section_id'  => $section_1_id,
                'artifact_id' => 1001,
                'freetext_id' => null,
                'rank'        => 1,
                'level'       => 1,
            ],
            [
                'section_id'  => $section_2_id,
                'artifact_id' => null,
                'freetext_id' => $introduction_id,
                'rank'        => 1,
                'level'       => 1,
            ],
            [
                'section_id'  => $section_3_id,
                'artifact_id' => null,
                'freetext_id' => $requirements_id,
                'rank'        => 2,
                'level'       => 1,
            ],
            [
                'section_id'  => $section_4_id,
                'artifact_id' => 1001,
                'freetext_id' => null,
                'rank'        => 4,
                'level'       => 1,
            ],
            [
                'section_id'  => $section_5_id,
                'artifact_id' => 2001,
                'freetext_id' => null,
                'rank'        => 3,
                'level'       => 1,
            ],
            [
                'section_id'  => $section_6_id,
                'artifact_id' => 1003,
                'freetext_id' => null,
                'rank'        => 3,
                'level'       => 1,
            ],
            [
                'section_id'  => $section_7_id,
                'artifact_id' => 1002,
                'freetext_id' => null,
                'rank'        => 2,
                'level'       => 1,
            ],
            [
                'section_id'  => $section_8_id,
                'artifact_id' => 1004,
                'freetext_id' => null,
                'rank'        => 4,
                'level'       => 1,
            ],
        ]);

        $dao = new RetrieveArtidocSectionDao($identifier_factory, $this->getFreetextIdentifierFactory());

        self::assertSame(4, $dao->searchPaginatedRetrievedSections($this->artidoc_101, 50, 0)->total);
        self::assertSame(
            [1001, 1002, 1003, 1004],
            array_map(
                $this->getContentForAssertion(...),
                $dao->searchPaginatedRetrievedSections($this->artidoc_101, 50, 0)->rows,
            ),
        );

        self::assertSame(4, $dao->searchPaginatedRetrievedSections($this->artidoc_101, 2, 1)->total);
        self::assertSame(
            [1002, 1003],
            array_map(
                $this->getContentForAssertion(...),
                $dao->searchPaginatedRetrievedSections($this->artidoc_101, 2, 1)->rows,
            ),
        );

        self::assertSame(4, $dao->searchPaginatedRetrievedSections($this->artidoc_102, 50, 0)->total);
        self::assertSame(
            ['Introduction', 'Requirements', 2001, 1001],
            array_map(
                $this->getContentForAssertion(...),
                $dao->searchPaginatedRetrievedSections($this->artidoc_102, 50, 0)->rows,
            )
        );

        self::assertSame(0, $dao->searchPaginatedRetrievedSections($this->artidoc_103, 50, 0)->total);
        self::assertSame(
            [],
            array_map(
                $this->getContentForAssertion(...),
                $dao->searchPaginatedRetrievedSections($this->artidoc_103, 50, 0)->rows,
            ),
        );
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

    public function testSearchSectionById(): void
    {
        $dao = $this->getDao();

        $this->createArtidocSections($this->artidoc_101, $this->getArtifactIdsToInsert(1001, 1002, 1003));
        $rows = $dao->searchPaginatedRetrievedSections($this->artidoc_101, 50, 0)->rows;

        foreach ($rows as $row) {
            $dao->searchSectionById($row->id)->match(
                function (RetrievedSection $section) use ($row) {
                    self::assertNotNull($section);
                    self::assertSame($row->id->toString(), $section->id->toString());
                    self::assertSame(
                        $this->getContentForAssertion($row),
                        $this->getContentForAssertion($section),
                    );
                    self::assertSame(101, $section->item_id);
                },
                function () {
                    self::fail('Section is expected');
                },
            );
        }

        $first_section_id = $rows[0]->id;
        $this->createArtidocSections($this->artidoc_101, $this->getArtifactIdsToInsert());
        self::assertTrue(Result::isErr($dao->searchSectionById($first_section_id)));
    }

    private function getDao(): RetrieveArtidocSectionDao
    {
        return new RetrieveArtidocSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory());
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

    private function getContentForAssertion(RetrievedSection $retrieved_sections): int|string|null
    {
        return $retrieved_sections->content->apply(
            static fn (int $id) => Result::ok($id),
            static fn (RetrievedSectionContentFreetext $freetext) => Result::ok($freetext->content->title),
        )->unwrapOr(null);
    }
}
