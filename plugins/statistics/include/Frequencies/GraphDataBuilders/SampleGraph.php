<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Statistics\Frequencies\GraphDataBuilder;

use Graph;
use BarPlot;
use DateLocale;
use PlotBand;

/**
 * Display data
 *
 */
class SampleGraph
{
    /**
     * data of the graph
     *
     * @type array $graphValues
     */
    public $graphValues;

    /**
     * color of the bar graph
     *
     * @type string $color
     */
    public $color;

    /**
     * title of the graph
     *
     * @type string $title
     */
    public $title;

    /**
     * the selected period
     *
     * @type string $titlePeriod
     */
    public $titlePeriod;

    /**
     * a Graph object
     *
     * @type Graph $graph
     */
    public $graph;

    /**
     * the parameter selected by user
     *
     * @type string $selectedData
     */
    public $selectedData;

    /**
     * filter of data display
     *
     * @type string $filter
     */
    public $filter;

    /**
     * true if an advanced search is made
     *
     * @type boolean $advsrch
     */
    public $advsrch;

    /**
     * $year the selected year
     *
     * @type int $year
     */
    public $year;

    /**
     * $month the selected month
     *
     * @type int $month
     */
    public $month;

    /**
     * $start the start date
     *
     * @type string $start
     */
    public $start;

    /**
     * $end the end date
     *
     * @type string $end
     */
    public $end;

    private $maxyear;

    private $minyear;

    private $minmonth;

    private $maxmonth;

    private $minday;

    private $maxday;

    /**
     * constructor
     *
     * @param array   $graphValues  array of graph values
     * @param string  $selectedData parameter choosen by user
     * @param string  $filter       the filter
     * @param string  $titlePeriod  the title period
     * @param bool $advsrch true if advanced search
     * @param int     $year         the selected year
     * @param int     $month        the selected month
     * @param string  $start        the start date
     * @param string  $end          the end date
     */
    public function __construct($graphValues, $selectedData, $filter, $titlePeriod, $advsrch, $year, $month, $start, $end)
    {
        $this->color        = null;
        $this->graphValues  = $graphValues;
        $this->selectedData = $selectedData;
        $this->filter       = $filter;
        $this->titlePeriod  = $titlePeriod;
        $this->advsrch      = $advsrch;
        $this->year         = $year;
        $this->month        = $month;
        $this->start        = $start;
        $this->end          = $end;
    }

    /**
     * set the color of the bar plot
     *
     * @return true
     */
    private function setColor()
    {
        $this->color = '#DCDAD5';
        return true;
    }

    /**
     * getColor()
     *
     * @return string the color of bar graphs
     */
    private function getColor()
    {
        $this->setColor();
        return $this->color;
    }

