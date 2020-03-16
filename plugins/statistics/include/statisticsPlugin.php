<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Statistics\DiskUsage\ConcurrentVersionsSystem\Collector as CVSCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\ProjectDetailsPresenter;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\Quota\ProjectQuotaInformation;
use Tuleap\Project\Quota\ProjectQuotaRequester;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\SOAP\SOAPRequestValidatorImplementation;
use Tuleap\Statistics\DiskUsage\ConcurrentVersionsSystem\FullHistoryDao;
use Tuleap\Statistics\DiskUsage\ConcurrentVersionsSystem\Retriever as CVSRetriever;
use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;
use Tuleap\SystemEvent\RootDailyStartEvent;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'constants.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class StatisticsPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->addHook(RootDailyStartEvent::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);
        $this->addHook('usergroup_data', 'usergroup_data', false);
        $this->addHook('groupedit_data', 'groupedit_data', false);
        $this->addHook(Event::WSDL_DOC2SOAP_TYPES, 'wsdl_doc2soap_types', false);

        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
        $this->addHook(Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE);
        $this->addHook(Event::AFTER_MASSMAIL_TO_PROJECT_ADMINS);

        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(ProjectDetailsPresenter::GET_MORE_INFO_LINKS);

        $this->addHook('aggregate_statistics');
        $this->addHook('get_statistics_aggregation');

        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(NavigationPresenter::NAME);

        $this->addHook(ProjectQuotaRequester::NAME);

        bindTextDomain('tuleap-statistics', STATISTICS_BASE_DIR . '/../site-content');
    }

    /** @see Event::GET_SYSTEM_EVENT_CLASS */
    public function get_system_event_class($params)
    {
        switch ($params['type']) {
            case SystemEvent_STATISTICS_DAILY::NAME:
                $queue = new SystemEventQueueStatistics();
                $params['class'] = 'SystemEvent_STATISTICS_DAILY';
                $params['dependencies'] = array(
                    $queue->getLogger(),
                    $this->getConfigurationManager(),
                    $this->getDiskUsagePurger($queue->getLogger()),
                    $this->getDiskUsageManager()
                );
                break;
            default:
                break;
        }
    }

    /** @see Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES */
    public function system_event_get_custom_queues(array $params)
    {
        $params['queues'][SystemEventQueueStatistics::NAME] = new SystemEventQueueStatistics();
    }

    /** @see Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE */
    public function system_event_get_types_for_custom_queue($params)
    {
        if ($params['queue'] === SystemEventQueueStatistics::NAME) {
            $params['types'][] = SystemEvent_STATISTICS_DAILY::NAME;
        }
    }

    /** @see Event::AFTER_MASSMAIL_TO_PROJECT_ADMINS */
    public function after_massmail_to_project_admins($params)
    {
        $request = HTTPRequest::instance();
        if ($request->get('project_over_quota')) {
            $GLOBALS['Response']->redirect("/plugins/statistics/project_over_quota.php");
        }
    }

    public function getPluginInfo()
    {
        if (!$this->pluginInfo instanceof StatisticsPluginInfo) {
            include_once('StatisticsPluginInfo.class.php');
            $this->pluginInfo = new StatisticsPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function site_admin_option_hook($params)
    {
        $params['plugins'][] = array(
            'label' => 'Statistics',
            'href'  => $this->getPluginPath() . '/'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0
            && ! strpos($_SERVER['REQUEST_URI'], 'project_stat.php')
        ) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    private function getConfigurationManager()
    {
        return new Statistics_ConfigurationManager(
            new Statistics_ConfigurationDao()
        );
    }

    private function getDiskUsagePurger(\Psr\Log\LoggerInterface $logger)
    {
        return new Statistics_DiskUsagePurger(
            new Statistics_DiskUsageDao(),
            $logger
        );
    }

    public function rootDailyStart(RootDailyStartEvent $event)
    {
        SystemEventManager::instance()->createEvent(
            SystemEvent_STATISTICS_DAILY::NAME,
            null,
            SystemEvent::PRIORITY_LOW,
            SystemEvent::OWNER_ROOT
        );
    }

    public function collectProjectAdminNavigationItems(NavigationPresenter $presenter)
    {
        $presenter->addDropdownItem(
            NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME,
            new NavigationDropdownItemPresenter(
                $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics'),
                $this->getPluginPath() . '/project_stat.php?' . http_build_query(
                    array('group_id' => $presenter->getProjectId())
                )
            )
        );
    }

    /**
     * Display link to user disk usage for site admin
     *
     * @param $params
     *
     * @return void
     */
    public function usergroup_data($params)
    {
        $user_url_params = array(
            'menu' => 'one_user_details',
            'user' => $params['user']->getRealName() . ' (' . $params['user']->getUserName() . ')'
        );

        $params['links'][] = array(
            'href'  => $this->getPluginPath() . '/disk_usage.php?' . http_build_query($user_url_params),
            'label' => $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics')
        );
    }

    /** @see ProjectDetailsPresenter::GET_MORE_INFO_LINKS */
    public function get_more_info_links($params)
    {
        if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
            return;
        }

        $project_url_params = array(
            'menu'           => 'services',
            'project_filter' => $params['project']->getPublicName() . ' (' . $params['project']->getUnixName() . ')'
        );
        $params['links'][] = array(
            'href'  => $this->getPluginPath() . '/disk_usage.php?' . http_build_query($project_url_params),
            'label' => $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics')
        );
    }

    /**
     * Instanciate the widget
     *
     *
     * @return void
     */
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_wiget_event)
    {
        if ($get_wiget_event->getName() === 'plugin_statistics_projectstatistics') {
            include_once 'Statistics_Widget_ProjectStatistics.class.php';
            $get_wiget_event->setWidget(new Statistics_Widget_ProjectStatistics());
        }
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event)
    {
        $event->addWidget('plugin_statistics_projectstatistics');
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(array('plugin_statistics_projectstatistics'));
    }

    public function cssFile($params)
    {
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('style-fp.css') . '" />' . "\n";
        }
    }

    public function processSOAP(Codendi_Request $request)
    {
        $uri           = $this->getSoapUri();
        $service_class = 'Statistics_SOAPServer';
        require_once $service_class . '.class.php';

        if ($request->exist('wsdl')) {
            $this->dumpWSDL($uri, $service_class);
        } else {
            $this->instantiateSOAPServer($uri, $service_class);
        }
    }

    private function dumpWSDL($uri, $service_class)
    {
        $wsdlGen = new SOAP_NusoapWSDL($service_class, 'TuleapStatisticsAPI', $uri);
        $wsdlGen->dumpWSDL();
    }

    private function instantiateSOAPServer($uri, $service_class)
    {
        require_once 'Statistics_DiskUsageManager.class.php';
        $user_manager           = UserManager::instance();
        $project_manager        = ProjectManager::instance();
        $soap_request_validator = new SOAPRequestValidatorImplementation(
            $project_manager,
            $user_manager,
            new ProjectAccessChecker(
                new PermissionsOverrider_PermissionsOverriderManager(),
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            )
        );
        $disk_usage_manager     = $this->getDiskUsageManager();
        $project_quota_manager  = new ProjectQuotaManager();

        $server = new TuleapSOAPServer($uri . '/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));
        $server->setClass($service_class, $soap_request_validator, $disk_usage_manager, $project_quota_manager);
        $xml_security = new XML_Security();
        $xml_security->enableExternalLoadOfEntities();
        $server->handle();
        $xml_security->disableExternalLoadOfEntities();
    }

    /**
     * @return Statistics_DiskUsageManager
     */
    private function getDiskUsageManager()
    {
        $disk_usage_dao  = new Statistics_DiskUsageDao();
        $svn_log_dao     = new SVN_LogDao();
        $svn_retriever   = new SVNRetriever($disk_usage_dao);
        $svn_collector   = new SVNCollector($svn_log_dao, $svn_retriever);
        $cvs_history_dao = new FullHistoryDao();
        $cvs_retriever   = new CVSRetriever($disk_usage_dao);
        $cvs_collector   = new CVSCollector($cvs_history_dao, $cvs_retriever);

        return new Statistics_DiskUsageManager(
            $disk_usage_dao,
            $svn_collector,
            $cvs_collector,
            EventManager::instance()
        );
    }

    private function getSoapUri()
    {
        return HTTPRequest::instance()->getServerUrl() . '/plugins/statistics/soap';
    }

    public function renderWSDL()
    {
        $uri = $this->getSoapUri();
        $wsdl_renderer = new SOAP_WSDLRenderer();
        $wsdl_renderer->render($uri . '/?wsdl');
    }

    public function wsdl_doc2soap_types($params)
    {
        $params['doc2soap_types'] = array_merge($params['doc2soap_types'], array(
            'arrayofstatistics' => 'tns:ArrayOfStatistics',
        ));
    }

    public function aggregate_statistics($params)
    {
        $statistics_aggregator = new StatisticsAggregatorDao();
        $statistics_aggregator->addStatistic($params['project_id'], $params['statistic_name']);
    }

    public function get_statistics_aggregation($params)
    {
        $statistics_aggregator = new StatisticsAggregatorDao();
        $params['result'] = $statistics_aggregator->getStatistics(
            $params['statistic_name'],
            $params['date_start'],
            $params['date_end']
        );
    }

    public function burning_parrot_get_stylesheets(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $variant    = $params['variant'];
            $params['stylesheets'][] = $this->getAssets()->getFileURL('style-bp-' . $variant->getName() . '.css');
        }
    }

    public function burning_parrot_get_javascript_files(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $ckeditor_assets = new IncludeAssets(__DIR__ . '/../../../src/www/assets', '/assets');
            $params['javascript_files'][] = $ckeditor_assets->getFileURL('ckeditor.js');
            $params['javascript_files'][] = $this->getAssets()->getFileURL('admin.js');
        }
    }

    public function getProjectQuota(ProjectQuotaRequester $project_quota_requester)
    {
        $project_quota_manager = new ProjectQuotaManager();
        $disk_usage_manager    = $this->getDiskUsageManager();
        $project               = $project_quota_requester->getProject();

        $project_quota_requester->setProjectQuotaInformation(
            new ProjectQuotaInformation(
                $project_quota_manager->getProjectAuthorizedQuota($project->getID()),
                $disk_usage_manager->returnTotalProjectSize($project->getID())
            )
        );
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/statistics',
            '/assets/statistics'
        );
    }
}
