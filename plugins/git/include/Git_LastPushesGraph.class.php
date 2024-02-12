<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Chart\Chart;
use Tuleap\Chart\ColorsForCharts;

class Git_LastPushesGraph
{
    public const MAX_WEEKSNUMBER  = 25;
    public const WEEKS_IN_SECONDS = 604800;

    public const NUMBER_OF_REPOSITORIES_BEFORE_GRAPH_LABEL_BREAK_DISPLAY = 15;

    /**
     * @var bool
     */
    public $displayChart;

    /**
     * @var Array
     */
    public $repoList = [];

    /**
     * @var int
     */
    public $weeksNumber;

    /**
     * @var String
     */
    protected $legend;

    /**
     * @var Array
     */
    protected $dates = [];

    /**
     * @var Array
     */
    protected $weekNum = [];

    /**
     * @var Array
     */
    protected $year = [];

    /**
     *
     *
     * @param int $groupId Project Id
     * @param int $weeksNumber Statistics duration in weeks
     *
     * @return Void
     */
    public function __construct($groupId, $weeksNumber)
    {
        $dao = new GitDao();
        // TODO: Optionally include presonal forks in repo list
        $allRepositories = $dao->getProjectRepositoryList($groupId);
        $um              = UserManager::instance();
        $user            = $um->getCurrentUser();
        $repoFactory     = new GitRepositoryFactory($dao, ProjectManager::instance());
        foreach ($allRepositories as $repo) {
            $repository = $repoFactory->getRepositoryById($repo['repository_id']);
            if ($repository->userCanRead($user)) {
                $this->repoList[] = $repository;
            }
        }
        $this->displayChart = false;
        $this->weeksNumber  = min($weeksNumber, self::MAX_WEEKSNUMBER);
        // Init some class properties according to 'weeks number' parameter
        $today         = \Tuleap\Request\RequestTime::getTimestamp();
        $startPeriod   = strtotime("-$this->weeksNumber weeks");
        $weekInSeconds = self::WEEKS_IN_SECONDS;
        for ($i = $startPeriod + $weekInSeconds; $i < $today + $weekInSeconds; $i += $weekInSeconds) {
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
    private function prepareGraph()
    {
        $nbRepo = count($this->repoList);

        $columns                 = $this->getNumberOfColumnForLegendWhenMultipleRepositories($nbRepo);
        $margin_for_repositories = $this->getNumberOfRepositoriesAddedToSpacesLeftToTotalDirectory($nbRepo, $columns);
        $graph_margin            = $this->getGraphMargin($margin_for_repositories, $columns, $nbRepo);

        $graph = new Chart(500, 300 + 16 * $graph_margin);
        $graph->SetScale('textint');
        $graph->img->SetMargin(40, 20, 20, 80 + 16 * $graph_margin);
        $graph->SetMarginColor('white');
        $graph->title->Set(dgettext('tuleap-git', 'Last Git pushes'));
        $graph->xaxis->SetLabelMargin(30);
        $graph->xaxis->SetLabelAlign('right', 'center');
        $graph->xaxis->SetTickLabels($this->dates);
        $graph->yaxis->SetPos('min');
        $graph->yaxis->SetTitle(dgettext('tuleap-git', 'Pushes'), 'center');
        $graph->yaxis->title->SetAngle(90);
        $graph->yaxis->title->Align('center', 'top');
        $graph->yaxis->SetTitleMargin(30);
        $graph->yaxis->SetLabelAlign('center', 'top');
        $graph->legend->Pos(0.1, 0.98, 'right', 'bottom');
        if ($columns > 1) {
            $graph->legend->SetColumns($columns);
        }
        return $graph;
    }

    private function getNumberOfColumnForLegendWhenMultipleRepositories($number_of_repositories)
    {
        return ceil($number_of_repositories / self::NUMBER_OF_REPOSITORIES_BEFORE_GRAPH_LABEL_BREAK_DISPLAY);
    }

    private function getNumberOfRepositoriesAddedToSpacesLeftToTotalDirectory($number_of_repositories, $columns)
    {
        if ($columns > 0) {
            return ceil($number_of_repositories / $columns);
        }

        return 0;
    }

    private function getGraphMargin($margin_for_repositories, $columns, $number_of_repositories)
    {
        if ($columns > 0) {
            return ($number_of_repositories + $margin_for_repositories)  / $columns;
        } else {
            return ($number_of_repositories + $margin_for_repositories);
        }
    }

    /**
     * Build a JpGraph barPlot object with retrived data.
     *
     * @return BarPlot
     */
    private function displayRepositoryPushesByWeek()
    {
        $colors_for_charts = new ColorsForCharts();
        $nbRepo            = count($this->repoList);
        $colors            = array_slice($colors_for_charts->getChartColors(), 0, $nbRepo);
        $nbColors          = count($colors);
        $i                 = 0;
        $bplot             = [];
        foreach ($this->repoList as $repository) {
            $this->legend = null;
            $pushes       = $this->getRepositoryPushesByWeek($repository);
            if ($this->displayChart) {
                $b2plot = new BarPlot($pushes);
                $color  = $colors[$i++ % $nbColors];
                $b2plot->SetColor($color . ':0.7');
                $b2plot->setFillColor($color);
                if (! empty($this->legend)) {
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
    private function getRepositoryPushesByWeek(GitRepository $repository)
    {
        $pushes    = [];
        $gitLogDao = new Git_LogDao();
        foreach ($this->weekNum as $key => $w) {
            $rows = $gitLogDao->getRepositoryPushesByWeek($repository->getId(), $w, $this->year[$key]);
            foreach ($rows as $row) {
                $pushes[$key] = (int) $row['pushes'];
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
    private function displayAccumulatedGraph($bplot, $graph)
    {
        $abplot = new AccBarPlot($bplot);
        $abplot->SetAbsWidth(10);
        $graph->Add($abplot);
        $graph->Stroke();
    }

    /**
     * Display the graph else an error if no pushes for this period
     */
    public function display()
    {
        $graph = $this->prepareGraph();
        $bplot = $this->displayRepositoryPushesByWeek();
        if ($this->displayChart) {
            $this->displayAccumulatedGraph($bplot, $graph);
        } else {
            $graph->displayMessage(sprintf(dgettext('tuleap-git', 'There is no logged pushes in the last %1$s week(s)'), $this->weeksNumber));
        }
        die();
    }
}
