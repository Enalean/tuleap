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

use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Hudson\HudsonJobBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Plugin\PluginWithLegacyInternalRouting;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Reference\Nature;
use Tuleap\Reference\NatureCollection;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Hudson\Reference\HudsonCrossReferenceOrganizer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

class hudsonPlugin extends PluginWithLegacyInternalRouting implements \Tuleap\Project\Service\PluginWithService //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const ICONS_PATH          = '/plugins/hudson/themes/default/images/ic/';
    public const HUDSON_JOB_NATURE   = 'hudson_job';
    public const HUDSON_BUILD_NATURE = 'hudson_build';

    public function __construct($id)
    {
        parent::__construct($id);

        bindtextdomain('tuleap-hudson', __DIR__ . '/../site-content');

        $this->addHook('javascript_file', 'jsFile');
        $this->addHook('cssfile', 'cssFile');

        $this->addHook(ProjectStatusUpdate::NAME);

        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);

        $this->addHook(NatureCollection::NAME);
        $this->addHook(\Tuleap\Reference\ReferenceGetTooltipContentEvent::NAME);
        $this->addHook(Event::AJAX_REFERENCE_SPARKLINE, 'ajax_reference_sparkline');
        $this->addHook('statistics_collector', 'statistics_collector');
        $this->addHook(CrossReferenceByNatureOrganizer::NAME);
    }

    private function canIncludeStylesheets()
    {
        return strpos($_SERVER['REQUEST_URI'], HUDSON_BASE_URL . '/') === 0;
    }

    public function getPluginInfo()
    {
        if (! is_a($this->pluginInfo, 'hudsonPluginInfo')) {
            require_once('hudsonPluginInfo.class.php');
            $this->pluginInfo = new hudsonPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname(): string
    {
        return 'hudson';
    }

    /**
     * @see Event::SERVICE_CLASSNAMES
     * @param array{classnames: array<string, class-string>, project: \Project} $params
     */
    public function serviceClassnames(array &$params): void
    {
        $params['classnames'][$this->getServiceShortname()] = \Tuleap\Hudson\HudsonService::class;
    }

    /**
     * @see Event::SERVICE_IS_USED
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        // nothing to do for hudson
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        // nothing to do for hudson
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        // nothing to do for hudson
    }

    public function addMissingService(AddMissingService $event): void
    {
        // nothing to do for hudson
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
            $layout = $params['layout'];
            assert($layout instanceof \Layout);
            $layout->includeJavascriptFile('/scripts/scriptaculous/scriptaculous.js');
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($this->getAssets(), 'hudson_tab.js'));
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/hudson'
        );
    }

    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status === \Project::STATUS_DELETED) {
            $job_dao = new PluginHudsonJobDao();
            $job_dao->deleteHudsonJobsByGroupID($event->project->getID());
        }
    }


    protected $hudsonJobFactory = null;

    protected function getMinimalHudsonJobFactory()
    {
        if (! $this->hudsonJobFactory) {
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
        $this->removeOrphanWidgets([
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
            'plugin_hudson_project_joblastartifacts',
        ]);
    }

    public function getAvailableReferenceNatures(NatureCollection $natures): void
    {
        $natures->addNature(
            self::HUDSON_BUILD_NATURE,
            new Nature(
                'build',
                'fas fa-sync-alt',
                dgettext('tuleap-hudson', 'Jenkins Build'),
                true
            )
        );
        $natures->addNature(
            self::HUDSON_JOB_NATURE,
            new Nature(
                'job',
                'fas fa-sync-alt',
                dgettext('tuleap-hudson', 'Jenkins Job'),
                true
            )
        );
    }

    public function referenceGetTooltipContentEvent(Tuleap\Reference\ReferenceGetTooltipContentEvent $event)
    {
        $html_purifier = Codendi_HTMLPurifier::instance();

        switch ($event->getReference()->getNature()) {
            case self::HUDSON_BUILD_NATURE:
                $val      = $event->getValue();
                $group_id = $event->getProject()->getID();
                $job_dao  = new PluginHudsonJobDao(CodendiDataAccess::instance());
                if (strpos($val, "/") !== false) {
                    $arr      = explode("/", $val);
                    $job_name = $arr[0];
                    $build_id = $arr[1];
                    $dar      = $job_dao->searchByJobName($job_name, $group_id);
                } else {
                    $build_id = $val;
                    $dar      = $job_dao->searchByGroupID($group_id);
                    if ($dar->rowCount() != 1) {
                        $dar = null;
                    }
                }
                if ($dar && $dar->valid()) {
                    $row   = $dar->current();
                    $build = new HudsonBuild(
                        $row['job_url'] . '/' . $build_id . '/',
                        HttpClientFactory::createClient(),
                        HTTPFactoryBuilder::requestFactory()
                    );
                    $event->setOutput(
                        '<strong>' . dgettext('tuleap-hudson', 'Build performed on:') . '</strong> ' . $html_purifier->purify($build->getBuildTime()) . '<br />' .
                        '<strong>' . dgettext('tuleap-hudson', 'Status:') . '</strong> ' . $html_purifier->purify($build->getResult())
                    );
                } else {
                    $event->setOutput('<span class="error">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</span>');
                }
                break;
            case self::HUDSON_JOB_NATURE:
                $job_dao  = new PluginHudsonJobDao(CodendiDataAccess::instance());
                $job_name = $event->getValue();
                $group_id = $event->getProject()->getID();
                $dar      = $job_dao->searchByJobName($job_name, $group_id);
                if ($dar->valid()) {
                    $row = $dar->current();
                    try {
                        $minimal_job_factory = $this->getMinimalHudsonJobFactory();
                        $job_builder         = new HudsonJobBuilder(HTTPFactoryBuilder::requestFactory(), HttpClientFactory::createAsyncClient());
                        $job                 = $job_builder->getHudsonJob(
                            $minimal_job_factory->getMinimalHudsonJob($row['job_url'], '')
                        );
                        $job_id              = $row['job_id'];

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
                            $html .= ' <li>' . dgettext('tuleap-hudson', 'Last Build:') . ' <a href="/plugins/hudson/?action=view_build&group_id=' . $group_id . '&job_id=' . $job_id . '&build_id=' . $job->getLastBuildNumber() . '"># ' . $job->getLastBuildNumber() . '</a></li>';
                            $html .= ' <li>' . dgettext('tuleap-hudson', 'Last Success:') . ' <a href="/plugins/hudson/?action=view_build&group_id=' . $group_id . '&job_id=' . $job_id . '&build_id=' . $job->getLastSuccessfulBuildNumber() . '"># ' . $job->getLastSuccessfulBuildNumber() . '</a></li>';
                            $html .= ' <li>' . dgettext('tuleap-hudson', 'Last Failure:') . ' <a href="/plugins/hudson/?action=view_build&group_id=' . $group_id . '&job_id=' . $job_id . '&build_id=' . $job->getLastFailedBuildNumber() . '"># ' . $job->getLastFailedBuildNumber() . '</a></li>';
                        } else {
                            $html .= ' <li>' . dgettext('tuleap-hudson', 'No build found for this job.') . '</li>';
                        }
                        $html .= '   </ul>';
                        $html .= '  </td>';
                        $html .= '  <td class="widget_lastbuilds_weather">';
                        $html .= dgettext('tuleap-hudson', 'Weather Report:') . '<img src="' . $job->getWeatherReportIcon() . '" align="middle" />';
                        $html .= '  </td>';
                        $html .= ' </tr>';
                        $html .= '</table>';
                        $event->setOutput($html);
                    } catch (Exception $e) {
                    }
                } else {
                    $event->setOutput('<span class="error">' . dgettext('tuleap-hudson', 'Error: Jenkins object not found.') . '</span>');
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
                $val      = $params['val'];
                $group_id = $params['group_id'];
                $job_dao  = new PluginHudsonJobDao(CodendiDataAccess::instance());
                if (strpos($val, "/") !== false) {
                    $arr      = explode("/", $val);
                    $job_name = $arr[0];
                    $build_id = $arr[1];
                    $dar      = $job_dao->searchByJobName($job_name, $group_id);
                } else {
                    $build_id = $val;
                    $dar      = $job_dao->searchByGroupID($group_id);
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
                $job_dao  = new PluginHudsonJobDao(CodendiDataAccess::instance());
                $job_name = $params['val'];
                $group_id = $params['group_id'];
                $dar      = $job_dao->searchByJobName($job_name, $group_id);
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
        if (! empty($params['formatter'])) {
            $formatter = $params['formatter'];
            $jobDao    = new PluginHudsonJobDao(CodendiDataAccess::instance());
            $dar       = $jobDao->countJobs($formatter->groupId);
            $count     = 0;
            if ($dar && ! $dar->isError()) {
                    $row = $dar->getRow();
                if ($row) {
                    $count = $row['count'];
                }
            }
            $formatter->clearContent();
            $formatter->addEmptyLine();
            $formatter->addLine([dgettext('tuleap-hudson', 'Continuous Integration')]);
            $formatter->addLine([sprintf(dgettext('tuleap-hudson', 'Number of jobs until %1$s'), date('Y-m-d')), $count]);
            echo $formatter->getCsvContent();
            $formatter->clearContent();
        }
    }

    public function crossReferenceByNatureOrganizer(CrossReferenceByNatureOrganizer $organizer): void
    {
        $hudson_organizer = new HudsonCrossReferenceOrganizer(ProjectManager::instance());
        $hudson_organizer->organizeHudsonReferences($organizer);
    }

    public function serviceEnableForXmlImportRetriever(\Tuleap\Project\XML\ServiceEnableForXmlImportRetriever $event): void
    {
    }
}
