<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\View\Admin;

final class ValueRanksBuilder
{
    /**
     * @return array{value: int, label: string}[]
     */
    public function getRanks(\Docman_ListMetadata $metadata): array
    {
        $ranks = [];
        foreach ($metadata->getListOfValueIterator() as $value) {
            assert($value instanceof \Docman_MetadataListOfValuesElement);
            if (! in_array($value->getStatus(), ['A', 'P'], true)) {
                continue;
            }

            $ranks[] = [
                'value' => (int) $value->getRank() + 1,
                'label' => sprintf(
                    dgettext('tuleap-docman', 'After %s'),
                    (int) $value->getId() === 100
                        ? dgettext('tuleap-docman', 'None')
                        : $value->getName()
                ),
            ];
        }

        return $ranks;
    }
}
