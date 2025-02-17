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

namespace Tuleap\Artidoc\Adapter\Document\Section\Freetext;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\RetrieveArtidocSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\SaveSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\SectionsAsserter;
use Tuleap\Artidoc\Adapter\Document\Section\UpdateLevelDao;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\RetrievedSectionContentFreetext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class UpdateFreetextContentDaoTest extends TestIntegrationTestCase
{
    public function testUpdateFreetextContent(): void
    {
        $artidoc = new ArtidocWithContext(new ArtidocDocument(['item_id' => 101]));
        $ids     = $this->createArtidocSections($artidoc, [
            ContentToInsert::fromFreetext(new FreetextContent('Intro', '', Level::One)),
            ContentToInsert::fromFreetext(new FreetextContent('Requirements', '', Level::One)),
            ContentToInsert::fromArtifactId(1001, Level::One),
            ContentToInsert::fromArtifactId(1002, Level::One),
        ]);
        SectionsAsserter::assertSectionsForDocument($artidoc, ['Intro', 'Requirements', 1001, 1002]);

        $search = new RetrieveArtidocSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory());

        $paginated_retrieved_sections = $search->searchPaginatedRetrievedSections($artidoc, 1, 0);
        self::assertCount(1, $paginated_retrieved_sections->rows);
        self::assertTrue(Result::isOk($paginated_retrieved_sections->rows[0]->content->apply(
            static fn () => Result::err(Fault::fromMessage('Should get freetext, not an artifact section')),
            static function (RetrievedSectionContentFreetext $freetext) use ($artidoc, $ids) {
                $dao = new UpdateFreetextContentDao(new UpdateLevelDao());

                $dao->updateFreetextContent($ids[0], $freetext->id, new FreetextContent('Introduction', '', Level::One));

                SectionsAsserter::assertSectionsForDocument($artidoc, ['Introduction', 'Requirements', 1001, 1002]);

                return Result::ok(null);
            }
        )));
    }

    /**
     * @return list<SectionIdentifier>
     */
    private function createArtidocSections(ArtidocWithContext $artidoc, array $content): array
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

        $ids = [];
        foreach ($content as $content_to_insert) {
            $dao->saveSectionAtTheEnd($artidoc, $content_to_insert)
                ->andThen(static function (SectionIdentifier $id) use (&$ids) {
                    $ids[] = $id;

                    return Result::ok($id);
                });
        }

        return $ids;
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
