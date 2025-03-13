<?php
/*
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

namespace Tuleap\Tracker\FormElement\FieldSpecificProperties;

final class FieldPropertiesRetriever
{
    public function __construct(private ?SearchSpecificProperties $dao)
    {
    }

    public function getProperties(?array $cache, array $default_properties, int $field_id): array
    {
        if ($cache !== null) {
            return $cache;
        }

        if ($this->dao === null) {
            return $default_properties;
        }

        $cache = $default_properties;
        $row   = $this->dao->searchByFieldId($field_id);
        if ($row === null) {
            return $cache;
        }

        foreach ($row as $key => $value) {
            $this->setPropertyValue($cache, $key, $value);
        }

        return $cache;
    }

    /**
     * Look for a suitable property and set its value
     */
    private function setPropertyValue(array &$properties, string $key, mixed $value): void
    {
        if ($key === 'field_id') {
            return;
        }

        if (isset($properties[$key])) {
            $properties[$key]['value'] = $value;
        } else {
            foreach ($properties as $k => $v) {
                if ($v['type'] === 'radio') {
                    $this->setPropertyValue($properties[$k]['choices'], $key, $value);
                }
            }
        }
    }
}
