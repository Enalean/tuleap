<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

use Tuleap\Project\Admin\ProjectDetailsPresenter;

require_once 'autoload.php';
require_once 'constants.php';

class StatisticsPlugin extends Plugin {

    function __construct($id) {
        parent::__construct($id);
        $this->_addHook('cssfile',                  'cssFile',                false);
        $this->_addHook('site_admin_option_hook',   'site_admin_option_hook', false);
        $this->_addHook('root_daily_start',         'root_daily_start',       false);
        $this->_addHook('widget_instance',          'widget_instance',        false);
        $this->_addHook('widgets',                  'widgets',                false);
        $this->_addHook('admin_toolbar_data',       'admin_toolbar_data',     false);
        $this->_addHook('usergroup_data',           'usergroup_data',         false);
        $this->_addHook('groupedit_data',           'groupedit_data',         false);
        $this->_addHook(Event::WSDL_DOC2SOAP_TYPES, 'wsdl_doc2soap_types',    false);
        $this->addHook('javascript_file');

        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
        $this->addHook(Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE);

        $this->addHook(Event::IS_IN_SITEADMIN);
        $this->addHook(ProjectDetailsPresenter::GET_MORE_INFO_LINKS);

        $this->addHook('aggregate_statistics');
        $this->addHook('get_statistics_aggregation');
    }

    /** @see Event::GET_SYSTEM_EVENT_CLASS */
    public function get_system_event_class($params) {
        switch($params['type']) {
            case SystemEvent_STATISTICS_DAILY::NAME:
                $queue = new SystemEventQueueStatistics();
                $params['class'] = 'SystemEvent_STATISTICS_DAILY';
                $params['dependencies'] = array(
                    $queue->getLogger(),
                    $this->getConfigurationManager(),
                    $this->getDiskUsagePurger()
                );
                break;
            default:
                break;
        }
    }

    /** @see Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES */
    public function system_event_get_custom_queues(array $params) {
        $params['queues'][SystemEventQueueStatistics::NAME] = new SystemEventQueueStatistics();
    }

    /** @see Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE */
    public function system_event_get_types_for_custom_queue($params) {
        if ($params['queue'] === SystemEventQueueStatistics::NAME) {
            $params['types'][] = SystemEvent_STATISTICS_DAILY::NAME;
        }
    }

    function getPluginInfo() {
        if (!$this->pluginInfo instanceof StatisticsPluginInfo) {
            include_once('StatisticsPluginInfo.class.php');
            $this->pluginInfo = new StatisticsPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function site_admin_option_hook($params)
    {
        $params['plugins'][] = array(
            'label' => 'Statistics',
            'href'  => $this->getPluginPath() . '/'
        );
    }

    /** @see Event::IS_IN_SITEADMIN */
    public function is_in_siteadmin($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $did_we_switch_statistics_to_burning_parrot = false;
            $params['is_in_siteadmin'] = $did_we_switch_statistics_to_burning_parrot;
        }
    }

    private function getConfigurationManager() {
        return new Statistics_ConfigurationManager(
            new Statistics_ConfigurationDao()
        );
    }

    private function getDiskUsagePurger() {
        return new Statistics_DiskUsagePurger(
            new Statistics_DiskUsageDao(CodendiDataAccess::instance())
        );
    }

    /**
     * @see root_daily_start
     */
    public function root_daily_start($params) {
        SystemEventManager::instance()->createEvent(
            SystemEvent_STATISTICS_DAILY::NAME,
            null,
            SystemEvent::PRIORITY_LOW,
            SystemEvent::OWNER_ROOT
        );
    }

    /**
     * Hook.
     *
     * @param $params
     *
     * @return void
     */
    function admin_toolbar_data($params) {
        $groupId = $params['group_id'];
        if ($groupId) {
            echo ' | <A HREF="'.$this->getPluginPath().'/project_stat.php?group_id='.$groupId.'">'.$GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics').'</A>';
        }
    }

    /**
     * Display link to user disk usage for site admin
     *
     * @param $params
     *
     * @return void
     */
    function usergroup_data($params)
    {
        $params['links'][] = array(
            'href'  => $this->getPluginPath() . '/disk_usage.php?func=show_one_user&user_id='.$params['user']->getId(),
            'label' => $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics')
        );
    }

    /** @see ProjectDetailsPresenter::GET_MORE_INFO_LINKS */
    function get_more_info_links($params) {
        if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
            return;
        }

        $params['links'][] = array(
            'href'  => $this->getPluginPath().'/disk_usage.php?func=show_one_project&group_id='.$params['project']->getID(),
            'label' => $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics')
        );
    }

