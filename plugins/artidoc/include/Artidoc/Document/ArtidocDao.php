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

use ParagonIE\EasyDB\EasyDB;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Domain\Document\Order\Direction;
use Tuleap\Artidoc\Domain\Document\Order\ReorderSections;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
use Tuleap\Artidoc\Domain\Document\Section\AlreadyExistingSectionWithSameArtifactException;
use Tuleap\Artidoc\Domain\Document\Section\DeleteOneSection;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\RawSection;
use Tuleap\Artidoc\Domain\Document\Section\SearchOneSection;
use Tuleap\Artidoc\Domain\Document\Section\UnableToFindSiblingSectionException;
use Tuleap\DB\DataAccessObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class ArtidocDao extends DataAccessObject implements SearchArtidocDocument, SearchOneSection, DeleteOneSection, SearchPaginatedRawSections, SaveSections, SaveOneSection, SearchConfiguredTracker, SaveConfiguredTracker, ReorderSections
{
    public function __construct(private readonly SectionIdentifierFactory $identifier_factory)
    {
        parent::__construct();
    }

    public function searchByItemId(int $item_id): ?array
    {
        return $this->getDB()->row(
            <<<EOS
            SELECT *
            FROM plugin_docman_item
            WHERE item_id = ?
              AND item_type = ?
              AND other_type = ?
              AND delete_date IS NULL
            EOS,
            $item_id,
            \Docman_Item::TYPE_OTHER,
            ArtidocDocument::TYPE,
        );
    }

    public function searchSectionById(SectionIdentifier $section_id): Ok|Err
    {
        $row = $this->getDB()->row(
            <<<EOS
            SELECT *
            FROM plugin_artidoc_document
            WHERE id = ?
            EOS,
            $section_id->getBytes(),
        );

        if ($row === null) {
            return Result::err(Fault::fromMessage('Unable to find section'));
        }

        $row['id'] = $section_id;

        return Result::ok(RawSection::fromRow($row));
    }

    public function searchPaginatedRawSectionsByItemId(int $item_id, int $limit, int $offset): PaginatedRawSections
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($item_id, $limit, $offset) {
            $rows = $db->run(
                <<<EOS
                SELECT id, artifact_id, item_id, `rank`
                FROM plugin_artidoc_document
                WHERE item_id = ?
                ORDER BY `rank`
                LIMIT ? OFFSET ?
                EOS,
                $item_id,
                $limit,
                $offset,
            );

            $total = $db->cell('SELECT COUNT(*) FROM plugin_artidoc_document WHERE item_id = ?', $item_id);

            return new PaginatedRawSections(
                $item_id,
                array_values(
                    array_map(
                        /**
                         * @param array{ id: string, item_id: int, artifact_id: int, rank: int } $row
                         */
                        function (array $row): RawSection {
                            $row['id'] = $this->identifier_factory->buildFromBytesData($row['id']);

                            return RawSection::fromRow($row);
                        },
                        $rows,
                    ),
                ),
                $total,
            );
        });
    }

    public function cloneItem(int $source_id, int $target_id): void
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($source_id, $target_id) {
            $rows = $db->run(
                'SELECT artifact_id, `rank`
                FROM plugin_artidoc_document
                WHERE item_id = ?',
                $source_id
            );

            if (count($rows) !== 0) {
                $db->insertMany(
                    'plugin_artidoc_document',
                    array_map(
                        function (array $row) use ($target_id) {
                            return [
                                'id' => $this->identifier_factory->buildIdentifier()->getBytes(),
                                'item_id' => $target_id,
                                'artifact_id' => $row['artifact_id'],
                                'rank' => $row['rank'],
                            ];
                        },
                        $rows,
                    )
                );
            }

            $db->run('DELETE FROM plugin_artidoc_document_tracker WHERE item_id = ?', $target_id);
            $db->run(
                'INSERT INTO plugin_artidoc_document_tracker (item_id, tracker_id)
                SELECT ?, tracker_id
                FROM plugin_artidoc_document_tracker
                WHERE item_id = ?',
                $target_id,
                $source_id
            );
        });
    }

    public function save(int $item_id, array $artifact_ids): void
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($item_id, $artifact_ids) {
            $db->run('DELETE FROM plugin_artidoc_document WHERE item_id = ?', $item_id);

            if (count($artifact_ids) > 0) {
                $rank = 0;
                $db->insertMany(
                    'plugin_artidoc_document',
                    array_map(
                        function ($artifact_id) use ($item_id, &$rank) {
                            return [
                                'id'          => $this->identifier_factory->buildIdentifier()->getBytes(),
                                'item_id'     => $item_id,
                                'artifact_id' => $artifact_id,
                                'rank'        => $rank++,
                            ];
                        },
                        $artifact_ids,
                    ),
                );
            }
        });
    }

    public function getTracker(int $item_id): ?int
    {
        return $this->getDB()->cell(
            'SELECT tracker_id
            FROM plugin_artidoc_document_tracker
            WHERE item_id = ?',
            $item_id,
        ) ?: null;
    }

    public function saveTracker(int $item_id, int $tracker_id): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_artidoc_document_tracker',
            [
                'item_id'    => $item_id,
                'tracker_id' => $tracker_id,
            ],
            [
                'tracker_id',
            ]
        );
    }

    public function saveSectionAtTheEnd(int $item_id, int $artifact_id): SectionIdentifier
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($item_id, $artifact_id) {
            $rank = $this->getDB()->cell(
                'SELECT max(`rank`) + 1 FROM plugin_artidoc_document WHERE item_id = ?',
                $item_id,
            ) ?: 0;

            return $this->insertSection($db, $item_id, $artifact_id, $rank);
        });
    }

    public function saveSectionBefore(int $item_id, int $artifact_id, SectionIdentifier $sibling_section_id): SectionIdentifier
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($item_id, $artifact_id, $sibling_section_id) {
            $rank = $this->getDB()->cell(
                'SELECT `rank` FROM plugin_artidoc_document WHERE id = ? AND item_id = ?',
                $sibling_section_id->getBytes(),
                $item_id,
            );

            if ($rank === false) {
                throw new UnableToFindSiblingSectionException();
            }

            $db->run(
                <<<EOS
                UPDATE plugin_artidoc_document
                SET `rank` = `rank` + 1
                WHERE item_id = ? AND `rank` >= ?
                EOS,
                $item_id,
                $rank,
            );

            return $this->insertSection($db, $item_id, $artifact_id, $rank);
        });
    }

    /**
     * @throws AlreadyExistingSectionWithSameArtifactException
     */
    private function insertSection(EasyDB $db, int $item_id, int $artifact_id, int $rank): SectionIdentifier
    {
        if (
            $db->cell(
                <<<EOL
                SELECT id
                FROM plugin_artidoc_document
                WHERE item_id = ? AND artifact_id = ?
                EOL,
                $item_id,
                $artifact_id,
            )
        ) {
            throw new AlreadyExistingSectionWithSameArtifactException();
        }

        $id = $this->identifier_factory->buildIdentifier();
        $db->insert(
            'plugin_artidoc_document',
            [
                'id'          => $id->getBytes(),
                'item_id'     => $item_id,
                'artifact_id' => $artifact_id,
                'rank'        => $rank,
            ],
        );

        return $id;
    }

    public function deleteSectionsByArtifactId(int $artifact_id): void
    {
        $this->getDB()->delete(
            'plugin_artidoc_document',
            [
                'artifact_id' => $artifact_id,
            ]
        );
    }

    public function deleteSectionById(SectionIdentifier $section_id): void
    {
        $this->getDB()->delete(
            'plugin_artidoc_document',
            [
                'id' => $section_id->getBytes(),
            ]
        );
    }

    public function reorder(int $item_id, SectionOrder $order): Ok|Err
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($item_id, $order): Ok|Err {
            $current_order = array_values($db->col(
                'SELECT id
                FROM plugin_artidoc_document
                WHERE item_id = ?
                ORDER BY `rank`',
                0,
                $item_id,
            ));

            $index_to_move = array_search($order->identifier->getBytes(), $current_order, true);
            if ($index_to_move === false) {
                return Result::err(UnknownSectionToMoveFault::build());
            }

            array_splice($current_order, $index_to_move, 1);

            $index_compared_to = array_search($order->compared_to->getBytes(), $current_order, true);
            if ($index_compared_to === false) {
                return Result::err(UnableToReorderSectionOutsideOfDocumentFault::build());
            }
            if ($order->direction === Direction::Before) {
                if ($index_compared_to === 0) {
                    array_unshift($current_order, $order->identifier->getBytes());
                } else {
                    array_splice($current_order, $index_compared_to, 0, [$order->identifier->getBytes()]);
                }
            } else {
                if ($index_compared_to === count($current_order) - 1) {
                    $current_order[] = $order->identifier->getBytes();
                } else {
                    array_splice($current_order, $index_compared_to + 1, 0, [$order->identifier->getBytes()]);
                }
            }

            $when   = '';
            $values = [];
            foreach ($current_order as $index => $value) {
                $when    .= ' WHEN id = ? THEN ? ';
                $values[] = $value;
                $values[] = $index;
            }

            $sql = <<<EOS
                UPDATE plugin_artidoc_document
                SET `rank` = CASE $when ELSE `rank` END
                WHERE item_id = ?
                EOS;
            $db->safeQuery($sql, [...$values, $item_id]);

            return Result::ok(null);
        });
    }
}
