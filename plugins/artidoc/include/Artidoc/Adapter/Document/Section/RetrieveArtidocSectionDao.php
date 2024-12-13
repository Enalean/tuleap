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

use ParagonIE\EasyDB\EasyDB;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRawSections;
use Tuleap\Artidoc\Domain\Document\Section\RawSection;
use Tuleap\Artidoc\Domain\Document\Section\SearchOneSection;
use Tuleap\Artidoc\Domain\Document\Section\SearchPaginatedRawSections;
use Tuleap\DB\DataAccessObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class RetrieveArtidocSectionDao extends DataAccessObject implements SearchOneSection, SearchPaginatedRawSections
{
    public function __construct(
        private readonly SectionIdentifierFactory $section_identifier_factory,
        private readonly FreetextIdentifierFactory $freetext_identifier_factory,
    ) {
        parent::__construct();
    }

    public function searchSectionById(SectionIdentifier $section_id): Ok|Err
    {
        $row = $this->getDB()->row(
            <<<EOS
            SELECT section.id,
                   section.item_id,
                   section.artifact_id,
                   freetext.id AS freetext_id,
                   freetext.title AS freetext_title,
                   freetext.description AS freetext_description,
                   section.`rank`
            FROM plugin_artidoc_document AS section
                LEFT JOIN plugin_artidoc_section_freetext AS freetext
                    ON (section.freetext_id = freetext.id)
            WHERE section.id = ?
            EOS,
            $section_id->getBytes(),
        );

        if ($row === null) {
            return Result::err(Fault::fromMessage('Unable to find section'));
        }

        $row['id'] = $section_id;

        return Result::ok($this->instantiateRawSection($row));
    }

    /**
     * @param array{ id: SectionIdentifier, item_id: int, artifact_id: int|null, freetext_id: int|null, freetext_title: string|null, freetext_description: string|null, rank: int } $row
     */
    private function instantiateRawSection(array $row): RawSection
    {
        if ($row['artifact_id'] !== null) {
            return RawSection::fromArtifact($row);
        }

        if ($row['freetext_id'] !== null) {
            $row['freetext_id'] = $this->freetext_identifier_factory->buildFromBytesData((string) $row['freetext_id']);

            $row['freetext_title']       = (string) $row['freetext_title'];
            $row['freetext_description'] = (string) $row['freetext_description'];

            return RawSection::fromFreetext($row);
        }

        throw new \LogicException('Section is neither an artifact nor a freetext, this is not expected');
    }

    public function searchPaginatedRawSections(ArtidocWithContext $artidoc, int $limit, int $offset): PaginatedRawSections
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($artidoc, $limit, $offset) {
            $item_id = $artidoc->document->getId();

            $rows = $db->run(
                <<<EOS
                SELECT section.id,
                   section.item_id,
                   section.artifact_id,
                   freetext.id AS freetext_id,
                   freetext.title AS freetext_title,
                   freetext.description AS freetext_description,
                   section.`rank`
                FROM plugin_artidoc_document AS section
                LEFT JOIN plugin_artidoc_section_freetext AS freetext
                    ON (section.freetext_id = freetext.id)
                WHERE section.item_id = ?
                ORDER BY section.`rank`
                LIMIT ? OFFSET ?
                EOS,
                $item_id,
                $limit,
                $offset,
            );

            $total = $db->cell('SELECT COUNT(*) FROM plugin_artidoc_document WHERE item_id = ?', $item_id);

            return new PaginatedRawSections(
                $artidoc,
                array_values(
                    array_map(
                        /**
                         * @param array{ id: string, item_id: int, artifact_id: int|null, freetext_id: int|null, freetext_title: string|null, freetext_description: string|null, rank: int } $row
                         */
                        function (array $row): RawSection {
                            $row['id'] = $this->section_identifier_factory->buildFromBytesData($row['id']);

                            return $this->instantiateRawSection($row);
                        },
                        $rows,
                    ),
                ),
                $total,
            );
        });
    }
}
