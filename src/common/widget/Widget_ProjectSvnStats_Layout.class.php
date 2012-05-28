<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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
    class Widget_ProjectSvnStats_Layout {

    public function __construct($nb_committer) {
        $this->nb_committer = $nb_committer;
        $this->legend_ratio = $nb_committer / 10;
    }

    public function getChartWidth() {
        return $this->hasOnlyOneColumn() ? 400 : 550;
    }

    public function getChartHeigh() {
        ($this->legend_ratio < 1)? $chartHeigh  = 300+16*$this->nb_committer*(1/$this->legend_ratio) : $chartHeigh  = 300+(16+$this->legend_ratio)*$this->nb_committer;
        return $chartHeigh;
    }

    private function getCustomImageMargin() {
        return $this->hasMoreThanTwoColumns() ? $customImageMargin = 80+(16-$this->legend_ratio+1)*$this->nb_committer : $customImageMargin = 100+18*$this->nb_committer;
    }

    private function hasOnlyOneColumn() {
        return $this->legend_ratio < 1;
    }

    public function getLegendXPosition() {
        return ($this->legend_ratio < 1)? $legend_x_position = 0.1 : $legend_x_position = 0.01;
    }

    public function getLegendYPosition() {
        return ($this->legend_ratio < 1)? $legend_y_position = 0.99 : $legend_y_position = 0.5;
    }

    public function getImageBottomMargin() {
        $customImageMargin = $this->getCustomImageMargin();
        return ($this->legend_ratio < 1)? $imgBottomMargin = 100+16*$this->nb_committer*(1/$this->legend_ratio) : $imgBottomMargin = $customImageMargin;
    }

    public function getLegendAlign() {
        return ($this->legend_ratio < 1)? $legendAlign = 'bottom' : $legendAlign = 'top';
    }

    private function hasMoreThanTwoColumns() {
        return $this->legend_ratio > 2;
    }

    }
?>