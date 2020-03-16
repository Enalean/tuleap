<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Chart\ColorsForCharts;

/**
* Chart_GanttBar
*
* Facade for jpgraph GanttBar
*
* @see jpgraph documentation for usage
*/
class Chart_GanttBar
{

    protected $jpgraph_instance;

    /**
    * Constructor
    *
    * @param int    $aPos          Vertical position (row)
    * @param string|string[] $aLabel        Text label
    * @param int    $aStart        Start date
    * @param int    $aEnd          End date
    * @param string $aCaption      Caption string for bar. Default is ""
    * @param float  $aHeightFactor Height factor. Default is 0.6
    *
    * @return void
    */
    public function __construct($aPos, $aLabel, $aStart, $aEnd, $aCaption = "", $aHeightFactor = 0.6)
    {
        $this->jpgraph_instance = new GanttBar($aPos, $aLabel, $aStart, $aEnd, $aCaption, $aHeightFactor);

        $colors_for_charts = new ColorsForCharts();

        $color      = $colors_for_charts->getGanttBarColor();
        $color_dark = $color . ':0.65';
        $this->jpgraph_instance->progress->SetPattern(BAND_SOLID, $color_dark);
        $this->jpgraph_instance->setColor($color_dark);
        $this->jpgraph_instance->setPattern(GANTT_SOLID, $color);
        $this->jpgraph_instance->title->setColor($colors_for_charts->getChartMainColor());
        $this->jpgraph_instance->title->setFont($this->getFont(), FS_NORMAL, 8);
        $this->jpgraph_instance->caption->setColor($colors_for_charts->getChartMainColor());
        $this->jpgraph_instance->caption->setFont($this->getFont(), FS_NORMAL, 7);
    }

    /**
     * Return the font used by the bar
     *
     * @return int
     */
    public function getFont()
    {
        return FF_USERFONT;
    }

    /**
     * Use magic method to retrieve property of a jpgraph instance
     * /!\ Do not call it directly
     *
     * @param string $name The name of the property
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->jpgraph_instance->$name;
    }

    /**
     * Use magic method to set property of a jpgraph instance
     * /!\ Do not call it directly
     *
     * @param string $name  The name of the property
     * @param mixed  $value The new value
     *
     * @return mixed the $value
     */
    public function __set($name, $value)
    {
        return $this->jpgraph_instance->$name = $value;
    }

    /**
     * Use magic method to know if a property of a jpgraph instance exists
     * /!\ Do not call it directly
     *
     * @param string $name The name of the property
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->jpgraph_instance->$name);
    }

    /**
     * Use magic method to unset a property of a jpgraph instance
     * /!\ Do not call it directly
     *
     * @param string $name The name of the property
     *
     * @return bool
     */
    public function __unset($name)
    {
        unset($this->jpgraph_instance->$name);
    }

    /**
     * Use magic method to call a method of a jpgraph instance
     * /!\ Do not call it directly
     *
     * @param string $method The name of the method
     * @param array  $args   The parameters of the method
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $result = call_user_func_array(array($this->jpgraph_instance, $method), $args);
        return $result;
    }

    /**
     * Set CSIM target and alt for the gantt bar
     *
     * @param string $link the target
     * @param string $alt  the alt of the target
     *
     * @return void
     */
    public function setCSIM($link, $alt)
    {
        $this->jpgraph_instance->SetCSIMTarget($link);
        $this->jpgraph_instance->SetCSIMAlt($alt);
        $this->jpgraph_instance->title->SetCSIMTarget(array($link, $link));
        $this->jpgraph_instance->title->SetCSIMAlt(array($alt));
    }
}
