<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
 *
 * Originally written by Nouha Terzi, 2009
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Statistics_DiskUsageGraph extends Statistics_DiskUsageOutput
{

    /**
     *
     * @param Array $services
     * @param unknown_type $groupBy
     * @param unknown_type $startDate
     * @param unknown_type $endDate
     * @param bool $absolute Is y-axis relative to data set or absolute (starting from 0)
     */
    function displayServiceGraph($services, $groupBy, $startDate, $endDate, $accumulative, $absolute = true)
    {
        $graph = new Chart(750, 450, "auto");
        $graph->SetScale("textint");
        $graph->title->Set($GLOBALS['Language']->getText('plugin_statistics_admin_page', 'graph_title'));

        $graph->yaxis->title->Set($GLOBALS['Language']->getText('plugin_statistics_admin_page', 'graph_y_axis_title'));
        $graph->yaxis->SetTitleMargin(60);
        $graph->yaxis->setLabelFormatCallback(array($this, 'sizeReadable'));
        if ($absolute) {
            $graph->yaxis->scale->SetAutoMin(0);
        }

        $servicesList = $this->_dum->getProjectServices();

        $data = $this->_dum->getWeeklyEvolutionServiceData($services, $groupBy, $startDate, $endDate);
        if (is_array($data) && ! empty($data)) {
            $lineplots = array();
            $dates = array();
            foreach ($data as $service => $values) {
                $color = $this->_dum->getServiceColor($service);
                $ydata = array();
                foreach ($values as $date => $size) {
                    $dates[] = $date;
                    $ydata[] = $size;
                }
                $lineplot = new LinePlot($ydata);

                $color = $this->_dum->getServiceColor($service);
                $lineplot->SetColor($color);
                $lineplot->SetFillColor($color.':1.5');
                $lineplot->SetLegend($servicesList[$service]);

                //$lineplot->value->show();
                $lineplot->value->SetFont($graph->getFont(), FS_NORMAL, 8);
                $lineplot->value->setFormatCallback(array($this, 'sizeReadable'));
                if ($accumulative) {
                    //$lineplots[] = $lineplot;
                    // Reverse order
                    array_unshift($lineplots, $lineplot);
                } else {
                    $graph->Add($lineplot);
                }
            }

            if ($accumulative) {
                $accLineplot = new AccLinePlot($lineplots);
                $graph->Add($accLineplot);
            }
            $graph->legend->SetReverse();
            $graph->xaxis->title->Set($GLOBALS['Language']->getText('plugin_statistics', $groupBy));
            $graph->xaxis->SetTitleMargin(35);
            $graph->xaxis->SetTickLabels($dates);

            $graph->Stroke();
        } else {
            $this->displayError($GLOBALS['Language']->getText('plugin_statistics', 'no_data_error'));
        }
    }

    /**
     *
     * @param int $userId
     * @param unknown_type $groupBy
     * @param unknown_type $startDate
     * @param unknown_type $endDate
     * @param bool $absolute Is y-axis relative to data set or absolute (starting from 0)
     */
    function displayUserGraph($userId, $groupBy, $startDate, $endDate, $absolute = true)
    {
        $graph = new Chart(750, 450, "auto");
        $graph->SetScale("textlin");
        $graph->title->Set($GLOBALS['Language']->getText('plugin_statistics_admin_page', 'graph_user_title'));

        $graph->yaxis->title->Set($GLOBALS['Language']->getText('plugin_statistics_admin_page', 'graph_y_axis_title'));
        $graph->yaxis->SetTitleMargin(60);
        $graph->yaxis->setLabelFormatCallback(array($this, 'sizeReadable'));
        if ($absolute) {
            $graph->yaxis->scale->SetAutoMin(0);
        }

        $data = $this->_dum->getWeeklyEvolutionUserData($userId, $groupBy, $startDate, $endDate);
        if (is_array($data) && count($data) > 1) {
            $dates = array();
            $ydata = array();
            foreach ($data as $xdate => $values) {
                $dates[] = $xdate;
                $ydata[] = (float)$values;
            }

            $lineplot = new BarPlot($ydata);
            $lineplot->SetColor('blue');

            $lineplot->value->SetFont($graph->getFont(), FS_NORMAL, 8);
            $lineplot->value->setFormatCallback(array($this, 'sizeReadable'));
            $graph->Add($lineplot);

            $graph->xaxis->title->Set($GLOBALS['Language']->getText('plugin_statistics', $groupBy));
            $graph->xaxis->SetTitleMargin(35);
            $graph->xaxis->SetTickLabels($dates);

            $graph->Stroke();
        } else {
            $this->displayError($GLOBALS['Language']->getText('plugin_statistics', 'no_data_error'));
        }
    }

    /**
     *
     * @param int $groupId
     * @param Array   $services
     * @param String  $groupBy
     * @param Date    $startDate
     * @param Date    $endDate
     * @param bool $absolute Is y-axis relative to data set or absolute (starting from 0)
     */
    function displayProjectGraph($groupId, $services, $groupBy, $startDate, $endDate, $absolute = true, $accumulative = true, $siteAdminView = true)
    {
        $graph = new Chart(750, 450, "auto");
        $graph->SetScale("textint");
        $graph->title->Set($GLOBALS['Language']->getText('plugin_statistics_admin_page', 'graph_project_title'));

        $graph->yaxis->title->Set($GLOBALS['Language']->getText('plugin_statistics_admin_page', 'graph_y_axis_title'));
        $graph->yaxis->SetTitleMargin(60);
        $graph->yaxis->setLabelFormatCallback(array($this, 'sizeReadable'));
        if ($absolute) {
            $graph->yaxis->scale->SetAutoMin(0);
        }

        $servicesList = $this->_dum->getProjectServices($siteAdminView);

        $data = $this->_dum->getWeeklyEvolutionProjectData($services, $groupId, $groupBy, $startDate, $endDate);
        if (is_array($data) && ! empty($data)) {
            $lineplots = array();
            $dates = array();
            $lineAdded = false;
            foreach ($servicesList as $service => $serviceName) {
                if (array_key_exists($service, $data) && is_array($data[$service]) && count($data[$service]) > 1) {
                    $values = $data[$service];
                    $ydata = array();
                    foreach ($values as $date => $size) {
                        $dates[] = $date;
                        $ydata[] = $size;
                    }
                    $lineplot = new LinePlot($ydata);

                    $color = $this->_dum->getServiceColor($service);
                    $lineplot->SetColor($color);
                    $lineplot->SetFillColor($color.':1.5');
                    $lineplot->SetLegend($serviceName);

                    //$lineplot->value->show();
                    $lineplot->value->SetFont($graph->getFont(), FS_NORMAL, 8);
                    $lineplot->value->setFormatCallback(array($this, 'sizeReadable'));
                    if ($accumulative) {
                        //$lineplots[] = $lineplot;
                        // Reverse order
                        $lineAdded = true;
                        array_unshift($lineplots, $lineplot);
                    } else {
                        $lineAdded = true;
                        $graph->Add($lineplot);
                    }
                }
            }

            if ($accumulative && count($lineplots)) {
                $accLineplot = new AccLinePlot($lineplots);
                $graph->Add($accLineplot);
            }

            $graph->legend->SetPos(0.05, 0.5, 'right', 'center');
            $graph->legend->SetColumns(1);

            if ($lineAdded) {
                $graph->legend->SetReverse();
                $graph->xaxis->title->Set($GLOBALS['Language']->getText('plugin_statistics', $groupBy));
                $graph->xaxis->SetTitleMargin(35);
                $graph->xaxis->SetTickLabels($dates);
                $graph->Stroke();
            } else {
                $this->displayError($GLOBALS['Language']->getText('plugin_statistics', 'no_data_error'));
            }
        } else {
            $this->displayError($GLOBALS['Language']->getText('plugin_statistics', 'no_data_error'));
        }
    }

    /**
     *
     * @param int $groupId
     * @param String  $groupBy
     * @param Date    $startDate
     * @param Date    $endDate
     * @param bool $absolute Is y-axis relative to data set or absolute (starting from 0)
     */
    function displayProjectTotalSizeGraph($groupId, $groupBy, $startDate, $endDate, $absolute = true)
    {
        $graph = new Chart(420, 340, "auto");
        $graph->img->SetMargin(70, 50, 30, 70);
        $graph->SetScale("textlin");
        $graph->title->Set("Total project size growth over the time");

        $graph->yaxis->title->Set("Size");
        $graph->yaxis->SetTitleMargin(60);
        $graph->yaxis->setLabelFormatCallback(array($this, 'sizeReadable'));
        if ($absolute) {
            $graph->yaxis->scale->SetAutoMin(0);
        }

        $data = $this->_dum->getWeeklyEvolutionProjectTotalSize($groupId, $groupBy, $startDate, $endDate);
        if (is_array($data) && count($data) > 1) {
            $dates = array();
            $ydata = array();
            foreach ($data as $xdate => $values) {
                $dates[] = $xdate;
                $ydata[] = (float)$values;
            }

            $lineplot = new LinePlot($ydata);

            $color = '#6BA132';
            $lineplot->SetColor($color);
            $lineplot->SetFillColor($color.':1.5');

            $lineplot->value->SetFont($graph->getFont(), FS_NORMAL, 8);
            $lineplot->value->setFormatCallback(array($this, 'sizeReadable'));
            $graph->Add($lineplot);

            $graph->xaxis->title->Set("Weeks");
            $graph->xaxis->SetTitleMargin(35);
            $graph->xaxis->SetTickLabels($dates);

            $graph->Stroke();
        } else {
            $this->displayError($GLOBALS['Language']->getText('plugin_statistics', 'no_data_error'));
        }
    }

    function displayError($msg)
    {
        //ttf from jpgraph
        $ttf = new TTF();
        Chart_TTFFactory::setUserFont($ttf);

        //Calculate the baseline
        // @see http://www.php.net/manual/fr/function.imagettfbbox.php#75333
        //this should be above baseline
        $test2="H";
        //some of these additional letters should go below it
        $test3="Hjgqp";
        //get the dimension for these two:
        $box2 = imageTTFBbox(10, 0, $ttf->File(FF_USERFONT), $test2);
        $box3 = imageTTFBbox(10, 0, $ttf->File(FF_USERFONT), $test3);
        $baseline = abs((abs($box2[5]) + abs($box2[1])) - (abs($box3[5]) + abs($box3[1])));
        $bbox = imageTTFBbox(10, 0, $ttf->File(FF_USERFONT), $msg);
        if ($im = @imagecreate($bbox[2] - $bbox[6], $bbox[3] - $bbox[5])) {
            $background_color = imagecolorallocate($im, 255, 255, 255);
            $text_color       = imagecolorallocate($im, 64, 64, 64);
            imagettftext($im, 10, 0, 0, $bbox[3] - $bbox[5] - $baseline, $text_color, $ttf->File(FF_USERFONT), $msg);
            header("Content-type: image/png");
            imagepng($im);
            imagedestroy($im);
        }
    }

    public function applyColorModifierRGB($color)
    {
        $jpgraphRgb = new RGB();
        $newColor   = $jpgraphRgb->color($color.':1.5');

        unset($newColor[3]);

        $col = implode(',', array_map('floor', $newColor));
        return 'rgb('.$col.')';
    }

    public function applyColorModifierRGBA($color)
    {
        $jpgraphRgb = new RGB();
        $newColor   = $jpgraphRgb->color($color.':1.5');

        unset($newColor[3]);

        $col = implode(',', array_map('floor', $newColor));
        return 'rgba('.$col.',0.5)';
    }
}
