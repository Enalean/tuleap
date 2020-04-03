<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Chart;

use Chart_TTFFactory;
use Feedback;
use TTF;

/**
* Chart
*
* Facade for jpgraph Graph
*
* @see jpgraph documentation for usage
*/
class Chart
{
    /**
     * @var ColorsForCharts
     */
    protected $colors_for_charts;

    protected $jpgraph_instance;

    /**
    * Constructor
    *
    * @param int    $aWidth      Default is 600
    * @param int    $aHeight     Default is 400
    * @param string $aCachedName Default is ""
    * @param int    $aTimeOut    Default is 0
    * @param bool   $aInline     Default is true
    *
    * @return void
    */
    public function __construct($aWidth = 600, $aHeight = 400, $aCachedName = "", $aTimeOut = 0, $aInline = true)
    {
        $this->colors_for_charts = new ColorsForCharts();

        $classname = $this->getGraphClass();
        $this->jpgraph_instance = new $classname($aWidth, $aHeight, $aCachedName, $aTimeOut, $aInline);
        $this->jpgraph_instance->SetMarginColor($this->getChartBackgroundColor());
        $this->jpgraph_instance->SetFrame(true, $this->getMainColor(), 0);

        if ($aWidth && $aHeight) {
            $this->jpgraph_instance->img->SetAntiAliasing();
        }

        Chart_TTFFactory::setUserFont($this->jpgraph_instance);

        //Fix margin
        try {
            $this->jpgraph_instance->img->SetMargin(70, 160, 30, 70);
        } catch (\Exception $e) {
            // do nothing, JPGraph displays the error by itself
        }

        $this->jpgraph_instance->legend->SetShadow(false);
        $this->jpgraph_instance->legend->SetColor($this->getMainColor());
        $this->jpgraph_instance->legend->SetFrameWeight(0);
        $this->jpgraph_instance->legend->SetFillColor($this->getChartBackgroundColor());
        $this->jpgraph_instance->legend->SetFont($this->getFont(), FS_NORMAL, 8);
        $this->jpgraph_instance->legend->SetVColMargin(5);
        $this->jpgraph_instance->legend->SetPos(0.05, 0.5, 'right', 'center');
        $this->jpgraph_instance->legend->SetLineSpacing(10);

        $this->jpgraph_instance->title->SetFont($this->getFont(), FS_BOLD, 12);
        $this->jpgraph_instance->title->SetColor($this->getMainColor());
        $this->jpgraph_instance->title->SetMargin(15);

        $this->jpgraph_instance->subtitle->SetFont($this->getFont(), FS_NORMAL, 9);
        $this->jpgraph_instance->subtitle->SetColor($this->getMainColor());
        $this->jpgraph_instance->subtitle->SetAlign('left', 'top', 'left');
        $this->jpgraph_instance->subtitle->SetMargin(20);
    }

    /**
     * Get the name of the jpgraph class to instantiate
     *
     * @psalm-return class-string
     */
    protected function getGraphClass(): string
    {
        return \Graph::class;
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
        try {
            $result = call_user_func_array(array($this->jpgraph_instance, $method), $args);
        } catch (\Exception $exc) {
            $error_message = sprintf(
                _('JpGraph error for graph "%s": %s'),
                $this->title->t,
                $exc->getMessage()
            );

            if (headers_sent()) {
                echo '<p class="feedback_error">';
                echo $error_message;
                echo '</p>';
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $error_message);
            }
            return false;
        }
        if (!strnatcasecmp($method, 'SetScale')) {
            $this->jpgraph_instance->xaxis->SetColor($this->getMainColor(), $this->getMainColor());
            $this->jpgraph_instance->xaxis->SetFont($this->getFont(), FS_NORMAL, 8);
            $this->jpgraph_instance->xaxis->SetLabelAngle(45);
            $this->jpgraph_instance->xaxis->title->SetFont($this->getFont(), FS_BOLD, 8);

            $this->jpgraph_instance->yaxis->SetColor($this->getMainColor(), $this->getMainColor());
            $this->jpgraph_instance->yaxis->SetFont($this->getFont(), FS_NORMAL, 8);
            $this->jpgraph_instance->xaxis->title->SetFont($this->getFont(), FS_BOLD, 8);
            $this->jpgraph_instance->yaxis->title->SetFont($this->getFont(), FS_BOLD, 8);
        }
        return $result;
    }

    /**
     * Return the font used by the chart
     *
     * @return int
     */
    public function getFont()
    {
        return FF_USERFONT;
    }

    /**
     * Return the main color used by the chart (axis, text, ...)
     *
     * @return string
     * @see Layout->getChartMainColor
     */
    public function getMainColor()
    {
        return $this->colors_for_charts->getChartMainColor();
    }

    /**
     * Return the colors used by the chart to draw data (part of pies, bars, ...)
     *
     * @return array
     * @see Layout->getChartColors
     */
    public function getThemedColors()
    {
        return $this->colors_for_charts->getChartColors();
    }

    private function getChartBackgroundColor()
    {
        return $this->colors_for_charts->getChartBackgroundColor();
    }

    /**
     * Compute the height of the top margin
     *
     * @return int
     */
    public function getTopMargin()
    {
        return 20 + $this->jpgraph_instance->title->getTextHeight($this->jpgraph_instance->img) + $this->jpgraph_instance->subtitle->getTextHeight($this->jpgraph_instance->img);
    }

    /**
     * Diplay a given message as png image
     *
     * @param String $msg Message to display
     */
    public function displayMessage($msg): void
    {
        //ttf from jpgraph
        $ttf = new TTF();
        Chart_TTFFactory::setUserFont($ttf);

        if ($msg === '') { // Workaround for an issue with gd 2.3.0, see https://tuleap.net/plugins/tracker/?aid=14721
            $im = @imagecreate(2, 2);
            if ($im !== false) {
                header('Content-type: image/png');
                imagecolorallocate($im, 0, 0, 0);
                imagepng($im);
                imagedestroy($im);
            }
            return;
        }

        //Calculate the baseline
        // @see http://www.php.net/manual/fr/function.imagettfbbox.php#75333
        //this should be above baseline
        $test2    = "H";
        //some of these additional letters should go below it
        $test3    = "Hjgqp";
        //get the dimension for these two:
        $box2     = imageTTFBbox(10, 0, $ttf->File(FF_USERFONT), $test2);
        $box3     = imageTTFBbox(10, 0, $ttf->File(FF_USERFONT), $test3);
        $baseline = abs((abs($box2[5]) + abs($box2[1])) - (abs($box3[5]) + abs($box3[1])));
        $bbox     = imageTTFBbox(10, 0, $ttf->File(FF_USERFONT), $msg);
        if ($im = @imagecreate($bbox[2] - $bbox[6], $bbox[3] - $bbox[5])) {
            $backgroundColor  = imagecolorallocate($im, 255, 255, 255);
            $textColor        = imagecolorallocate($im, 64, 64, 64);
            imagettftext($im, 10, 0, 0, $bbox[3] - $bbox[5] - $baseline, $textColor, $ttf->File(FF_USERFONT), $msg);
            header("Content-type: image/png");
            imagepng($im);
            imagedestroy($im);
        }
    }
}
