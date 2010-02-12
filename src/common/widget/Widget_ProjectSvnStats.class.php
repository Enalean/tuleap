<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/dao/SvnCommitsDao.class.php');
require_once('common/chart/Chart.class.php');

class Widget_ProjectSvnStats extends Widget {
    function __construct() {
        parent::__construct('projectsvnstats');
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('svn_widget', 'svnstats');
    }
    function canBeUsedByProject($project) {
        return $project->usesSvn();
    }
    function getCategory() {
        return 'scm';
    }
    function getContent() {
        $request = HTTPRequest::instance();
        return '<div style="text-align:center">
                <img src="/widgets/widget.php?owner='. WidgetLayoutManager::OWNER_TYPE_GROUP.($request->get('group_id')) .'&action=process&name['. $this->id .']='. $this->getInstanceId() .'" />
                </div>';
    }
    
    protected $tmp_nb_of_commit;
    function process($owner_type, $owner_id) {
        $dao = new SvnCommitsDao(CodendiDataAccess::instance());
        //The default duration is 3 months back
        $nb_weeks = 4 * 3;
        $duration = 7 * $nb_weeks;
        
        $day = 24 * 3600;
        $week = 7 * $day;
        
        //compute the stats
        $stats = array();
        $nb_of_commits = array();
        foreach($dao->statsByGroupId($owner_id, $duration) as $row) {
            $stats[$row['whoid']]['by_day'][$row['day'] * $day] = $row['nb_commits'];
            $stats[$row['whoid']]['by_week'][$row['week']]      = $row['nb_commits'];
            $this->tmp_nb_of_commit[$row['whoid']] = (isset($this->tmp_nb_of_commit[$row['whoid']]) ? $this->tmp_nb_of_commit[$row['whoid']] : 0) + $row['nb_commits'];
        }
        if (count($stats)) {
            //sort the results
            uksort($stats, array($this, 'sortByTop'));
            
            $today           = $_SERVER['REQUEST_TIME'];
            $start_of_period = strtotime("-$nb_weeks weeks");
            
            //fill-in the holes
            $tmp_stats = array();
            foreach($stats as $whoid => $stat) {
                $tmp_stats = array();
                for($i = $start_of_period ; $i <= $today ; $i += $week) {
                    $w = (int)date('W', $i);
                    $tmp_stats[$w] = isset($stat['by_week'][$w]) ? $stat['by_week'][$w] : '0';
                }
                $stats[$whoid]['by_week'] = $tmp_stats;
            }
            
            
            //fill-in the labels
            $dates = array();
            for($i = $start_of_period ; $i <= $today ; $i += $week) {
                $dates[] = date('M d', $i);
            }
            
            $nb_commiters = count($stats);
            
            //Build the chart
            $c = new Chart(400, 300+16*$nb_commiters);
            $c->SetScale('textlin');
            $c->img->SetMargin(40,20,20,80+16*$nb_commiters);
            $c->xaxis->SetTickLabels($dates);
            $c->legend->Pos(0.1,0.95,'left','bottom');
            
            $colors = array_reverse(array_slice($GLOBALS['HTML']->getChartColors(), 0, $nb_commiters));
            $nb_colors = count($colors);
            $bars = array();
            $i = 0;
            foreach($stats as $whoid => $stat) {
                $l = new BarPlot(array_values($stat['by_week']));
                $color = $colors[$i++ % $nb_colors];
                $l->SetColor($color.':0.7');
                $l->setFillColor($color);
                if ($user = UserManager::instance()->getUserById($whoid)) {
                    $l->SetLegend(UserHelper::instance()->getDisplayNameFromUser($user));
                } else {
                    $l->SetLegend('Unknown user ('. (int)$whoid .')');
                }
                $bars[] = $l;
            }
            
            $gbplot = new AccBarPlot($bars);
            $c->Add($gbplot);
            echo $c->stroke();
        } else {
            //There is no stats yet
            //generate a message as an image 
            //(plz remember that we must return some img data)
            
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
            
            $error = "No commits in the last $duration days";
            $bbox = imageTTFBbox(10, 0, $ttf->File(FF_USERFONT), $error);
            if ($im = @imagecreate($bbox[2] - $bbox[6], $bbox[3] - $bbox[5])) {
                $background_color = imagecolorallocate($im, 255, 255, 255);
                $text_color       = imagecolorallocate($im, 64, 64, 64);
                imagettftext($im, 10, 0, 0, $bbox[3] - $bbox[5] - $baseline, $text_color, $ttf->File(FF_USERFONT), $error);
                header("Content-type: image/png");
                imagepng($im);
                imagedestroy($im);
            }
        }
    }
    
    protected function sortByTop($a, $b) {
        return strnatcasecmp($this->tmp_nb_of_commit[$a], $this->tmp_nb_of_commit[$b]);
    }
    
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_project_svn_stats','description');
    }
}
?>
