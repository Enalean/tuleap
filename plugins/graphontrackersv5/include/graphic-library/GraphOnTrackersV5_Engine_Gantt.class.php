<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

use Tuleap\chart\GanttVerticalLine;

class GraphOnTrackersV5_Engine_Gantt extends GraphOnTrackersV5_Engine
{

    public $title;
    public $description;
    public $scale;
    public $start;
    public $due;
    public $finish;
    public $progress;
    public $right;
    public $asOfDate;
    public $hint;
    public $links;
    public $summary;
    public $jp_graph_path;
    public $summary_label;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setScale($scale)
    {
        $this->scale = $scale;
    }

    public function setStart($start)
    {
        $this->start = $start;
    }

    public function setDue($due)
    {
        $this->due = $due;
    }

    public function setFinish($finish)
    {
        $this->finish = $finish;
    }

    public function setProgress($progress)
    {
        $this->progress = $progress;
    }


    public function setRight($right)
    {
        $this->right = $right;
    }


    public function setAsOfDate($asOfDate)
    {
        $this->asOfDate = $asOfDate;
    }

    public function setHint($hint)
    {
        $this->hint = $hint;
    }

    public function setSummary($summary)
    {
        $this->summary = $summary;
    }


    public function setLinks($links)
    {
        $this->links = $links;
    }

    public function setData($data)
    {
        $this->data = $data;
    }


    public function formatScale()
    {
        switch ($this->scale) {
            case 'day':
                return GANTT_HYEAR | GANTT_HMONTH | GANTT_HWEEK | GANTT_HDAY;
                break;
            case 'week':
                return GANTT_HYEAR | GANTT_HMONTH | GANTT_HWEEK;
                break;
            case 'month':
                return GANTT_HYEAR | GANTT_HMONTH;
                break;
            case 'year':
            default:
                return GANTT_HYEAR;
                break;
        }
    }

    public function getScaleDim()
    {
        $scale_dim = null;
        switch ($this->scale) {
            case 'day':
                $scale_dim = 1;
                break;
            case 'week':
                $scale_dim = 3;
                break;
            case 'month':
                $scale_dim = 7;
                break;
            case 'year':
            default:
                $scale_dim = 14;
                break;
        }
        return $scale_dim;
    }


    /**
    * Builds gantt graph
    */
    public function buildGraph()
    {
        $this->graph = new Chart_Gantt($this->width, $this->height, "auto");

        // title setup
        $this->graph->title->Set($this->title);

        if (is_null($this->description)) {
            $this->description = "";
        }
        $this->graph->subtitle->Set($this->description);

        //Try to fix issue with 'cut' right infos
        //TODO : more elegant way...
        $margin_right = 20;
        if (isset($this->data[0]['right']) && $this->data[0]['right'] != '' && $this->asOfDate == '') {
            $margin_right = 100;
        }
        $this->graph->SetMargin(20, $margin_right, $this->graph->getTopMargin(), 30);

        // asOfDate setup
        if ($this->asOfDate == 0) {
            $dateRep  = date("Y-m-d", $_SERVER['REQUEST_TIME']);
            $dateDisp = date("m-d-Y", $_SERVER['REQUEST_TIME']);
            $vline = new GanttVerticalLine($dateRep, "Today:" . $dateDisp, $this->graph->getTodayLineColor(), 1, 'solid');
        } else {
            $dateRep  = $this->asOfDate;
            $dateDisp = date("m-d-Y", strtotime($this->asOfDate));
            $vline = new GanttVerticalLine($dateRep, $dateDisp, $this->graph->getTodayLineColor(), 1, 'solid');
        }
        $vline->SetDayOffset(0.5);
        $vline->title->SetFont($this->graph->getFont(), FS_NORMAL, 7);
        $vline->title->SetColor($this->graph->getMainColor());
        $this->graph->Add($vline);

        //scale setup
        $this->graph->ShowHeaders($this->formatScale());
        $scale_dim = $this->getScaleDim();

        //add info to gantt graph
        $this->graph->scale->actinfo->SetColTitles(array("Id", $this->summary_label));

        $format = "Y-m-d";
        $today  = strtotime('now');
        $one_day = 24 * 3600;
        for ($i = 0; $i < count($this->data); $i++) {
            $s = $this->data[$i]['start'];
            $d = $this->data[$i]['due'];
            $f = $this->data[$i]['finish'];

            //Milestone
            if (($s == 0 && $d == 0 && $f != 0)
                || ($s == 0 && $d != 0 && $f == 0)
                || ($s == 0 && $d != 0 && $f == $d)
                ) {
                $this->addMilestone($i, $this->data[$i], array('date' => max($this->data[$i]['due'], $this->data[$i]['finish'])));
            } elseif ($s == 0 && $d != 0 && $f != 0 && $d < $f) { //Late milestone
                $this->addLateBar($i, $this->data[$i], false, array(
                    'start'   => 'due',
                    'end'     => 'finish',
                    'label'   => "",
                    'caption' => "",
                    'height'  => 0.2
                ));
                $this->addMilestone($i, $this->data[$i], array('date' => 'finish'));
            } elseif ($s == 0 && $d != 0 && $f != 0 && $f < $d) { //Early milestone
                $this->addBar($i, $this->data[$i], false, array(
                    'start'   => 'finish',
                    'end'     => 'due',
                    'label'   => "",
                    'caption' => "",
                    'height'  => 0.2
                ));
                $this->addMilestone($i, $this->data[$i], array('date' => 'finish'));
            } elseif ($s != 0 && $d == 0 && $s <= $f) { //Bar, start to finish
                $this->addBar($i, $this->data[$i], true, array(
                    'start'   => 'start',
                    'end'     => 'finish'
                ));
            } elseif ($s != 0 && $d != 0 && $s <= $d && ($f == 0 || $d == $f)) { //Bar, start to due
                $this->addBar($i, $this->data[$i], true, array(
                    'start'   => 'start',
                    'end'     => 'due'
                ));
            } elseif ($s != 0 && $d != 0 && $f != 0 && $s <= $d && $d < $f) { //Late bar, start to due to finish
                $this->addBar($i, $this->data[$i], true, array(
                    'start' => 'start',
                    'end' => 'due',
                    'caption' => ""));
                $bar = $this->addLateBar($i, $this->data[$i], false, array(
                    'start' => date($format, ( strtotime($this->data[$i]['due']) + $one_day)),
                    'end' => 'finish',
                    'label' => ""));
            } elseif ($s != 0 && $d != 0 && $d < $s && ($f == 0 || $s == $f)) { //Late bar, due to start
                $bar = $this->addLateBar($i, $this->data[$i], true, array(
                    'start' => 'due',
                    'end' => 'start'));
            } elseif ($s != 0 && $d != 0 && $f != 0 && $d < $s && $s < $f) { //Late bar, due to finish
                $bar = $this->addLateBar($i, $this->data[$i], true, array(
                    'start' => 'due',
                    'end' => 'finish'));
            } elseif ($s != 0 && $d != 0 && $f != 0 && $s <= $f && $f < $d) { //Early bar
                $this->addBar($i, $this->data[$i], true, array(
                    'start' => 'start',
                    'end' => 'finish',
                    'caption' => ""));
                $this->addBar($i, $this->data[$i], false, array(
                    'start' => date($format, ( strtotime($this->data[$i]['finish']) + $one_day)),
                    'end' => 'due',
                    'label' => "",
                    'height' => 0.2));
            } else { //Error
                $this->addErrorBar($i, $this->data[$i]);
            }
        }
    }

