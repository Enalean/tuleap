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
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
use Tuleap\Artidoc\Domain\Document\Section\AlreadyExistingSectionWithSameArtifactException;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\RawSectionContentFreetext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\RawSection;
use Tuleap\Artidoc\Domain\Document\Section\UnableToFindSiblingSectionException;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class ArtidocDaoTest extends TestIntegrationTestCase
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

    public function testSearchPaginatedRawSectionsById(): void
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

        $db->insertMany('plugin_artidoc_document', [
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_101->document->getId(),
                'artifact_id' => 1001,
                'freetext_id' => null,
                'rank'        => 1,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_102->document->getId(),
                'artifact_id' => null,
                'freetext_id' => $introduction_id,
                'rank'        => 1,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_102->document->getId(),
                'artifact_id' => null,
                'freetext_id' => $requirements_id,
                'rank'        => 2,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_102->document->getId(),
                'artifact_id' => 1001,
                'freetext_id' => null,
                'rank'        => 4,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_102->document->getId(),
                'artifact_id' => 2001,
                'freetext_id' => null,
                'rank'        => 3,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_101->document->getId(),
                'artifact_id' => 1003,
                'freetext_id' => null,
                'rank'        => 3,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_101->document->getId(),
                'artifact_id' => 1002,
                'freetext_id' => null,
                'rank'        => 2,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_101->document->getId(),
                'artifact_id' => 1004,
                'freetext_id' => null,
                'rank'        => 4,
            ],
        ]);

        $dao = new ArtidocDao($identifier_factory, $this->getFreetextIdentifierFactory());

        self::assertSame(4, $dao->searchPaginatedRawSections($this->artidoc_101, 50, 0)->total);
        self::assertSame(
            [1001, 1002, 1003, 1004],
            array_map(
                $this->getContentForAssertion(...),
                $dao->searchPaginatedRawSections($this->artidoc_101, 50, 0)->rows,
            ),
        );

        self::assertSame(4, $dao->searchPaginatedRawSections($this->artidoc_101, 2, 1)->total);
        self::assertSame(
            [1002, 1003],
            array_map(
                $this->getContentForAssertion(...),
                $dao->searchPaginatedRawSections($this->artidoc_101, 2, 1)->rows,
            ),
        );

        self::assertSame(4, $dao->searchPaginatedRawSections($this->artidoc_102, 50, 0)->total);
        self::assertSame(
            ['Introduction', 'Requirements', 2001, 1001],
            array_map(
                $this->getContentForAssertion(...),
                $dao->searchPaginatedRawSections($this->artidoc_102, 50, 0)->rows,
            )
        );

        self::assertSame(0, $dao->searchPaginatedRawSections($this->artidoc_103, 50, 0)->total);
        self::assertSame(
            [],
            array_map(
                $this->getContentForAssertion(...),
                $dao->searchPaginatedRawSections($this->artidoc_103, 50, 0)->rows,
            ),
        );
    }

    private function createArtidocSections(ArtidocDao $dao, ArtidocWithContext $artidoc, array $content): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_artidoc_document WHERE item_id = ?', $artidoc->document->getId());

        foreach ($content as $content_to_insert) {
            $dao->saveSectionAtTheEnd($artidoc, $content_to_insert);
        }
    }

    private function getArtifactIdsToInsert(int ...$artifact_ids): array
    {
        return array_map(
            static fn ($id) => ContentToInsert::fromArtifactId($id),
            $artifact_ids,
        );
    }

    public function testCloneItem(): void
    {
        $db  = DBFactory::getMainTuleapDBConnection()->getDB();
        $dao = $this->getDao();
        $this->createArtidocSections($dao, $this->artidoc_101, $this->getArtifactIdsToInsert(1001));
        $this->createArtidocSections($dao, $this->artidoc_102, [
            ContentToInsert::fromFreetext(new FreetextContent('Introduction', 'Lorem ipsum')),
            ContentToInsert::fromFreetext(new FreetextContent('Requirements', '')),
            ContentToInsert::fromArtifactId(2001),
            ContentToInsert::fromArtifactId(1001),
        ]);
        $dao->saveTracker($this->artidoc_102->document->getId(), 10001);

        $this->assertSectionsForDocument($dao, $this->artidoc_103, []);
        self::assertSame(2, $db->cell('SELECT count(*) FROM plugin_artidoc_section_freetext'));

        $dao->cloneItem($this->artidoc_102->document->getId(), $this->artidoc_103->document->getId());

        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1001]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, ['Introduction', 'Requirements', 2001, 1001]);
        $this->assertSectionsForDocument($dao, $this->artidoc_103, ['Introduction', 'Requirements', 2001, 1001]);
        self::assertSame(4, $db->cell('SELECT count(*) FROM plugin_artidoc_section_freetext'));

        self::assertSame(10001, $dao->getTracker($this->artidoc_103->document->getId()));
    }

    public function testCloneItemForEmptyDocument(): void
    {
        $dao = $this->getDao();
        $this->createArtidocSections($dao, $this->artidoc_101, $this->getArtifactIdsToInsert());
        $dao->saveTracker($this->artidoc_101->document->getId(), 10001);

        $dao->cloneItem($this->artidoc_101->document->getId(), $this->artidoc_103->document->getId());

        $this->assertSectionsForDocument($dao, $this->artidoc_101, []);
        $this->assertSectionsForDocument($dao, $this->artidoc_103, []);

        self::assertSame(10001, $dao->getTracker($this->artidoc_103->document->getId()));
    }

    public function testSave(): void
    {
        $dao = $this->getDao();
        $this->createArtidocSections($dao, $this->artidoc_101, $this->getArtifactIdsToInsert(1001, 1002, 1003));

        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1001, 1002, 1003]);

        $this->createArtidocSections($dao, $this->artidoc_102, $this->getArtifactIdsToInsert(1001, 1003));

        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1001, 1002, 1003]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1001, 1003]);

        $this->createArtidocSections($dao, $this->artidoc_101, $this->getArtifactIdsToInsert());

        $this->assertSectionsForDocument($dao, $this->artidoc_101, []);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1001, 1003]);
    }

    public function testSearchSectionById(): void
    {
        $dao = $this->getDao();

        $this->createArtidocSections($dao, $this->artidoc_101, $this->getArtifactIdsToInsert(1001, 1002, 1003));
        $rows = $dao->searchPaginatedRawSections($this->artidoc_101, 50, 0)->rows;

        foreach ($rows as $row) {
            $dao->searchSectionById($row->id)->match(
                function (RawSection $section) use ($row) {
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
        $this->createArtidocSections($dao, $this->artidoc_101, $this->getArtifactIdsToInsert());
        self::assertTrue(Result::isErr($dao->searchSectionById($first_section_id)));
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

    public function testSaveSectionAtTheEnd(): void
    {
        $dao = $this->getDao();

        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1001),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1002),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1003),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1004),
        );

        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1001, 1002, 1004]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1003]);
    }

    public function testSaveAlreadyExistingSectionAtTheEnd(): void
    {
        $dao = $this->getDao();

        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1001),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1002),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1003),
        );

        $this->expectException(AlreadyExistingSectionWithSameArtifactException::class);
        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1001),
        );
    }

    public function testSaveSectionBefore(): void
    {
        $dao = $this->getDao();

        [$uuid_1, $uuid_2, $uuid_3] = [
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1001),
            ),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1002),
            ),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1003),
            ),
        ];

        $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1004),
            $uuid_1,
        );
        $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1005),
            $uuid_2,
        );
        $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1006),
            $uuid_3,
        );

        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1004, 1001, 1005, 1002, 1006, 1003]);
    }

    public function testSaveAlreadyExistingArtifactSectionBefore(): void
    {
        $dao = $this->getDao();

        [$uuid_1] = [
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1001),
            ),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1002),
            ),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1003),
            ),
        ];

        $this->expectException(AlreadyExistingSectionWithSameArtifactException::class);
        $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1003),
            $uuid_1,
        );
    }

    public function testSaveSectionBeforeUnknownSectionWillRaiseAnException(): void
    {
        $dao = $this->getDao();

        [, $uuid_2] = [
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1001),
            ),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1002),
            ),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1003),
            ),
        ];

        // remove section linked to artifact #1002
        $this->createArtidocSections($dao, $this->artidoc_101, $this->getArtifactIdsToInsert(1001, 1003));

        $this->expectException(UnableToFindSiblingSectionException::class);
        $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1004),
            $uuid_2,
        );
    }

    public function testDeleteSectionsByArtifactId(): void
    {
        $dao = $this->getDao();
        $this->createArtidocSections($dao, $this->artidoc_101, $this->getArtifactIdsToInsert(1001, 1002, 1003));
        $this->createArtidocSections($dao, $this->artidoc_102, $this->getArtifactIdsToInsert(1002, 1003, 1004));
        $this->createArtidocSections($dao, $this->artidoc_103, $this->getArtifactIdsToInsert(1003));

        $dao->deleteSectionsByArtifactId(1003);
        $dao->deleteSectionsByArtifactId(1005);

        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1001, 1002]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1002, 1004]);
        $this->assertSectionsForDocument($dao, $this->artidoc_103, []);
    }

    public function testDeleteSectionsById(): void
    {
        $dao = $this->getDao();

        $uuid_1 = $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1001),
        );
        $uuid_2 = $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1002),
        );
        $uuid_3 = $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1003),
        );

        $dao->deleteSectionById($uuid_2);

        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1001, 1003]);
    }

    public function testReorderSections(): void
    {
        $dao = $this->getDao();

        $uuid_1 = $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1001),
        );
        $uuid_2 = $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1002),
        );
        $uuid_3 = $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1003),
        );
        $uuid_4 = $dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1001),
        );
        $uuid_5 = $dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1002),
        );
        $uuid_6 = $dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1003),
        );

        $order_builder = new SectionOrderBuilder($this->getSectionIdentifierFactory());

        // "before", at the beginning
        $order = $order_builder->build([$uuid_2->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1002, 1001, 1003]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1001, 1002, 1003]);

        // "before", in the middle
        $order = $order_builder->build([$uuid_3->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1002, 1003, 1001]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1001, 1002, 1003]);

        // "after", at the end
        $order = $order_builder->build([$uuid_2->toString()], 'after', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1003, 1001, 1002]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1001, 1002, 1003]);

        // "after", in the middle
        $order = $order_builder->build([$uuid_3->toString()], 'after', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1001, 1003, 1002]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1001, 1002, 1003]);
    }

    public function testExceptionWhenReorderSectionsOutsideOfDocument(): void
    {
        $dao = $this->getDao();

        $uuid_1 = $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1001),
        );
        $uuid_2 = $dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1002),
        );

        $order_builder = new SectionOrderBuilder($this->getSectionIdentifierFactory());

        $order = $order_builder->build([$uuid_2->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnknownSectionToMoveFault::class, $result->error);
        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1001]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1002]);

        $order = $order_builder->build([$uuid_2->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_102,
            $order->value,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnableToReorderSectionOutsideOfDocumentFault::class, $result->error);
        $this->assertSectionsForDocument($dao, $this->artidoc_101, [1001]);
        $this->assertSectionsForDocument($dao, $this->artidoc_102, [1002]);
    }

    /**
     * @param list<int|string> $content
     */
    private function assertSectionsForDocument(
        ArtidocDao $dao,
        ArtidocWithContext $artidoc,
        array $content,
    ): void {
        $paginated_raw_sections = $dao->searchPaginatedRawSections($artidoc, 50, 0);

        self::assertSame(count($content), $paginated_raw_sections->total);
        self::assertSame($content, array_map(
            $this->getContentForAssertion(...),
            $paginated_raw_sections->rows,
        ));
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

    private function getContentForAssertion(RawSection $raw_section): int|string|null
    {
        return $raw_section->content->apply(
            static fn (int $id) => Result::ok($id),
            static fn (RawSectionContentFreetext $freetext) => Result::ok($freetext->content->title),
        )->unwrapOr(null);
    }
}
