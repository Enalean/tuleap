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
use Tuleap\Artidoc\Domain\Document\Section\AlreadyExistingSectionWithSameArtifactException;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\SaveOneSection;
use Tuleap\Artidoc\Domain\Document\Section\UnableToFindSiblingSectionException;
use Tuleap\DB\DataAccessObject;

final class SaveSectionDao extends DataAccessObject implements SaveOneSection
{
    public function __construct(
        private readonly SectionIdentifierFactory $section_identifier_factory,
        private readonly FreetextIdentifierFactory $freetext_identifier_factory,
    ) {
        parent::__construct();
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
}
