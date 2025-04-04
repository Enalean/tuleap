<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field;

use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\ArtifactSectionField;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\DB\DataAccessObject;

final class ConfiguredFieldDao extends DataAccessObject implements RetrieveConfiguredField
{
    public function retrieveConfiguredFieldsFromItemId(int $item_id): array
    {
        return array_values(
            array_map(
                self::mapToField(...),
                $this->getDB()->run(
                    <<<EOS
                    SELECT field_id, display_type
                    FROM plugin_artidoc_document_tracker_field
                    WHERE item_id = ?
                    ORDER BY `rank`
                    EOS,
                    $item_id,
                ),
            ),
        );
    }

    public function retrieveConfiguredFieldsFromSectionId(SectionIdentifier $section_identifier): array
    {
        return array_values(
            array_map(
                self::mapToField(...),
                $this->getDB()->run(
                    <<<EOS
                    SELECT field_id, display_type
                    FROM plugin_artidoc_document_tracker_field
                        INNER JOIN plugin_artidoc_section USING (item_id)
                    WHERE id = ?
                    ORDER BY `rank`
                    EOS,
                    $section_identifier->getBytes(),
                ),
            ),
        );
    }

    public function deleteConfiguredFieldById(int $field_id): void
    {
        $this->getDB()->delete(
            'plugin_artidoc_document_tracker_field',
            [
                'field_id' => $field_id,
            ]
        );
    }

    /**
     * @param array{field_id: int, display_type: string} $row
     */
    private static function mapToField(array $row): ArtifactSectionField
    {
        $display_type = DisplayType::tryFrom($row['display_type']) ?? DisplayType::COLUMN;
        return new ArtifactSectionField($row['field_id'], $display_type);
    }
}
