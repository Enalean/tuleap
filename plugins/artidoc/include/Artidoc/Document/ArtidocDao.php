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
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Order\Direction;
use Tuleap\Artidoc\Domain\Document\Order\ReorderSections;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
use Tuleap\Artidoc\Domain\Document\Section\AlreadyExistingSectionWithSameArtifactException;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\SaveOneSection;
use Tuleap\Artidoc\Domain\Document\Section\UnableToFindSiblingSectionException;
use Tuleap\DB\DataAccessObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class ArtidocDao extends DataAccessObject implements SaveOneSection, SearchConfiguredTracker, SaveConfiguredTracker, ReorderSections
{
    public function __construct(
        private readonly SectionIdentifierFactory $section_identifier_factory,
        private readonly FreetextIdentifierFactory $freetext_identifier_factory,
    ) {
        parent::__construct();
    }

    public function cloneItem(int $source_id, int $target_id): void
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($source_id, $target_id) {
            $rows = $db->run(
                'SELECT artifact_id, freetext_id, `rank`
                FROM plugin_artidoc_document
                WHERE item_id = ?',
                $source_id
            );

            foreach ($rows as $row) {
                if ($row['artifact_id'] !== null) {
                    $db->insert(
                        'plugin_artidoc_document',
                        [
                            'id'          => $this->section_identifier_factory->buildIdentifier()->getBytes(),
                            'item_id'     => $target_id,
                            'artifact_id' => $row['artifact_id'],
                            'freetext_id' => null,
                            'rank'        => $row['rank'],
                        ]
                    );
                } elseif ($row['freetext_id'] !== null) {
                    $freetext    = $db->row(
                        'SELECT title, description FROM plugin_artidoc_section_freetext WHERE id = ?',
                        $row['freetext_id']
                    );
                    $freetext_id = $this->freetext_identifier_factory->buildIdentifier()->getBytes();
                    $db->insert(
                        'plugin_artidoc_section_freetext',
                        [
                            'id'          => $freetext_id,
                            'title'       => $freetext['title'],
                            'description' => $freetext['description'],
                        ]
                    );
                    $db->insert(
                        'plugin_artidoc_document',
                        [
                            'id'          => $this->section_identifier_factory->buildIdentifier()->getBytes(),
                            'item_id'     => $target_id,
                            'artifact_id' => null,
                            'freetext_id' => $freetext_id,
                            'rank'        => $row['rank'],
                        ]
                    );
                }
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

    public function saveSectionAtTheEnd(ArtidocWithContext $artidoc, ContentToInsert $content): SectionIdentifier
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($artidoc, $content) {
            $item_id = $artidoc->document->getId();

            $rank = $this->getDB()->cell(
                'SELECT max(`rank`) + 1 FROM plugin_artidoc_document WHERE item_id = ?',
                $item_id,
            ) ?: 0;

            return $this->insertSection($db, $item_id, $content, $rank);
        });
    }

    public function saveSectionBefore(ArtidocWithContext $artidoc, ContentToInsert $content, SectionIdentifier $sibling_section_id): SectionIdentifier
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($artidoc, $content, $sibling_section_id) {
            $item_id = $artidoc->document->getId();

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

            return $this->insertSection($db, $item_id, $content, $rank);
        });
    }

    /**
     * @throws AlreadyExistingSectionWithSameArtifactException
     */
    private function insertArtifactSection(EasyDB $db, int $item_id, int $artifact_id, int $rank): SectionIdentifier
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

        $id = $this->section_identifier_factory->buildIdentifier();
        $db->insert(
            'plugin_artidoc_document',
            [
                'id' => $id->getBytes(),
                'item_id' => $item_id,
                'artifact_id' => $artifact_id,
                'freetext_id' => null,
                'rank' => $rank,
            ],
        );

        return $id;
    }

    /**
     * @throws AlreadyExistingSectionWithSameArtifactException
     */
    private function insertSection(EasyDB $db, int $item_id, ContentToInsert $content, int $rank): SectionIdentifier
    {
        return $content->artifact_id
            ->match(
                fn (int $artifact_id) => $this->insertArtifactSection($db, $item_id, $artifact_id, $rank),
                fn () => $content->freetext->match(
                    fn (FreetextContent $content) => $this->insertFreetextSection($db, $item_id, $content, $rank),
                    static fn () => throw new \LogicException('Section is neither an artifact nor a freetext, this is not expected'),
                ),
            );
    }

    private function insertFreetextSection(EasyDB $db, int $item_id, FreetextContent $content, int $rank): SectionIdentifier
    {
        $freetext_id = $this->freetext_identifier_factory->buildIdentifier()->getBytes();
        $db->insert(
            'plugin_artidoc_section_freetext',
            [
                'id'          => $freetext_id,
                'title'       => $content->title,
                'description' => $content->description,
            ]
        );

        $id = $this->section_identifier_factory->buildIdentifier();
        $db->insert(
            'plugin_artidoc_document',
            [
                'id' => $id->getBytes(),
                'item_id' => $item_id,
                'artifact_id' => null,
                'freetext_id' => $freetext_id,
                'rank' => $rank,
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

    public function reorder(ArtidocWithContext $artidoc, SectionOrder $order): Ok|Err
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($artidoc, $order): Ok|Err {
            $current_order = array_values($db->col(
                'SELECT id
                FROM plugin_artidoc_document
                WHERE item_id = ?
                ORDER BY `rank`',
                0,
                $artidoc->document->getId(),
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
            $db->safeQuery($sql, [...$values, $artidoc->document->getId()]);

            return Result::ok(null);
        });
    }
}
