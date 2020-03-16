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

/**
 * For graphs which have a large number of plots (say 10 items) it might be necessary
 * to specify 2 (or more columns) to avoid that the legend box become too large and hide the image.
 * Legend ratio is computed in order to specify the number of text columns to use in the legend text box.
 */
class Widget_ProjectSvnStats_Layout
{

    /**
     * The number of lgend plots
     */
    private $nb_committer;

    /**
     * Computed legend ratio
     */
    public $legend_ratio;

    /**
     * Constructor of the class
     *
     * @param int $nb_committer The number of lgend plots
     *
     * @return Void
     */
    public function __construct($nb_committer)
    {
        $this->nb_committer = $nb_committer;
        $this->legend_ratio = $nb_committer / 10;
    }

    /**
     * Adjust the chart width to fit legend ratio
     *
     * @return bool
     */
    public function getChartWidth()
    {
        return $this->hasOnlyOneColumn() ? 400 : 550;
    }

    /**
     * Adjust the chart heigh to fit legend ratio
     *
     * @return bool
     */
    public function getChartHeigh()
    {
        return $this->hasOnlyOneColumn() ? 300 + 16 * $this->nb_committer * (1 / $this->legend_ratio) : 300 + (16 + $this->legend_ratio) * $this->nb_committer;
    }

    /**
     * Specify marging between the image bottom and the legend top
     * according to computed legend ratio(less than 10, between 10 and 20 or more than 20 legend items, etc...).
     *
     * @return int
     */
    private function getCustomImageMargin()
    {
        return $this->hasMoreThanTwoColumns() ? 80 + (16 - $this->legend_ratio) * $this->nb_committer : 125 + 15 * (1 / $this->legend_ratio) * $this->nb_committer;
    }

    /**
     * Check if committers number take one legend column
     *
     * @return bool
     */
    private function hasOnlyOneColumn()
    {
        return $this->legend_ratio < 1;
    }

    /**
     * Legend default X position given as fraction
     *
     * @return Float
     */
    public function getLegendXPosition()
    {
        return $this->hasOnlyOneColumn() ? 0.1 : 0.05;
    }

    /**
     * Legend default Y position given as fraction
     *
     * @return Float
     */
    public function getLegendYPosition()
    {
        if ($this->hasMoreThanTwoColumns()) {
            $legend_y_position = 0.6;
        } elseif ($this->hasOnlyOneColumn()) {
            $legend_y_position = 0.99;
        } else {
            $legend_y_position = 0.645;
        }
        return $legend_y_position;
    }

    /**
     * Customise image bottom margin according to commiters number.
     *
     * @return Float
     */
    public function getImageBottomMargin()
    {
        $customImageMargin = $this->getCustomImageMargin();
        return $this->hasOnlyOneColumn() ? 100 + 18 * $this->nb_committer : $customImageMargin;
    }

    /**
     * Align legend according to commiters number, this should take in consideration X and Y positions of the legend
     *
     * @return String
     */
    public function getLegendAlign()
    {
        return $this->hasOnlyOneColumn() ? 'bottom' : 'top';
    }

    /**
     * Check if committers number take more than two legend columns
     *
     * @return bool
     */
    private function hasMoreThanTwoColumns()
    {
        return $this->legend_ratio > 2;
    }
}
