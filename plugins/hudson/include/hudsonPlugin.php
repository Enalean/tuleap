<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Hudson\HudsonJobBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Plugin\PluginWithLegacyInternalRouting;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

class hudsonPlugin extends PluginWithLegacyInternalRouting //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const ICONS_PATH = '/plugins/hudson/themes/default/images/ic/';

    public function __construct($id)
    {
        parent::__construct($id);

        bindtextdomain('tuleap-hudson', __DIR__ . '/../site-content');

        $this->addHook('javascript_file', 'jsFile', false);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);

        $this->addHook('project_is_deleted', 'projectIsDeleted', false);

        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);

        $this->addHook('get_available_reference_natures', 'getAvailableReferenceNatures', false);
        $this->addHook(\Tuleap\Reference\ReferenceGetTooltipContentEvent::NAME);
        $this->addHook(Event::AJAX_REFERENCE_SPARKLINE, 'ajax_reference_sparkline', false);
        $this->addHook('statistics_collector', 'statistics_collector', false);

        $this->listenToCollectRouteEventWithDefaultController();
    }

    private function canIncludeStylesheets()
    {
        return strpos($_SERVER['REQUEST_URI'], HUDSON_BASE_URL . '/') === 0;
    }

    public function getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'hudsonPluginInfo')) {
            require_once('hudsonPluginInfo.class.php');
            $this->pluginInfo = new hudsonPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname()
    {
        return 'hudson';
    }

    public function service_classnames(array &$params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['classnames'][$this->getServiceShortname()] = \Tuleap\Hudson\HudsonService::class;
    }

    public function cssFile($params)
    {
        // Only show the stylesheet if we're actually in the hudson pages.
        // This stops styles inadvertently clashing with the main site.
        if (
            $this->canIncludeStylesheets() ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('default-style.css') . '" />';
        }
    }

    public function jsFile($params)
    {
        // Only include the js files if we're actually in the IM pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>' . "\n";
            echo $this->getAssets()->getHTMLSnippet('hudson_tab.js');
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/hudson',
            '/assets/hudson'
        );
    }

    /**
     * When a project is deleted,
     * we delete all the hudson jobs of this project
     *
     * @param mixed $params ($param['group_id'] the ID of the deleted project)
     */
    public function projectIsDeleted($params)
    {
        $group_id = $params['group_id'];
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->deleteHudsonJobsByGroupID($group_id);
    }


    protected $hudsonJobFactory = null;

    protected function getMinimalHudsonJobFactory()
    {
        if (!$this->hudsonJobFactory) {
            $this->hudsonJobFactory = new MinimalHudsonJobFactory();
        }
        return $this->hudsonJobFactory;
    }

    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event)
    {
        $request = HTTPRequest::instance();

        $user        = UserManager::instance()->getCurrentUser();
        $hf          = $this->getMinimalHudsonJobFactory();
        $job_builder = new HudsonJobBuilder(HTTPFactoryBuilder::requestFactory(), HttpClientFactory::createAsyncClient());

        switch ($get_widget_event->getName()) {
            // MY
            case 'plugin_hudson_my_jobs':
                require_once('hudson_Widget_MyMonitoredJobs.class.php');
                $get_widget_event->setWidget(new hudson_Widget_MyMonitoredJobs($user->getId(), $this, $hf, $job_builder));
                break;
            case 'plugin_hudson_my_joblastbuilds':
                require_once('hudson_Widget_JobLastBuilds.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobLastBuilds(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf, $job_builder));
                break;
            case 'plugin_hudson_my_jobtestresults':
                require_once('hudson_Widget_JobTestResults.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobTestResults(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf, $job_builder));
                break;
            case 'plugin_hudson_my_jobtesttrend':
                require_once('hudson_Widget_JobTestTrend.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobTestTrend(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf, $job_builder));
                break;
            case 'plugin_hudson_my_jobbuildhistory':
                require_once('hudson_Widget_JobBuildHistory.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobBuildHistory(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf, $job_builder));
                break;
            case 'plugin_hudson_my_joblastartifacts':
                require_once('hudson_Widget_JobLastArtifacts.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobLastArtifacts(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf, $job_builder));
                break;

            // PROJECT
            case 'plugin_hudson_project_jobsoverview':
                require_once('hudson_Widget_ProjectJobsOverview.class.php');
                $get_widget_event->setWidget(new hudson_Widget_ProjectJobsOverview($request->get('group_id'), $this, $hf, $job_builder));
                break;
            case 'plugin_hudson_project_joblastbuilds':
                require_once('hudson_Widget_JobLastBuilds.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobLastBuilds(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf, $job_builder));
                break;
            case 'plugin_hudson_project_jobtestresults':
                require_once('hudson_Widget_JobTestResults.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobTestResults(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf, $job_builder));
                break;
            case 'plugin_hudson_project_jobtesttrend':
                require_once('hudson_Widget_JobTestTrend.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobTestTrend(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf, $job_builder));
                break;
            case 'plugin_hudson_project_jobbuildhistory':
                require_once('hudson_Widget_JobBuildHistory.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobBuildHistory(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf, $job_builder));
                break;
            case 'plugin_hudson_project_joblastartifacts':
                require_once('hudson_Widget_JobLastArtifacts.class.php');
                $get_widget_event->setWidget(new hudson_Widget_JobLastArtifacts(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf, $job_builder));
                break;
        }
    }

    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event)
    {
        $event->addWidget('plugin_hudson_my_jobs');
        $event->addWidget('plugin_hudson_my_joblastbuilds');
        $event->addWidget('plugin_hudson_my_jobtestresults');
        $event->addWidget('plugin_hudson_my_jobtesttrend');
        $event->addWidget('plugin_hudson_my_jobbuildhistory');
        $event->addWidget('plugin_hudson_my_joblastartifacts');
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event)
    {
        $event->addWidget('plugin_hudson_project_jobsoverview');
        $event->addWidget('plugin_hudson_project_joblastbuilds');
        $event->addWidget('plugin_hudson_project_jobtestresults');
        $event->addWidget('plugin_hudson_project_jobtesttrend');
        $event->addWidget('plugin_hudson_project_jobbuildhistory');
        $event->addWidget('plugin_hudson_project_joblastartifacts');
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(array(
            'plugin_hudson_my_jobs',
            'plugin_hudson_my_joblastbuilds',
            'plugin_hudson_my_jobtestresults',
            'plugin_hudson_my_jobtesttrend',
            'plugin_hudson_my_jobbuildhistory',
            'plugin_hudson_my_joblastartifacts',
            'plugin_hudson_project_jobsoverview',
            'plugin_hudson_project_joblastbuilds',
            'plugin_hudson_project_jobtestresults',
            'plugin_hudson_project_jobtesttrend',
            'plugin_hudson_project_jobbuildhistory',
            'plugin_hudson_project_joblastartifacts'
        ));
    }

    public function getAvailableReferenceNatures($params)
    {
        $hudson_plugin_reference_natures = array(
            'hudson_build'  => array('keyword' => 'build', 'label' => $GLOBALS['Language']->getText('plugin_hudson', 'reference_build_nature_key')),
            'hudson_job' => array('keyword' => 'job', 'label' => $GLOBALS['Language']->getText('plugin_hudson', 'reference_job_nature_key')));
        $params['natures'] = array_merge($params['natures'], $hudson_plugin_reference_natures);
    }

    public function referenceGetTooltipContentEvent(Tuleap\Reference\ReferenceGetTooltipContentEvent $event)
    {
        $html_purifier = Codendi_HTMLPurifier::instance();

        switch ($event->getReference()->getNature()) {
            case 'hudson_build':
                $val = $event->getValue();
                $group_id = $event->getProject()->getID();
                $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
                if (strpos($val, "/") !== false) {
                    $arr = explode("/", $val);
                    $job_name = $arr[0];
                    $build_id = $arr[1];
                    $dar = $job_dao->searchByJobName($job_name, $group_id);
                } else {
                    $build_id = $val;
                    $dar = $job_dao->searchByGroupID($group_id);
                    if ($dar->rowCount() != 1) {
                        $dar = null;
                    }
                }
                if ($dar && $dar->valid()) {
                    $row         = $dar->current();
                    $build       = new HudsonBuild(
                        $row['job_url'] . '/' . $build_id . '/',
                        HttpClientFactory::createClient(),
                        HTTPFactoryBuilder::requestFactory()
                    );
                    $event->setOutput(
                        '<strong>' . $GLOBALS['Language']->getText('plugin_hudson', 'build_time') . '</strong> ' . $html_purifier->purify($build->getBuildTime()) . '<br />' .
                        '<strong>' . $GLOBALS['Language']->getText('plugin_hudson', 'status') . '</strong> ' . $html_purifier->purify($build->getResult())
                    );
                } else {
                    $event->setOutput('<span class="error">' . $GLOBALS['Language']->getText('plugin_hudson', 'error_object_not_found') . '</span>');
                }
                break;
            case 'hudson_job':
                $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
                $job_name = $event->getValue();
                $group_id = $event->getProject()->getID();
                $dar = $job_dao->searchByJobName($job_name, $group_id);
                if ($dar->valid()) {
                    $row = $dar->current();
                    try {
                        $minimal_job_factory = $this->getMinimalHudsonJobFactory();
                        $job_builder         = new HudsonJobBuilder(HTTPFactoryBuilder::requestFactory(), HttpClientFactory::createAsyncClient());
                        $job                 = $job_builder->getHudsonJob(
                            $minimal_job_factory->getMinimalHudsonJob($row['job_url'], '')
                        );
                        $job_id      = $row['job_id'];

                        $html  = '';
                        $html .= '<table>';
                        $html .= ' <tr>';
                        $html .= '  <td colspan="2">';
                        $html .= '   <img src="' . $job->getStatusIcon() . '" width="10" height="10" /> ' . $html_purifier->purify($job->getName()) . ':';
                        $html .= '  </td>';
                        $html .= ' </tr>';
                        $html .= ' <tr>';
                        $html .= '  <td>';
                        $html .= '   <ul>';
                        if ($job->hasBuilds()) {
                            $html .= ' <li>' . $GLOBALS['Language']->getText('plugin_hudson', 'last_build') . ' <a href="/plugins/hudson/?action=view_build&group_id=' . $group_id . '&job_id=' . $job_id . '&build_id=' . $job->getLastBuildNumber() . '"># ' . $job->getLastBuildNumber() . '</a></li>';
                            $html .= ' <li>' . $GLOBALS['Language']->getText('plugin_hudson', 'last_build_success') . ' <a href="/plugins/hudson/?action=view_build&group_id=' . $group_id . '&job_id=' . $job_id . '&build_id=' . $job->getLastSuccessfulBuildNumber() . '"># ' . $job->getLastSuccessfulBuildNumber() . '</a></li>';
                            $html .= ' <li>' . $GLOBALS['Language']->getText('plugin_hudson', 'last_build_failure') . ' <a href="/plugins/hudson/?action=view_build&group_id=' . $group_id . '&job_id=' . $job_id . '&build_id=' . $job->getLastFailedBuildNumber() . '"># ' . $job->getLastFailedBuildNumber() . '</a></li>';
                        } else {
                            $html .= ' <li>' . $GLOBALS['Language']->getText('plugin_hudson', 'widget_build_not_found') . '</li>';
                        }
                        $html .= '   </ul>';
                        $html .= '  </td>';
                        $html .= '  <td class="widget_lastbuilds_weather">';
                        $html .= $GLOBALS['Language']->getText('plugin_hudson', 'weather_report') . '<img src="' . $job->getWeatherReportIcon() . '" align="middle" />';
                        $html .= '  </td>';
                        $html .= ' </tr>';
                        $html .= '</table>';
                        $event->setOutput($html);
                    } catch (Exception $e) {
                    }
                } else {
                    $event->setOutput('<span class="error">' . $GLOBALS['Language']->getText('plugin_hudson', 'error_object_not_found') . '</span>');
                }
                break;
        }
    }

    public function ajax_reference_sparkline($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        require_once('HudsonJob.class.php');
        require_once('HudsonBuild.class.php');
        require_once('hudson_Widget_JobLastBuilds.class.php');

        $ref = $params['reference'];
        switch ($ref->getNature()) {
            case 'hudson_build':
                $val = $params['val'];
                $group_id = $params['group_id'];
                $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
                if (strpos($val, "/") !== false) {
                    $arr = explode("/", $val);
                    $job_name = $arr[0];
                    $build_id = $arr[1];
                    $dar = $job_dao->searchByJobName($job_name, $group_id);
                } else {
                    $build_id = $val;
                    $dar = $job_dao->searchByGroupID($group_id);
                    if ($dar->rowCount() != 1) {
                        $dar = null;
                    }
                }
                if ($dar && $dar->valid()) {
                    $row = $dar->current();
                    try {
                        $build               = new HudsonBuild(
                            $row['job_url'] . '/' . $build_id . '/',
                            HttpClientFactory::createClient(),
                            HTTPFactoryBuilder::requestFactory()
                        );
                        $params['sparkline'] = $build->getStatusIcon();
                    } catch (Exception $e) {
                    }
                }
                break;
            case 'hudson_job':
                $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
                $job_name = $params['val'];
                $group_id = $params['group_id'];
                $dar = $job_dao->searchByJobName($job_name, $group_id);
                if ($dar->valid()) {
                    $row = $dar->current();
                    try {
                        $minimal_job_factory = $this->getMinimalHudsonJobFactory();
                        $job_builder         = new HudsonJobBuilder(HTTPFactoryBuilder::requestFactory(), HttpClientFactory::createAsyncClient());
                        $job                 = $job_builder->getHudsonJob(
                            $minimal_job_factory->getMinimalHudsonJob($row['job_url'], '')
                        );
                        $params['sparkline'] = $job->getStatusIcon();
                    } catch (Exception $e) {
                    }
                }
                break;
        }
    }

    public function process(): void
    {
        require_once('hudson.class.php');
        $controler = new hudson();
        $controler->process();
    }

    /**
     * Display CI statistics in CSV format
     *
     * @param Array $params parameters of the event
     *
     * @return void
     */
    public function statistics_collector($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (!empty($params['formatter'])) {
            $formatter = $params['formatter'];
            $jobDao = new PluginHudsonJobDao(CodendiDataAccess::instance());
            $dar = $jobDao->countJobs($formatter->groupId);
            $count = 0;
            if ($dar && !$dar->isError()) {
                    $row = $dar->getRow();
                if ($row) {
                    $count = $row['count'];
                }
            }
            $formatter->clearContent();
            $formatter->addEmptyLine();
            $formatter->addLine(array($GLOBALS['Language']->getText('plugin_hudson', 'title')));
            $formatter->addLine(array($GLOBALS['Language']->getText('plugin_hudson', 'job_count', array(date('Y-m-d'))), $count));
            echo $formatter->getCsvContent();
            $formatter->clearContent();
        }
    }
}