    /**
     * prepare the context of the graph
     *
     * @return void
     */
    private function prepareGraph()
    {
        if ($this->start !== null && $this->end !== null) {
            //recovery of  the date
            $startdate = preg_match('#(\d{4})-(\d{1,2})-(\d{1,2})#', $this->start, $startarray);
            $enddate   = preg_match('#(\d{4})-(\d{1,2})-(\d{1,2})#', $this->end, $endarray);

            $this->minday   = $startarray[3];
            $this->minmonth = $startarray[2];
            $this->minyear  = $startarray[1];

            $this->maxday   = $endarray[3];
            $this->maxmonth = $endarray[2];
            $this->maxyear  = $endarray[1];
        }

        $nbmonth = 1;

        //advanced search display per day
        if ($this->advsrch == 3) {
            $datagraph = array();

            // init array of graph values
            for ($y = $this->minyear; $y <= $this->maxyear; $y++) {
                $minm = 1;

                if ($y == $this->minyear) {
                    $minm = $this->minmonth;
                }
                $maxm = 12;

                if ($y == $this->maxyear) {
                    $maxm = $this->maxmonth;
                }

                for ($m = $minm; $m <= $maxm; $m++) {
                    $mind = 1;
                    if ($m == $minm && $y == $this->minyear) {
                        $mind = $this->minday;
                    }

                    $maxd = date('t', mktime(0, 0, 0, $m, 1, $y));

                    if ($m == $maxm && $y == $this->maxyear) {
                        $maxd = $this->maxday;
                    }

                    for ($d = $mind; $d <= $maxd; $d++) {
                        if (isset($this->graphValues[$y][$m][$d])) {
                            $datagraph[] = $this->graphValues[$y][$m][$d];
                        } else {
                            $datagraph[] = 0;
                        }
                    }
                }
            }

            $this->graphValues = $datagraph;

            $nbmonth = ceil(count($this->graphValues) / 30);

            //create the graph
            $this->graph = new Graph($nbmonth * 500, 450);
            $this->graph->img->SetMargin(60, 120, 20, 90);
            $gbparray = new BarPlot($this->graphValues);
        } else { // create the graph
            $this->graph = new Graph(900, 400);
            $this->graph->img->SetMargin(60, 120, 20, 40);
        }

        //management of the axes scales
        if ($this->filter == 'hour') {
            $this->graph->SetScale("linlin");
        } else {
            $this->graph->SetScale("textlin");
        }

        $this->graph->yaxis->scale->SetGrace(20);
        $this->graph->setShadow();

        // create the bar plots in the advanced search display per month case
        if ($this->advsrch == 2) {
            $datagraph = array();

            for ($y = $this->minyear; $y <= $this->maxyear; $y++) {
                $minm = 1;

                if ($y == $this->minyear) {
                    $minm = $this->minmonth;
                }

                $maxm = 12;

                if ($y == $this->maxyear) {
                    $maxm = $this->maxmonth;
                }

                for ($m = $minm; $m <= $maxm; $m++) {
                    if (isset($this->graphValues[$y][$m])) {
                        $datagraph[] = $this->graphValues[$y][$m];
                    } else {
                        $datagraph[] = 0;
                    }
                }
            }

            $this->graphValues = $datagraph;

            $gbparray = new BarPlot($this->graphValues);
        } else {
            $gbparray = new BarPlot($this->graphValues);
        }
        $gbparray->value->Show();
        $gbparray->value->HideZero();
        $gbparray->value->SetFormat('%d');
        $gbparray->SetLegend($this->selectedData);
        $gbparray->SetFillColor($this->getColor());

        //add to the graph
        $this->graph->legend->Pos(.02, .05, "right", "top");

        if ($this->advsrch == 3) { //the position of the legend depend on the number of month display
            $this->graph->legend->Pos(.075 / $nbmonth, .05, "right", "top");
        }

        $this->graph->Add($gbparray);

        $color1 = array(195, 225, 255);
        $color2 = array(225,240,255);
        $this->graph->setBackgroundGradient($color1, $color2, GRAD_HOR, BGRAD_MARGIN);

        //manage the Weekend display in the Simple search
        if ($this->advsrch == 0 && $this->filter == 'day') {
            $ubandarray = [];
            $abandarray = [];
            foreach ($this->graphValues as $key => $val) {
                $testday = date("w", mktime(0, 0, 0, $this->month, $key, $this->year));
                $color3  = "#F4F3F2@0.45";

                //color the Saturday
                if ($testday == 5) {
                    $beginwe = $key;
                    $endwe   = $beginwe + 1;

                    /**
                     * @psalm-suppress UndefinedConstant VERTICAL and BAND_SOLID might not be present during the inspection
                     * due to the magic inclusion and loading of the jpgraph library
                     */
                    $uband = new PlotBand(VERTICAL, BAND_SOLID, $beginwe, $endwe, $color3);
                    $uband->ShowFrame(false);
                    $uband->SetDensity(80);
                    $ubandarray[] = $uband;
                } elseif ($testday == 6) { //color the Sunday
                    $beginwe = $key;
                    $endwe   = $beginwe + 1;

                    /**
                     * @psalm-suppress UndefinedConstant VERTICAL and BAND_SOLID might not be present during the inspection
                     * due to the magic inclusion and loading of the jpgraph library
                     */
                    $aband = new PlotBand(VERTICAL, BAND_SOLID, $beginwe, $endwe, $color3);
                    $aband->ShowFrame(false);
                    $aband->SetDensity(80);
                    $abandarray[] = $aband;
                }
            }

            for ($i = 0; $i < count($ubandarray); $i++) {
                $this->graph->AddBand($ubandarray[$i]);
            }

            for ($i = 0; $i < count($abandarray); $i++) {
                $this->graph->AddBand($abandarray[$i]);
            }
        }

        //manage the title of the graph
        if ($this->advsrch != 0) {
            $this->graph->title->Set('New ' . $this->selectedData . ' by ' . $this->filter . ' between ' . $this->titlePeriod);
        } else {
            $this->graph->title->Set('New ' . $this->selectedData . ' during ' . $this->titlePeriod);
        }
        $this->graph->title->setFont(FF_FONT1, FS_BOLD);
    }

    /**
     * Display the graph
     *
     * @return void
     */
    public function display()
    {
        $this->prepareGraph();

        //init x axis for advanced search display per month
        if ($this->filter == 'month') {
            if ($this->advsrch == 2) {
                $this->minmonth = $this->minmonth - 1;
                $this->maxmonth = $this->maxmonth - 1;

                $dateLocale = new DateLocale();
                $months     = $dateLocale->GetShortMonth();

                for ($y = $this->minyear; $y <= $this->maxyear; $y++) {
                    $minm = 1;
                    if ($y == $this->minyear) {
                        $minm = $this->minmonth;
                    }
                    $maxm = 12;
                    if ($y == $this->maxyear) {
                        $maxm = $this->maxmonth;
                    }

                    for ($m = $minm; $m <= $maxm; $m++) {
                        $databarx[] = $months[$m % 12];
                    }
                }
                $this->graph->xaxis->SetTickLabels($databarx);
            } else {
                $dateLocale = new DateLocale();
                $allmonths  = $dateLocale->GetMonth();
                $this->graph->xaxis->SetTickLabels($allmonths);
            }
        } elseif ($this->advsrch == 3) {
            for ($y = $this->minyear; $y <= $this->maxyear; $y++) {
                $minm = 1;

                if ($y == $this->minyear) {
                    $minm = $this->minmonth;
                }

                $maxm = 12;

                if ($y == $this->maxyear) {
                    $maxm = $this->maxmonth;
                }

                for ($m = $minm; $m <= $maxm; $m++) {
                    $mind = 1;
                    if ($m == $minm && $y == $this->minyear) {
                        $mind = $this->minday;
                    }

                    $maxd = date('t', mktime(0, 0, 0, $m, 1, $y));
                    if ($m == $maxm && $y == $this->maxyear) {
                        $maxd = $this->maxday;
                    }

                    $databarx = [];
                    for ($d = $mind; $d <= $maxd; $d++) {
                        if ($d == 1) {
                            $databarx[] = $d . '/' . date('F', mktime(0, 0, 0, $m, $d, $y));
                        } elseif ($d % 5 == 0) {
                            $databarx[] = $d;
                        } else {
                            $databarx[] = '';
                        }
                    }
                }
            }
            $this->graph->xaxis->SetLabelAngle(90);
            $this->graph->xaxis->SetTickLabels($databarx);
        }
        $this->graph->stroke();
    }
}
