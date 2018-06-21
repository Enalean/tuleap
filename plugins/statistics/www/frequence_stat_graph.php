<?php
  /** 
   * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
   *
   * Originally written by Manuel Vacelet, 2005
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

require_once 'pre.php';
require_once $jpgraph_dir.'/jpgraph.php';
require_once $jpgraph_dir.'/jpgraph_bar.php';

/**
 * Global data processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class Sample
{
    /**
     * $start look for timestamps superior or equals to this timestamp
     *
     * @type int $start
     */
    var $start;

    /**
     * $end look for timestamps strickly inferior to this timestamp
     *
     * @type int $end
     */
    var $end;

    /**
     * $filter filter of data display
     *
     * @type string $filter
     */
    var $filter;

    /**
     * $year the selected year
     *
     * @type int $year
     */
    var $year;

    /**
     * $month the selected month
     *
     * @type int $month
     */
    var $month;

    /**
     * $day the selected day
     *
     * @type int $day
     */
    var $day;

    /**
     * $startdate the start date in the advanced search
     *
     * @type string $startdate
     */
    var $startdate;

    /**
     * $enddate the end date in the advanced search
     *
     * @type string $enddate
     */
    var $enddate;

    /**
     * the selected period
     *
     * @type string $titlePeriod
     */
    var $titlePeriod;

    /**
     * the table in which we looking for data
     *
     * @type string $table
     */
    var $table;

    /**
     * field selected in the table
     *
     * @type string $field
     */
    var $field;

    /**
     * constructor
     *
     */
    function __construct()
    {
        $this->year      = null;
        $this->month     = null;
        $this->day       = null;
        $this->startdate = null;
        $this->enddate   = null;
        $this->filter    = "month";
    }

    /**
     * indicate the search mode
     *
     * @param string $startdate the date the graph start
     * @param string $enddate   the date teh graph end
     *
     * @return boolean true if advanced search, else return false
     */
    function isAdvanced($startdate, $enddate)
    {
        if (($startdate != '') && ($enddate != '')) {
            return true;
     
        } else {
            return false;
        }
    }

    /**
     * init parameter related with date in the simple search 
     * ($start, $end, $filter, $titlePeriod)
     *
     * @param int $year  the selected year
     * @param int $month the selected month
     * @param int $day   the selected day
     *
     * @return void
     */
    function initDateSimple($year, $month, $day)
    {
        if ($year != 0 && $month !=0 && $day != 0) {
            $this->start = mktime(0, 0, 0, $month, $day, $year);

        } elseif ($year != 0 && $month != 0) {
            $this->start = mktime(0, 0, 0, $month, 1, $year);

        } elseif ($year != 0) {
            $this->start = mktime(0, 0, 0, 1, 1, $year);

        } elseif ($year == 0) {
            $year        = date("Y");
            $this->start = mktime(0, 0, 0, 1, 1, $year);
        }

        if ($day !=0 ) {
            $this->filter = 'hour';

        } elseif ($month !=0) {
            $this->filter = 'day';

        } else {
            $this->filter = 'month';
        }

        switch ($this->filter) {

        case 'month':
            $this->end = mktime(0, 0, 0, 1, 1, $year+1); //I search timestamps strictly inferior to the end timestamps. That's why I use $year+! instead of 12,31,$year

            $this->titlePeriod = $year;
            break;

        case 'day':
            $this->end = mktime(0, 0, 0, $month+1, 1, $year); //$month+1 is used for the same reason

            $this->titlePeriod = $month.'/'.$year;
            break;

        case 'hour':
            $this->end = mktime(0, 0, 0, $month, $day+1, $year); //$day+1 is used for the same reason

            $this->titlePeriod = $day.'/'.$month.'/'.$year;
            break;
        }

        $this->year  = $year;
        $this->month = $month;
        $this->day   = $day;
    }

    /**
     * init parameter related with date in the advanced search 
     * ($start, $end, $filter, $titlePeriod)
     *
     * @param string $startdate the date the graph start
     * @param string $enddate   the date the graph end
     * @param string $filter    the filter of display 
     * (group by month, group by day,group by hour, month, day)
     *
     * @return void
     */
    function initDateAdvanced($startdate, $enddate, $filter)
    {
        $this->start = strtotime($startdate);
        $this->end   = strtotime($enddate);

        switch ($filter) {

        case 'month1':
            $this->filter = 'month';
            break;

        case 'day1':
            $this->filter = 'day';
            break;

        case 'hour1':
            $this->filter = 'hour';
            break;

        default:
            $this->filter = $filter;
            break;
        }

        $date1 = preg_replace('#(\d{4})-(\d{1,2})-(\d{1,2})#', '$3/$2/$1', $startdate);
        $date2 = preg_replace('#(\d{4})-(\d{1,2})-(\d{1,2})#', '$3/$2/$1', $enddate);

        $this->titlePeriod = $date1.' and '.$date2;
        $this->startdate   = $startdate;
        $this->enddate     = $enddate;
    } 

    /**
     * getFilter()
     *
     * @return string the filter
     */
    function getFilter()
    {
        return $this->filter;
    }

    /**
     * getStartDate()
     *
     * @return int the timestamp of start
     */
    function getStartDate()
    {
        return $this->start;
    }

    /**
     * getendDate()
     *
     * @return int the timestamp of end
     */
    function getEndDate()
    {
        return $this->end;
    }

    /**
     * getTitlePeriod()
     *
     * @return string the title period
     */
    function getTitlePeriod()
    {
        return $this->titlePeriod;
    }

    protected function getDataSQLQuery($filter, $startDate, $endDate)
    {
        return sprintf('SELECT %s(FROM_UNIXTIME('.$this->field.')) as '.$this->getFilter().',COUNT(*) as c'.
            ' FROM '.$this->table.
            ' WHERE '.$this->field.' >= %d'.
            ' AND  '.$this->field.' < %d'.
            ' GROUP BY %s', db_escape_string($filter), db_escape_int($startDate), db_escape_int($endDate), db_escape_string($filter));
    }

    /**
     * fetchData()
     *
     * @return array an array of data according to the parameter choosen by user
     */
    function fetchData()
    {
        $filter    = $this->getFilter();
        $startDate = $this->getStartDate();
        $endDate   = $this->getEndDate();

        $res = db_query($this->getDataSQLQuery($filter, $startDate, $endDate));

        if ($this->getFilter() == 'month') {
            $nbr = 11;            

        } elseif ($this->getFilter() == 'day') {
            $nbr = date("t", mktime(0, 0, 0, date('m', $this->start), 1, date('Y', $this->start)))-1;

        } elseif ($this->getFilter() == 'hour') {
            $nbr = 23;
        }

        $i = 0;
        while ($i <= $nbr) {
            $paramarray[] =0;
            $i++;
        }

        while ($paramrow = db_fetch_array($res)) {

            if ($this->getFilter() == 'hour') {
                $i = $paramrow[$this->getFilter()];

            } else {
                $i = $paramrow[$this->getFilter()] - 1; 
            }
            $paramarray[$i] = $paramrow['c'];
        }
        return $paramarray;
    }   

    protected function getMonthDataSQLQuery($startDate, $endDate)
    {
        return sprintf('SELECT month(FROM_UNIXTIME('.$this->field.')) as month,COUNT(*) as c, YEAR(FROM_UNIXTIME('.$this->field.')) as year'.
            ' FROM '.$this->table.
            ' WHERE '.$this->field.' >= %d'.
            ' AND  '.$this->field.' < %d'.
            ' GROUP BY month, year'.
            ' ORDER BY year, month', db_escape_int($startDate), db_escape_int($endDate));
    }

    /**
     * fetchMonthData()
     *
     * @return mixed an array of data according to the parameter choosen by user, 
     * advanced search display by month
     */
    function fetchMonthData()
    {
        $startDate = $this->getStartDate();
        $endDate   = $this->getEndDate();

        $res = db_query($this->getMonthDataSQLQuery($startDate, $endDate));

        while ($paramrow = db_fetch_array($res)) {

            $year  = $paramrow['year'];
            $month = $paramrow['month'];

            $paramarray[$year][$month] = $paramrow['c'];
        }
        return $paramarray;
    }

    protected function getDayDataSQLQuery($startDate, $endDate)
    {
        return sprintf('SELECT day(FROM_UNIXTIME('.$this->field.')) as day,COUNT(*) as c, MONTH(FROM_UNIXTIME('.$this->field.')) as month, YEAR(FROM_UNIXTIME('.$this->field.')) as year'.
            ' FROM '.$this->table.
            ' WHERE '.$this->field.' >= %d'.
            ' AND  '.$this->field.' < %d'.
            ' GROUP BY day, month, year'.
            ' ORDER BY year, month, day', db_escape_int($startDate), db_escape_int($endDate));
    }

    /**
     * fetchDayData()
     *
     * @return mixed an array of data according to the parameter choosen by user, advanced search display by day
     */
    function fetchDayData()
    {
        $startDate = $this->getStartDate();
        $endDate   = $this->getEndDate();

        $res = db_query($this->getDayDataSQLQuery($startDate, $endDate));

        while ($paramrow = db_fetch_array($res)) {

            $year  = $paramrow['year'];
            $month = $paramrow['month'];
            $day   = $paramrow['day'];

            $paramarray[$year][$month][$day] = $paramrow['c'];
        }
        return $paramarray;
    }
}

