<?php

/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * Provide statistics with a data set.
 */
class StatisticsManager {

    const FIRST_QUARTILE    = 0;
    const MEDIAN_QUARTILE   = 1;
    const THIRD_QUARTILE    = 2;

    var $datas;

    /**
     * Initialize a StatisticsManager instance to manage datas given as parameter.
     * @param type $datas Datas to manage
     */
    public function __construct($datas) {
        $this->datas = $datas;
        sort($this->datas, SORT_NUMERIC);
    }

    /**
     * @return array Return the quartiles of the datas list.
     */
    public function getQuartiles() {
        $datas_size         = count($this->datas);
        $quartiles_array    = array();

        foreach ($this->getQuartilesIndexes($datas_size) as $quartile_index) {
            $quartiles_array[] = $this->datas[$quartile_index];
        }
        return $quartiles_array;
    }

    /**
     * @return array Return the normal distribution for each value of the datas list.
     */
    public function getNormalDistribution() {
        $normal_distribution = array();
        foreach ($this->datas as $data) {
            $this->incrementCellContent($normal_distribution, $data);
        }
        return $normal_distribution;
    }

    private function incrementCellContent(& $array, $index) {
        if (array_key_exists($array, $index)) {
            $array[$index]++;
        } else {
            $array[$index] = 1;
        }
    }

    private function getQuartilesIndexes($array_size) {
        $array_size--;
        return array(
            ceil($array_size / 4),
            ceil($array_size / 2),
            ceil($array_size * 3 / 4)
        );
    }

}

?>