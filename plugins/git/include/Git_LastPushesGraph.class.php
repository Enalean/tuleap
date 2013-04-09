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

require_once 'common/chart/Chart.class.php';

class Git_LastPushesGraph {
    const MAX_WEEKSNUMBER  = 25;
    const WEEKS_IN_SECONDS = 604800;

    /**
     * @var Boolean
     */
    public $displayChart;

    /**
     * @var Array
     */
    public $repoList;

    /**
     * @var Integer
     */
    public $weeksNumber;

    /**
     * @var String
     */
    protected $legend;

    /**
     * @var Array
     */
    protected $dates   = array();

    /**
     * @var Array
     */
    protected $weekNum = array();

    /**
     * @var Array
     */
    protected $year    = array();
    
    /**
     * Constructor.
     *
     * @param Integer $groupId     Project Id
     * @param Integer $weeksNumber Statistics duration in weeks
     *
     * @return Void
     */
    public function __construct($groupId, $weeksNumber) {
        $dao                = new GitDao();
        // TODO: Optionally include presonal forks in repo list
        $allRepositories    = $dao->getProjectRepositoryList($groupId);
        $um                 = UserManager::instance();
        $user               = $um->getCurrentUser();
        $repoFactory        = new GitRepositoryFactory($dao, ProjectManager::instance());
        foreach ($allRepositories as $repo) {
            $repository = $repoFactory->getRepositoryById($repo['repository_id']);
            if ($repository->userCanRead($user)) {
                $this->repoList[] = $repository;
            }
        }
        $this->displayChart = false;
        $this->weeksNumber  = min($weeksNumber, self::MAX_WEEKSNUMBER);
        // Init some class properties according to 'weeks number' parameter         
        $today              = $_SERVER['REQUEST_TIME'];
        $startPeriod        = strtotime("-$this->weeksNumber weeks");
        $weekInSeconds      = self::WEEKS_IN_SECONDS ;
        for ($i = $startPeriod+$weekInSeconds ; $i < $today+$weekInSeconds ; $i += $weekInSeconds) {
            $this->dates[]   = date('M d', $i);
            $this->weekNum[] = intval(date('W', $i));
            $this->year[]    = intval(date('Y', $i));
        }
    }

    /**
     * Manage graph axis
     *
     * @return Chart
     */
    private function prepareGraph() {
        $nbRepo = count($this->repoList);
        $graph  = new Chart(500, 300 + 16 * $nbRepo);
        $graph->SetScale('textint');
        $graph->img->SetMargin(40, 20, 20, 80 + 16 * $nbRepo);
        $graph->SetMarginColor('white');
        $graph->title->Set($GLOBALS['Language']->getText('plugin_git', 'widget_project_pushes_title'));
        $graph->xaxis->SetLabelMargin(30);
        $graph->xaxis->SetLabelAlign('right', 'center');
        $graph->xaxis->SetTickLabels($this->dates);
        $graph->yaxis->SetPos('min');
        $graph->yaxis->SetTitle($GLOBALS['Language']->getText('plugin_git', 'widget_project_pushes_label'), 'center');
        $graph->yaxis->title->SetAngle(90);
        $graph->yaxis->title->Align('center', 'top');
        $graph->yaxis->SetTitleMargin(30);
        $graph->yaxis->SetLabelAlign('center', 'top');
        $graph->legend->Pos(0.1, 0.98, 'right', 'bottom');
        return $graph;
    }

    /**
     * Build a JpGraph barPlot object with retrived data.
     *
     * @return BarPlot
     */
    private function displayRepositoryPushesByWeek() {
        $nbRepo   = count($this->repoList);
        $colors   = array_slice($GLOBALS['HTML']->getChartColors(), 0, $nbRepo);
        $nbColors = count($colors);
        $i        = 0;
        $bplot    = array();
        foreach ($this->repoList as $repository) {
            $this->legend = null;
            $pushes = $this->getRepositoryPushesByWeek($repository);
            if ($this->displayChart) {
                $b2plot = new BarPlot($pushes);
                $color  = $colors[$i++ % $nbColors];
                $b2plot->SetColor($color.':0.7');
                $b2plot->setFillColor($color);
                if (!empty($this->legend)) {
                    $b2plot->SetLegend($this->legend);
                }
                $bplot[] = $b2plot;
            }
        }
        return $bplot;
    }

    /**
     * Collect, into an array, logged git pushes matching a given git repository for the given duration.
     *
     * @param GitRepository $repository Git repository we want to fetch its pushes
     *
     * @return Array
     */
    private function getRepositoryPushesByWeek(GitRepository $repository) {
        $pushes    = array();
        $gitLogDao = new Git_LogDao();
        foreach ($this->weekNum as $key => $w) {
            $res = $gitLogDao->getRepositoryPushesByWeek($repository->getId(), $w, $this->year[$key]);
            if ($res && !$res->isError() && $res->valid()) {
                $row          = $res->getRow();
                $pushes[$key] = intval($row['pushes']);
                if ($pushes[$key] > 0) {
                    $this->displayChart = true;
                    $this->legend       = $repository->getFullName();
                }
            }
            $pushes = array_pad($pushes, $this->weeksNumber, 0);
        }  
        return $pushes;      
    }
    
    /**
     * Create a JpGraph accumulated barPlot chart 
     *
     * @param Array $bplot Array of JpGraph barPlot objects
     * @param Chart $graph The output graph that will contains accumulated barPlots
     *
     * @return Void
     */
    private function displayAccumulatedGraph($bplot, $graph) {
        $abplot = new AccBarPlot($bplot);
        $abplot->SetAbsWidth(10);
        $graph->Add($abplot);
        $graph->Stroke();
    }

    /**
     * Display the graph else an error if no pushes for this period
     * 
     * @return void
     */
    public function display() {
        $graph = $this->prepareGraph();
        $bplot = $this->displayRepositoryPushesByWeek();
        if ($this->displayChart) {
            $this->displayAccumulatedGraph($bplot, $graph);
        } else {
            $graph->displayMessage($GLOBALS['Language']->getText('plugin_git', 'widget_project_pushes_error', $this->weeksNumber));
        }
    }
}
?>