/**
 * Sessions processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class SessionSample extends Sample
{
    /**
     * Constructor
     */
    function __construct()
    {
        $this->field = 'time';
        $this->table = 'plugin_statistics_user_session';
        parent::__construct();
    }
}

/**
 * Users processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class UserSample extends Sample
{
    /**
     * Constructor
     */
    function __construct()
    {
        $this->field = 'add_date';
        $this->table = 'user';
        parent::__construct();
    }  
}

/**
 * Messages in forums processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class ForumSample extends Sample
{
    /**
     * Constructor
     */
    function __construct()
    {
        $this->field = 'date';
        $this->table = 'forum';
        parent::__construct();
    }
}

/**
 * Files downloaded processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class FiledSample extends Sample
{
    /**
     * Constructor
     */
    function __construct()
    {
        $this->field = 'time';
        $this->table = 'filedownload_log';
        parent::__construct();
    }
}

/**
 * Files released processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnuad.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class FilerSample extends Sample
{
    /**
     * Constructor
     */
    function __construct()
    {
        $this->field = 'release_time';
        $this->table = 'frs_file';
        parent::__construct();
    }
}

/**
 * Project created processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class ProjectSample extends Sample
{
    /**
     * Constructor
     */
    function __construct()
    {
        $this->field = 'register_time';
        $this->table = 'groups';
        parent::__construct();
    }
}

