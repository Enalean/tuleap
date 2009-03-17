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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
        //The default duration is 30 days back
        $duration = 30;
        
        $day = 24 * 3600;
        
        //compute the stats
        $stats = array();
        $nb_of_commits = array();
        foreach($dao->statsByGroupId($owner_id, $duration) as $row) {
            $stats[$row['whoid']][$row['day'] * $day] = $row['nb_commits'];
            $this->tmp_nb_of_commit[$row['whoid']] = (isset($this->tmp_nb_of_commit[$row['whoid']]) ? $this->tmp_nb_of_commit[$row['whoid']] : 0) + $row['nb_commits'];
        }
        if (count($stats)) {
            //sort the results
            uasort($stats, array($this, 'sortByTop'));
            
            //fill-in the holes and the labels
            $today = strtotime(date('Y-m-d', $_SERVER['REQUEST_TIME']));
            $dates = array();
            for($i = $today - $duration * $day ; $i <= $today ; $i += $day) {
                $dates[] = date('M d', $i);
                foreach($stats as $whoid => $stat) {
                    if (!isset($stat[$i])) {
                        $stats[$whoid][$i] = '-';
                    }
                }
            }
            
            $nb_commiters = count($stats);
            
            //Build the chart
            $c = new Chart(300, 300+16*$nb_commiters);
            $c->SetScale('datlin');
            $c->img->SetMargin(40,20,20,80+16*$nb_commiters);
            $c->xaxis->SetTickLabels($dates);
            $c->legend->Pos(0.1,0.95,'left','bottom');
            $colors = $GLOBALS['HTML']->getChartColors();
            $nb_colors = count($colors);
            foreach($stats as $whoid => $stat) {
                ksort($stat);
                $l = new LinePlot(array_values($stat));
                $l->SetColor($colors[$i % $nb_colors]);
                if ($user = UserManager::instance()->getUserById($whoid)) {
                    $l->SetLegend(UserHelper::instance()->getDisplayNameFromUser($user));
                } else {
                    $l->SetLegend('Unknown user ('. (int)$whoid .')');
                }
                $c->Add($l);
            }
            echo $c->stroke();
        } else {
            //There is no stats yet
            //generate a message as an image 
            //(plz remember that we must return some img data)
            
            //ttf from jpgraph
            $ttf = new TTF();
            //Calculate the baseline
            // @see http://www.php.net/manual/fr/function.imagettfbbox.php#75333
            //this should be above baseline
            $test2="H";
            //some of these additional letters should go below it
            $test3="Hjgqp";
            //get the dimension for these two:
            $box2 = imageTTFBbox(10,0,$ttf->File(FF_DEJAVU),$test2);
            $box3 = imageTTFBbox(10,0,$ttf->File(FF_DEJAVU),$test3);
            $baseline = abs((abs($box2[5]) + abs($box2[1])) - (abs($box3[5]) + abs($box3[1])));
            
            $error = "No commits in the last $duration days";
            $bbox = imageTTFBbox(10, 0, $ttf->File(FF_DEJAVU), $error);
            if ($im = @imagecreate($bbox[2] - $bbox[6], $bbox[3] - $bbox[5])) {
                $background_color = imagecolorallocate($im, 255, 255, 255);
                $text_color       = imagecolorallocate($im, 64, 64, 64);
                imagettftext($im, 10, 0, 0, $bbox[3] - $bbox[5] - $baseline, $text_color, $ttf->File(FF_DEJAVU), $error);
                header("Content-type: image/png");
                imagepng($im);
                imagedestroy($im);
            }
        }
    }
    
    protected function sortByTop($a, $b) {
        return strnatcasecmp($this->tmp_nb_of_commit[$a], $this->tmp_nb_of_commit[$b]);
    }
}
?>