    /**
     * Instanciate the widget
     *
     * @param Array $params params of the event
     *
     * @return void
     */
    function widget_instance($params) {
        if ($params['widget'] == 'plugin_statistics_projectstatistics') {
            include_once 'Statistics_Widget_ProjectStatistics.class.php';
            $params['instance'] = new Statistics_Widget_ProjectStatistics();
        }
    }

    /**
     * Add the widget to the list
     *
     * @param Array $params params of the event
     *
     * @return void
     */
    function widgets($params) {
        include_once 'common/widget/WidgetLayoutManager.class.php';
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_GROUP) {
            $params['codendi_widgets'][] = 'plugin_statistics_projectstatistics';
        }
    }

    function cssFile($params) {
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }

    public function processSOAP(Codendi_Request $request) {
        $uri           = $this->getSoapUri();
        $service_class = 'Statistics_SOAPServer';
        require_once $service_class .'.class.php';

        if ($request->exist('wsdl')) {
            $this->dumpWSDL($uri, $service_class);
        } else {
            $this->instantiateSOAPServer($uri, $service_class);
        }
    }

    private function dumpWSDL($uri, $service_class) {
        require_once 'common/soap/SOAP_NusoapWSDL.class.php';
        $wsdlGen = new SOAP_NusoapWSDL($service_class, 'TuleapStatisticsAPI', $uri);
        $wsdlGen->dumpWSDL();
    }

    private function instantiateSOAPServer($uri, $service_class) {
        require_once 'common/soap/SOAP_RequestValidator.class.php';
        require_once 'Statistics_DiskUsageManager.class.php';
        $user_manager           = UserManager::instance();
        $project_manager        = ProjectManager::instance();
        $soap_request_validator = new SOAP_RequestValidator($project_manager, $user_manager);
        $disk_usage_manager     = new Statistics_DiskUsageManager();
        $project_quota_manager  = new ProjectQuotaManager();

        $server = new TuleapSOAPServer($uri.'/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));
        $server->setClass($service_class, $soap_request_validator, $disk_usage_manager, $project_quota_manager);
        $server->handle();
    }

    private function getSoapUri() {
        return HTTPRequest::instance()->getServerUrl().'/plugins/statistics/soap';
    }

    public function renderWSDL() {
        require_once 'common/soap/SOAP_WSDLRenderer.class.php';
        $uri = $this->getSoapUri();
        $wsdl_renderer = new SOAP_WSDLRenderer();
        $wsdl_renderer->render($uri .'/?wsdl');
    }

    public function wsdl_doc2soap_types($params) {
        $params['doc2soap_types'] = array_merge($params['doc2soap_types'], array(
            'arrayofstatistics' => 'tns:ArrayOfStatistics',
        ));
    }

    public function javascript_file($params) {
        if ($this->currentRequestIsForPlugin()) {
            echo '<script type="text/javascript" src="' . $this->getPluginPath() . '/js/autocomplete.js"></script>'."\n";
        }
    }

    public function aggregate_statistics($params) {
        $statistics_aggregator = new StatisticsAggregatorDao();
        $statistics_aggregator->addStatistic($params['project_id'], $params['statistic_name']);
    }

    public function get_statistics_aggregation($params) {
        $statistics_aggregator = new StatisticsAggregatorDao();
        $params['result'] = $statistics_aggregator->getStatistics(
            $params['statistic_name'],
            $params['date_start'],
            $params['date_end']
        );
    }
}
