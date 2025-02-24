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
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRetrievedSections;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\Artidoc\Domain\Document\Section\SearchAllSections;
use Tuleap\Artidoc\Domain\Document\Section\SearchOneSection;
use Tuleap\Artidoc\Domain\Document\Section\SearchPaginatedRetrievedSections;
use Tuleap\DB\DataAccessObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class RetrieveArtidocSectionDao extends DataAccessObject implements SearchOneSection, SearchPaginatedRetrievedSections, SearchAllSections
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
                   section_version.artifact_id,
                   freetext.id AS freetext_id,
                   freetext.title AS freetext_title,
                   freetext.description AS freetext_description,
                   section_version.`rank`,
                   section_version.level
            FROM plugin_artidoc_section AS section
                INNER JOIN plugin_artidoc_section_version AS section_version
                    ON (section.id = section_version.section_id)
                LEFT JOIN plugin_artidoc_section_freetext AS freetext
                    ON (section_version.freetext_id = freetext.id)
            WHERE section.id = ?
            EOS,
            $section_id->getBytes(),
        );

        if ($row === null) {
            return Result::err(Fault::fromMessage('Unable to find section'));
        }

        $row['id'] = $section_id;

        return Result::ok($this->instantiateRetrievedSection($row));
    }

    /**
     * @param array{ id: SectionIdentifier, item_id: int, artifact_id: int|null, freetext_id: int|null, freetext_title: string|null, freetext_description: string|null, rank: int, level: int } $row
     */
    private function instantiateRetrievedSection(array $row): RetrievedSection
    {
        if ($row['artifact_id'] !== null) {
            return RetrievedSection::fromArtifact($row);
        }

        if ($row['freetext_id'] !== null) {
            $row['freetext_id'] = $this->freetext_identifier_factory->buildFromBytesData((string) $row['freetext_id']);

            $row['freetext_title']       = (string) $row['freetext_title'];
            $row['freetext_description'] = (string) $row['freetext_description'];

            return RetrievedSection::fromFreetext($row);
        }

        throw new \LogicException('Section is neither an artifact nor a freetext, this is not expected');
    }

    public function searchPaginatedRetrievedSections(ArtidocWithContext $artidoc, int $limit, int $offset): PaginatedRetrievedSections
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($artidoc, $limit, $offset) {
            $item_id = $artidoc->document->getId();

            $rows = $db->run(
                <<<EOS
                SELECT section.id,
                   section.item_id,
                   section_version.artifact_id,
                   freetext.id AS freetext_id,
                   freetext.title AS freetext_title,
                   freetext.description AS freetext_description,
                   section_version.`rank`,
                   section_version.level
                FROM plugin_artidoc_section AS section
                    INNER JOIN plugin_artidoc_section_version AS section_version
                        ON (section.id = section_version.section_id)
                    LEFT JOIN plugin_artidoc_section_freetext AS freetext
                        ON (section_version.freetext_id = freetext.id)
                WHERE section.item_id = ?
                ORDER BY section_version.`rank`
                LIMIT ? OFFSET ?
                EOS,
                $item_id,
                $limit,
                $offset,
            );

            $total = $db->cell(
                <<<EOS
                SELECT COUNT(*)
                FROM plugin_artidoc_section AS section
                    INNER JOIN plugin_artidoc_section_version AS section_version
                        ON (section.id = section_version.section_id)
                WHERE item_id = ?
                EOS,
                $item_id,
            );

            return new PaginatedRetrievedSections(
                $artidoc,
                array_values(
                    array_map(
                        /**
                         * @param array{ id: string, item_id: int, artifact_id: int|null, freetext_id: int|null, freetext_title: string|null, freetext_description: string|null, rank: int, level: int } $row
                         */
                        function (array $row): RetrievedSection {
                            $row['id'] = $this->section_identifier_factory->buildFromBytesData($row['id']);

                            return $this->instantiateRetrievedSection($row);
                        },
                        $rows,
                    ),
                ),
                $total,
            );
        });
    }

    /**
     * @return list<RetrievedSection>
     */
    public function searchAllSectionsOfDocument(ArtidocWithContext $artidoc): array
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($artidoc) {
            $item_id = $artidoc->document->getId();

            $rows = $db->run(
                <<<EOS
                SELECT section.id,
                   section.item_id,
                   section_version.artifact_id,
                   freetext.id AS freetext_id,
                   freetext.title AS freetext_title,
                   freetext.description AS freetext_description,
                   section_version.`rank`,
                   section_version.level
                FROM plugin_artidoc_section AS section
                    INNER JOIN plugin_artidoc_section_version AS section_version
                        ON (section.id = section_version.section_id)
                    LEFT JOIN plugin_artidoc_section_freetext AS freetext
                        ON (section_version.freetext_id = freetext.id)
                WHERE section.item_id = ?
                ORDER BY section_version.`rank`
                EOS,
                $item_id,
            );

            return array_values(
                array_map(
                    /**
                     * @param array{ id: string, item_id: int, artifact_id: int|null, freetext_id: int|null, freetext_title: string|null, freetext_description: string|null, rank: int, level: int } $row
                     */
                    function (array $row): RetrievedSection {
                        $row['id'] = $this->section_identifier_factory->buildFromBytesData($row['id']);

                        return $this->instantiateRetrievedSection($row);
                    },
                    $rows,
                ),
            );
        });
    }
}
