<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once 'pre.php';
require_once 'common/chart/Chart.class.php';

class Git_LastPushesGraph {
    
    public $displayChart;

    /**
     * Constructor.
     *
     * @return Void
     */
    public function __construct() {
        if (empty($this->duration)) {
            $this->displayChart = false;
        }
    }

    function prepareGraph($nb_repo, $dates) {
        $graph   = new Chart(500, 300+16*$nb_repo);
        $graph->SetScale('textlin');
        $graph->img->SetMargin(40, 20, 20, 80 + 16 * $nb_repo);
        $graph->SetMarginColor('white');
        $graph->title->Set($GLOBALS['Language']->getText('plugin_git', 'widget_project_pushes_title'));
        $graph->title->SetFont(FF_FONT2, FS_BOLD);
        $graph->xaxis->SetLabelMargin(30);
        $graph->xaxis->SetLabelAlign('right', 'center');
        $graph->xaxis->SetTickLabels($dates);
        $graph->yaxis->SetPos('min');
        $graph->yaxis->SetTitle("Pushes", 'center');
        $graph->yaxis->title->SetFont(FF_FONT2, FS_BOLD);
        $graph->yaxis->title->SetAngle(90);
        $graph->yaxis->title->Align('center', 'top');
        $graph->yaxis->SetTitleMargin(30);
        $graph->yaxis->SetLabelAlign('center', 'top');
        $graph->legend->Pos(0.1, 0.98, 'right', 'bottom');
        return $graph;
    }

    function displayAccumulatedGraph($bplot, $graph) {
        // Create the accumulated bar plot
        $abplot = new AccBarPlot($bplot);
        $abplot->SetShadow();
        $abplot->SetAbsWidth(10);
        $graph->Add($abplot);
        $graph->Stroke();
    }

    function displayError($msg) {
        //ttf from jpgraph
        $ttf = new TTF();
        $ttf->SetUserFont(
            'dejavu-lgc/DejaVuLGCSans.ttf',
            'dejavu-lgc/DejaVuLGCSans-Bold.ttf',
            'dejavu-lgc/DejaVuLGCSans-Oblique.ttf',
            'dejavu-lgc/DejaVuLGCSans-BoldOblique.ttf'
        );
        //Calculate the baseline
        // @see http://www.php.net/manual/fr/function.imagettfbbox.php#75333
        //this should be above baseline
        $test2="H";
        //some of these additional letters should go below it
        $test3="Hjgqp";
        //get the dimension for these two:
        $box2 = imageTTFBbox(10,0,$ttf->File(FF_USERFONT),$test2);
        $box3 = imageTTFBbox(10,0,$ttf->File(FF_USERFONT),$test3);
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
}
?>