/**
 * Wiki pages viewed processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class WikiSample extends Sample
{
    /**
     * Constructor
     */
    function __construct()
    {
        $this->field = 'time';
        $this->table = 'wiki_log';
        parent::__construct();
    }
}

/**
 * Opened Artifacts processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class OartifactSample extends Sample
{
    /** 
     * Constructor
     */
    function __construct()
    {
        $this->field = 'open_date';
        $this->table = 'artifact';
        parent::__construct();
    }
}

/**
 * Closed Artifacts processing
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class CartifactSample extends Sample
{
    /**
     * Constructor
     */
    function __construct()
    {
        $this->field = 'close_date';
        $this->table = 'artifact';
        parent::__construct();
    }
}

/**
 * Display data
 *
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class SampleGraph
{
    /**
     * data of the graph
     *
     * @type array $graphValues
     */
    var $graphValues;

    /**
     * color of the bar graph
     *
     * @type string $color
     */
    var $color;

    /**
     * title of the graph
     *
     * @type string $title
     */
    var $title;

    /**
     * the selected period
     *
     * @type string $titlePeriod
     */
    var $titlePeriod;

    /**
     * a Graph object 
     *
     * @type Graph $graph
     */
    var $graph;

    /**
     * the parameter selected by user
     *
     * @type string $selectedData
     */
    var $selectedData;

    /**
     * filter of data display
     *
     * @type string $filter
     */
    var $filter;

    /**
     * true if an advanced search is made
     *
     * @type boolean $advsrch
     */
    var $advsrch;

    /**
     * $year the selected year
     *
     * @type int $year
     */
    var $year;

    /**
     * $month the selected month
     *
     * @type int $month
     */
    var $month;

    /**
     * $start the start date
     *
     * @type string $start
     */
    var $start;

    /**
     * $end the end date
     *
     * @type string $end
     */
    var $end;

    /**
     * constructor
     *
     * @param array   $graphValues  array of graph values
     * @param string  $selectedData parameter choosen by user
     * @param string  $filter       the filter
     * @param string  $titlePeriod  the title period
     * @param boolean $advsrch      true if advanced search
     * @param int     $year         the selected year
     * @param int     $month        the selected month
     * @param string  $start        the start date
     * @param string  $end          the end date
     */
    function __construct($graphValues, $selectedData, $filter, $titlePeriod, $advsrch, $year, $month, $start, $end)
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
    function setColor()
    {
        $this->color = '#DCDAD5';
        return true;       
    }

    /**
     * getColor()
     *
     * @return string the color of bar graphs
     */
    function getColor() 
    {
        $this->setColor();      
        return $this->color;
    }

    /**
     * prepare the context of the graph
     *
     * @return void
     */
    function prepareGraph()
    {
        if($this->start !== null && $this->end !== null) {
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

            $nbmonth = ceil(count($this->graphValues)/30);

            //create the graph
            $this->graph = new Graph($nbmonth*500, 450);
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
            $this->graph->legend->Pos(.075/$nbmonth, .05, "right", "top");
        }

        $this->graph->Add($gbparray);

        $color1 = array(195, 225, 255);
        $color2 = array(225,240,255);
        $this->graph->setBackgroundGradient($color1, $color2, GRAD_HOR, BGRAD_MARGIN);

        //manage the Weekend display in the Simple search
        if ($this->advsrch == 0 && $this->filter == 'day') { 

            foreach ($this->graphValues as $key => $val) {

                $testday = date("w", mktime(0, 0, 0, $this->month, $key, $this->year));
                $color3  = "#F4F3F2@0.45";

                //color the Saturday
                if ($testday == 5) {
                    $beginwe = $key;
                    $endwe   = $beginwe + 1;

                    $uband = new PlotBand(VERTICAL, BAND_SOLID, $beginwe, $endwe, $color3);
                    $uband->ShowFrame(false);
                    $uband->SetDensity(80);
                    $ubandarray[] = $uband;

                } elseif ($testday == 6) { //color the Sunday
                    $beginwe = $key;
                    $endwe   = $beginwe + 1;

                    $aband = new PlotBand(VERTICAL, BAND_SOLID, $beginwe, $endwe, $color3);
                    $aband->ShowFrame(false);
                    $aband->SetDensity(80);
                    $abandarray[] = $aband;
                }  
            }   

            for ($i=0; $i < count($ubandarray); $i++) {
                $this->graph->AddBand($ubandarray[$i]);        
            }

            for ($i=0; $i < count($abandarray); $i++) {
                $this->graph->AddBand($abandarray[$i]);        
            }
        }

        //manage the title of the graph
        if ($this->advsrch != 0) { 

            $this->graph->title->Set('New '.$this->selectedData.' by '.$this->filter.' between '.$this->titlePeriod);
        } else {
            $this->graph->title->Set('New '.$this->selectedData.' during '.$this->titlePeriod);
        }
        $this->graph->title->setFont(FF_FONT1, FS_BOLD);
    }   

    /**
     * Display the graph
     *
     * @return void
     */
    function display()
    {
        $this->prepareGraph();

        //init x axis for advanced search display per month
        if ($this->filter == 'month') {

            if ($this->advsrch == 2) {

                $this->minmonth = $this->minmonth-1;
                $this->maxmonth = $this->maxmonth-1;

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
                        $databarx[] = $months[$m%12];
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

                    for ($d = $mind; $d <= $maxd; $d++) {

                        if ($d == 1) {
                            $databarx[] = $d.'/'.date('F', mktime(0, 0, 0, $m, $d, $y));

                        } elseif ($d%5 == 0) {
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
        $this->graph->Stroke();           
    }
}

/**
 * Design pattern factory
 * 
 * @package   Sample
 * @author    Arnaud Salvucci <arnaud.salvucci@st.com>
 * @copyright 2007 STMicroelectronics
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class SampleFactory
{
    /**
     * $sample a Sample object
     *
     * @type Extended sample object
     */
    var $sample;

    /**
     * Constructor
     */
    function __construct()
    {
        $this->Sample = new SessionSample();        
    }

    /**
     * setSample()
     *
     * @param string $character session by default
     *
     * @return Extended          Sample Object. SessionSample by default
     */
    function setSample($character="session")
    {
        switch($character) {

        case 'session':
            $this->sample = new SessionSample();
            break;

        case 'user':
            $this->sample = new UserSample();
            break;

        case 'forum':
            $this->sample = new ForumSample();
            break;

        case 'filedl':
            $this->sample = new FiledSample();
            break;

        case 'file':
            $this->sample = new FilerSample();
            break;

        case 'groups':
            $this->sample = new ProjectSample();
            break;

        case 'wikidl':
            $this->sample =  new WikiSample();
            break;

        case 'oartifact':
            $this->sample =  new OartifactSample();
            break;

        case 'cartifact':
            $this->sample =  new CartifactSample();
            break;

        default:
            $sample = new SessionSample();
            EventManager::instance()->processEvent(
                Statistics_Event::FREQUENCE_STAT_SAMPLE,
                array(
                    'character' => $character,
                    'sample'    => &$sample
                )
            );
            $this->sample = $sample;
            break;
        }
    }

    /**
     * getSimple()
     *
     * @param int $year  0 by default
     * @param int $month 0 by default
     * @param int $day   0 by default
     *
     * @return Extended          Sample Object.
     */
    function getSimple($year=0, $month=0, $day=0)
    {
        $this->sample->initDateSimple($year, $month, $day);
        return $this->sample;
    }

    /**
     * getAdvanced()
     *
     * @param string $startdate the date the graph start
     * @param string $enddate   the date the graph end
     * @param string $filter    the filter of display 
     * (group by month, group by day,group by hour, month, day)
     *
     * @return Extended           Sample Object.
     */
    function getAdvanced($startdate, $enddate, $filter)
    {
        $this->sample->initDateAdvanced($startdate, $enddate, $filter);
        return $this->sample;
    }
}

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginAvailable($p)) {
    $GLOBALS['Response']->redirect('/');
}

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$sampleFactory = new SampleFactory();

$request = HTTPRequest::instance();
$sampleFactory->setSample($request->get('data'));


//advanced search
if ($request->get('start') && $request->get('end') && $request->get('filter')) {

    //if user make a mistake in the advanced search
    if (strtotime($request->get('start')) >= strtotime($request->get('end')) || $request->get('start') == '' || $request->get('end') == '' ) {
        $sampleFactory->setSample('session');
        $statGraph   = $sampleFactory->getSimple(date('Y'), 0, 0);
        $sampleGraph = new SampleGraph($statGraph->fetchData(), 
                                       'session', 
                                       'month', 
                                       $statGraph->getTitlePeriod(), 
                                       0, 
                                       null, 
                                       null, 
                                       null, 
                                       null);
    } else {
        $statGraph = $sampleFactory->getAdvanced($request->get('start'),
                                                 $request->get('end'),
                                                 $request->get('filter'));

        switch ($request->get('filter')) {

        case 'month1':
            $sampleGraph = new SampleGraph($statGraph->fetchMonthData(),
                                           $request->get('data'), 
                                           $statGraph->getFilter(), 
                                           $statGraph->getTitlePeriod(), 
                                           2,
                                           null, 
                                           null,
                                           $request->get('start'),
                                           $request->get('end'));
            break;

        case 'day1':
            $sampleGraph = new SampleGraph($statGraph->fetchDayData(), 
                                           $request->get('data'), 
                                           $statGraph->getFilter(), 
                                           $statGraph->getTitlePeriod(), 
                                           3,
                                           null, 
                                           null,
                                           $request->get('start'),
                                           $request->get('end'));
            break;

        default:
            $sampleGraph = new SampleGraph($statGraph->fetchData(), 
                                           $request->get('data'), 
                                           $statGraph->getFilter(), 
                                           $statGraph->getTitlePeriod(), 
                                           $request->get('advsrch'),
                                           null,
                                           null,
                                           null,
                                           null);
            break;
        }
    }    
} else { //simple search
    $statGraph = $sampleFactory->getSimple($request->get('year'),
                                           $request->get('month'),
                                           $request->get('day'));    

    $sampleGraph = new SampleGraph($statGraph->fetchData(), 
                                   $request->get('data'), 
                                   $statGraph->getFilter(), 
                                   $statGraph->getTitlePeriod(), 
                                   $request->get('advsrch'),
                                   $request->get('year'),
                                   $request->get('month'),
                                   null,
                                   null);
}

$sampleg = $sampleGraph->display();
?>
