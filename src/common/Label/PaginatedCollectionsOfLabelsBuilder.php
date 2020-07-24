<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Label;

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class PaginatedCollectionsOfLabelsBuilder
{
    public function build(LegacyDataAccessResultInterface $result, $total_size)
    {
        $labels = [];
        foreach ($result as $row) {
            $labels[] = new Label($row['id'], $row['name'], $row['is_outline'], $row['color']);
        }

        return new PaginatedCollectionsOfLabels($labels, $total_size);
    }
}