    protected function addBar($pos, $data, $progress, $params = array())
    {
        $format = "Y-m-d";
        //start date
        if (isset($params['start'])) {
            if (in_array($params['start'], array('start', 'due', 'finish'))) {
                $aStart = $data[$params['start']];
            } else {
                $aStart = $params['start'];
            }
        } else {
            $aStart = $data['start'];
        }

        //end date
        if (isset($params['end'])) {
            if (in_array($params['end'], array('start', 'due', 'finish'))) {
                $aEnd = $data[$params['end']];
            } else {
                $aEnd = $params['end'];
            }
        } else {
            $aEnd = $data['finish'];
        }
        $aLabel        = isset($params['label']) ? $params['label'] : array($data['id'], html_entity_decode($data['summary']));
        $aCaption      = isset($params['caption']) ? $params['caption'] : $data['right'];
        $aHeightFactor = isset($params['height']) ? $params['height'] : 0.6; //default jpgraph value

        $bar = new Chart_GanttBar($pos, $aLabel, $aStart, $aEnd, $aCaption, $aHeightFactor);
        if ($progress) {
            $bar->progress->Set($data['progress']);
        }
        $bar->SetCSIM($data['links'], $data['hint']);
        $this->graph->Add($bar);
        return $bar;
    }

    protected function addMilestone($pos, $data, $params = array())
    {
        $format = "Y-m-d";
        $aLabel   = isset($params['label']) ? $params['label'] : array($data['id'], html_entity_decode($data['summary']));
        if (isset($params['date'])) {
            if (in_array($params['date'], array('start', 'due', 'finish'))) {
                $aDate = $data[$params['date']];
            } else {
                $aDate = $params['date'];
            }
        } else {
            $aDate = $data['due'];
        }
        $aCaption = isset($params['caption']) ? $params['caption'] : $data['right'];

        $milestone = new Chart_GanttMileStone($pos, $aLabel, $aDate, $aCaption);

        $milestone->SetCSIM($data['links'], $data['hint']);
        $this->graph->Add($milestone);
        return $milestone;
    }

    protected function addErrorBar($pos, $data)
    {
        $format   = "Y-m-d";

        $debut = null;
        $fin   = null;
        foreach (array('start', 'due', 'finish') as $date) {
            if ($data[$date]) {
                if (!$debut) {
                    $debut = $data[$date];
                } else {
                    $debut = min($debut, $data[$date]);
                }
            }
            if (!$fin) {
                $fin = $data[$date];
            } else {
                $fin = max($fin, $data[$date]);
            }
        }
        if (!$debut) {
            $debut = date($format, strtotime('now'));
        }
        if (!$fin) {
            $fin = $debut;
        }

        $bar = $this->addBar($pos, $data, false, array('start' => $debut, 'end' => $fin, 'height' => 0.2));
        $bar->SetColor($this->graph->getErrorBarColor() . ":0.7");
        $bar->SetPattern(GANTT_RDIAG, "black", 96);
    }

    protected function addLateBar($pos, $data, $progress, $params = array())
    {
        $bar = $this->addBar($pos, $data, $progress, $params);
        $bar->SetColor($this->graph->getLateBarColor() . ":0.7");
        $bar->SetPattern(GANTT_SOLID, $this->graph->getLateBarColor());
    }
}
