<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\FieldSpecificProperties;

use Tuleap\DB\DataAccessObject;

final class ArtifactLinkFieldSpecificPropertiesDAO extends DataAccessObject implements SearchSpecificProperties, SaveSpecificFieldProperties, DuplicateSpecificProperties
{
    /**
     * @return null | array{field_id: int, can_edit_reverse_links: 0|1}
     */
    public function searchByFieldId(int $field_id): ?array
    {
        $sql = 'SELECT field_id, can_edit_reverse_links FROM plugin_tracker_field_artifact_link WHERE field_id = ?';

        return $this->getDB()->row($sql, $field_id);
    }

    public function countNumberOfTrackersWithoutTheFeature(): int
    {
        return $this->getDB()->cell(
            <<<EOS
            SELECT COUNT(DISTINCT tracker.id)
            FROM tracker_field
                INNER JOIN tracker ON (tracker_field.tracker_id = tracker.id AND tracker.deletion_date IS NULL)
                INNER JOIN `groups` AS project ON (tracker.group_id = project.group_id AND project.status = 'A')
                LEFT JOIN plugin_tracker_field_artifact_link
                    ON (tracker_field.id = plugin_tracker_field_artifact_link.field_id)
            WHERE (can_edit_reverse_links IS NULL OR can_edit_reverse_links = 0) AND formElement_type = 'art_link'
            EOS
        );
    }

    public function massActivateForActiveTrackers(): void
    {
        $this->getDB()->cell(
            <<<EOS
            INSERT INTO plugin_tracker_field_artifact_link (field_id, can_edit_reverse_links)
            SELECT DISTINCT tracker_field.id, 1
            FROM tracker_field
                INNER JOIN tracker ON (tracker_field.tracker_id = tracker.id AND tracker.deletion_date IS NULL)
                INNER JOIN `groups` AS project ON (tracker.group_id = project.group_id AND project.status = 'A')
                LEFT JOIN plugin_tracker_field_artifact_link
                    ON (tracker_field.id = plugin_tracker_field_artifact_link.field_id)
            WHERE (can_edit_reverse_links IS NULL OR can_edit_reverse_links = 0) AND formElement_type = 'art_link'
            ON DUPLICATE KEY UPDATE can_edit_reverse_links = 1
            EOS
        );
    }

    public function saveSpecificProperties(int $field_id, array $row): void
    {
        $can_edit_reverse_links = $row['can_edit_reverse_links'] ?? 0;

        $sql = 'REPLACE INTO plugin_tracker_field_artifact_link (field_id, can_edit_reverse_links) VALUES (?, ?)';
        $this->getDB()->run($sql, $field_id, $can_edit_reverse_links);
    }

    public function duplicate(int $from_field_id, int $to_field_id): void
    {
        $sql = 'REPLACE INTO plugin_tracker_field_artifact_link (field_id, can_edit_reverse_links)
                SELECT ?, can_edit_reverse_links
                FROM plugin_tracker_field_artifact_link
                WHERE field_id = ?';
        $this->getDB()->run($sql, $to_field_id, $from_field_id);
    }
}
