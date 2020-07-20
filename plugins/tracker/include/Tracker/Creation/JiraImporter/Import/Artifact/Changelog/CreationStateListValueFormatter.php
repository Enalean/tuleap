<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

class CreationStateListValueFormatter
{
    /**
     * @return array{id: string}|non-empty-list<array{id: string}>
     */
    public function formatListValue(string $changed_field_from): array
    {
        if (strpos($changed_field_from, '[') === 0) {
            $formatted_value = $this->formatMultiListValues($changed_field_from);
        } else {
            $formatted_value = $this->formatSimpleListValues($changed_field_from);
        }

        return $formatted_value;
    }

    /**
     * @param int[] $user_ids
     * @return non-empty-list<array{id: string}>
     */
    public function formatMultiUserListValues(array $user_ids): array
    {
        return array_map(
            function (int $user_id) {
                return $this->formatValueAsArray((string) $user_id);
            },
            $user_ids
        );
    }

    /**
     * @return non-empty-list<array{id: string}>
     */
    private function formatMultiListValues(string $changed_field_from): array
    {
        $all_values       = substr($changed_field_from, 1, -1);
        $formatted_values = [];
        foreach (explode(',', $all_values) as $value) {
            $formatted_values[] = $this->formatValueAsArray($value);
        }

        return $formatted_values;
    }

    /**
     * @return array{id: string}
     */
    private function formatSimpleListValues(string $value): array
    {
        return $this->formatValueAsArray($value);
    }

    /**
     * @return array{id: string}
     */
    private function formatValueAsArray(string $value): array
    {
        return [
            'id' => trim($value)
        ];
    }
}
