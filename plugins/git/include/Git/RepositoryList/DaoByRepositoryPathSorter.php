<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\RepositoryList;

class DaoByRepositoryPathSorter
{
    /**
     * @param array $repository_list_results
     *
     * @return array
     */
    public function sort(array $repository_list_results)
    {
        $results_to_sort = $repository_list_results;
        usort(
            $results_to_sort,
            function ($a, $b) {
                $repository_name_a = $a['repository_name'];
                $repository_name_b = $b['repository_name'];

                return $this->compareRepositoryName($repository_name_a, $repository_name_b);
            }
        );

        return $results_to_sort;
    }

    /**
     * @param $repository_name_a
     * @param $repository_name_b
     *
     * @return int
     */
    private function compareRepositoryName($repository_name_a, $repository_name_b)
    {
        $a_pos = strpos($repository_name_a, "/");
        $b_pos = strpos($repository_name_b, "/");

        if ($a_pos === false && $b_pos === false) {
            return strnatcasecmp($repository_name_a, $repository_name_b);
        }

        if ($a_pos === false) {
            return 1;
        }

        if ($b_pos === false) {
            return -1;
        }

        $a_root = substr($repository_name_a, 0, $a_pos);
        $b_root = substr($repository_name_b, 0, $b_pos);

        if ($a_root === $b_root) {
            return $this->compareRepositoryName(
                substr($repository_name_a, $a_pos + 1),
                substr($repository_name_b, $b_pos + 1)
            );
        }

        return strnatcasecmp($a_root, $b_root);
    }
}
