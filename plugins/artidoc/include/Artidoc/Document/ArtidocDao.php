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
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\DB\DataAccessObject;

final class ArtidocDao extends DataAccessObject implements SearchConfiguredTracker, SaveConfiguredTracker
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
                'SELECT artifact_id, freetext_id, `rank`, level
                FROM plugin_artidoc_section AS section
                    INNER JOIN plugin_artidoc_section_version AS section_version
                        ON (section.id = section_version.section_id)
                WHERE item_id = ?',
                $source_id
            );

            foreach ($rows as $row) {
                if ($row['artifact_id'] !== null) {
                    $section_id = $this->section_identifier_factory->buildIdentifier()->getBytes();
                    $db->insert(
                        'plugin_artidoc_section',
                        [
                            'id'      => $section_id,
                            'item_id' => $target_id,
                        ]
                    );
                    $db->insert(
                        'plugin_artidoc_section_version',
                        [
                            'section_id'  => $section_id,
                            'artifact_id' => $row['artifact_id'],
                            'freetext_id' => null,
                            'rank'        => $row['rank'],
                            'level'       =>  $row['level'],
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
                    $section_id = $this->section_identifier_factory->buildIdentifier()->getBytes();
                    $db->insert(
                        'plugin_artidoc_section',
                        [
                            'id'      => $section_id,
                            'item_id' => $target_id,
                        ]
                    );
                    $db->insert(
                        'plugin_artidoc_section_version',
                        [
                            'section_id'  => $section_id,
                            'artifact_id' => null,
                            'freetext_id' => $freetext_id,
                            'rank'        => $row['rank'],
                            'level'       =>  $row['level'],
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

    public function deleteSectionsByArtifactId(int $artifact_id): void
    {
        $this->getDB()->run(
            <<<EOS
            DELETE section, section_version
            FROM plugin_artidoc_section AS section
                INNER JOIN plugin_artidoc_section_version AS section_version
                    ON (section.id = section_version.section_id)
            WHERE artifact_id = ?
            EOS,
            $artifact_id,
        );
    }
}
