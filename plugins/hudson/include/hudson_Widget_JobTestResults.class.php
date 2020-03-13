<?php
/**
 * Copyright (c) Enalean, 2016-Present. All rights reserved
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

use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Hudson\HudsonJobBuilder;
use Tuleap\Hudson\TestResultPieChart\TestResultsPieChartDisplayer;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class hudson_Widget_JobTestResults extends HudsonJobWidget
{
    /**
     * @var HudsonJob
     */
    private $job;

    /**
     * @var HudsonTestResult
     */
    private $test_result;
    /**
     * @var HudsonJobBuilder
     */
    private $hudson_job_builder;

    /**
     * @param String           $owner_type The owner type
     * @param Int              $owner_id   The owner id
     * @param MinimalHudsonJobFactory $factory    The HudsonJob factory
     *
     * @return void
     */
    public function __construct($owner_type, $owner_id, MinimalHudsonJobFactory $factory, HudsonJobBuilder $hudson_job_builder)
    {
        $request = HTTPRequest::instance();
        if ($owner_type == UserDashboardController::LEGACY_DASHBOARD_TYPE) {
            $this->widget_id = 'plugin_hudson_my_jobtestresults';
            $this->group_id  = $owner_id;
        } else {
            $this->widget_id = 'plugin_hudson_project_jobtestresults';
            $this->group_id  = $request->get('group_id');
        }
        parent::__construct($this->widget_id, $factory);

        $this->setOwner($owner_id, $owner_type);
        $this->hudson_job_builder = $hudson_job_builder;
    }

    public function getTitle()
    {
        $title = '';
        if ($this->job && $this->test_result) {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults_widget_title', array($this->job->getName(), $this->test_result->getPassCount(), $this->test_result->getTotalCount()));
        } elseif ($this->job && ! $this->test_result) {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults_projectname', array($this->job->getName()));
        } else {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults');
        }
        return $title;
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('plugin_hudson', 'widget_description_testresults');
    }

    public function loadContent($id)
    {
        $this->content_id = $id;
    }

    protected function initContent()
    {
        $job_id = $this->getJobIdFromWidgetConfiguration();
        if ($job_id) {
            $this->job_id = $job_id;

            $jobs = $this->getAvailableJobs();

            if (array_key_exists($this->job_id, $jobs)) {
                try {
                    $used_job          = $jobs[$this->job_id];
                    $this->job         = $this->hudson_job_builder->getHudsonJob($used_job);
                    $this->test_result = new HudsonTestResult(
                        $this->job->getUrl(),
                        HttpClientFactory::createClient(),
                        HTTPFactoryBuilder::requestFactory()
                    );
                } catch (Exception $e) {
                    $this->test_result = null;
                }
            } else {
                $this->job = null;
                $this->test_result = null;
            }
        }
    }

    public function getContent()
    {
        $this->initContent();

        $html = '';
        if ($this->job !== null && $this->test_result !== null) {
            $pie_displayer = new TestResultsPieChartDisplayer();

            $pie_displayer->displayTestResultsPieChart(
                $this->getInstanceId(),
                $this->job_id,
                $this->group_id,
                $this->test_result
            );
        } else {
            if ($this->job != null) {
                $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_tests_not_found');
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_job_not_found');
            }
        }

        return $html;
    }

    public function getJavascriptDependencies(): array
    {
        return [
            ['file' => $this->getAssets()->getFileURL('test-results-pie.js')]
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection([new CssAsset($this->getAssets(), 'bp-style')]);
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/hudson',
            '/assets/hudson'
        );
    }
}
