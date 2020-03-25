<?php
/**
 * Copyright (c) Enalean, 2011 - present. All Rights Reserved.
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

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';

use Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector;
use Tuleap\Cardwall\Agiledashboard\CardwallPaneInfo;
use Tuleap\Cardwall\AllowedFieldRetriever;
use Tuleap\Cardwall\Semantic\BackgroundColorDao;
use Tuleap\Cardwall\Semantic\BackgroundColorSemanticFactory;
use Tuleap\Cardwall\Semantic\FieldUsedInSemanticObjectChecker;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Events\AllowedFieldTypeChangesRetriever;
use Tuleap\Tracker\Events\IsFieldUsedInASemanticEvent;
use Tuleap\Tracker\Report\Renderer\ImportRendererFromXmlEvent;

/**
 * CardwallPlugin
 */
class cardwallPlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var Cardwall_OnTop_ConfigFactory
     */
    private $config_factory;

    public function __construct($id)
    {
        parent::__construct($id);

        bindTextDomain('tuleap-cardwall', CARDWALL_BASE_DIR . '/../site-content');
    }

    public function getConfigFactory()
    {
        if (!$this->config_factory) {
            $tracker_factory  = TrackerFactory::instance();
            $element_factory  = Tracker_FormElementFactory::instance();
            $this->config_factory = new Cardwall_OnTop_ConfigFactory($tracker_factory, $element_factory);
        }
        return $this->config_factory;
    }

    public const RENDERER_TYPE = 'plugin_cardwall';


    public function getHooksAndCallbacks()
    {
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook('cssfile');
            $this->addHook('javascript_file');
            $this->addHook('tracker_report_renderer_types');
            $this->addHook('tracker_report_renderer_instance');
            $this->addHook(TRACKER_EVENT_TRACKERS_DUPLICATED);
            $this->addHook(TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION);
            $this->addHook(TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE);
            $this->addHook(Event::JAVASCRIPT);
            $this->addHook(Event::IMPORT_XML_PROJECT_TRACKER_DONE);
            $this->addHook(AllowedFieldTypeChangesRetriever::NAME);
            $this->addHook(TRACKER_EVENT_MANAGE_SEMANTICS);
            $this->addHook(TRACKER_EVENT_SEMANTIC_FROM_XML);
            $this->addHook(TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS);
            $this->addHook(TRACKER_EVENT_EXPORT_FULL_XML);
            $this->addHook(IsFieldUsedInASemanticEvent::NAME);
            $this->addHook(ImportRendererFromXmlEvent::NAME);
            $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);

            if (defined('AGILEDASHBOARD_BASE_DIR')) {
                $this->addHook(PaneInfoCollector::NAME);
                $this->addHook(AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT);
                $this->addHook(AGILEDASHBOARD_EVENT_PLANNING_CONFIG);
                $this->addHook(AGILEDASHBOARD_EVENT_PLANNING_CONFIG_UPDATE);
                $this->addHook(AGILEDASHBOARD_EVENT_REST_GET_CARDWALL);
                $this->addHook(AGILEDASHBOARD_EVENT_REST_GET_MILESTONE);
                $this->addHook(AGILEDASHBOARD_EVENT_IS_CARDWALL_ENABLED);
                $this->addHook(AGILEDASHBOARD_EVENT_GET_CARD_FIELDS);
                $this->addHook(AGILEDASHBOARD_EVENT_REST_RESOURCES);
                $this->addHook(AGILEDASHBOARD_EXPORT_XML);
            }
        }
        return parent::getHooksAndCallbacks();
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('tracker');
    }

    public function tracker_event_export_full_xml($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $plannings = PlanningFactory::build()->getOrderedPlanningsWithBacklogTracker($params['user'], $params['group_id']);
        $this->getAgileDashboardExporter()->exportFull($params['project'], $params['xml_content'], $plannings);

        $this->getCardwallXmlExporter($params['group_id'])->export($params['xml_content']);
    }

    private function getAgileDashboardExporter(): AgileDashboard_XMLExporter
    {
        return AgileDashboard_XMLExporter::build();
    }

    private function getCardwallXmlExporter($group_id)
    {
        return new CardwallConfigXmlExport(
            ProjectManager::instance()->getProject($group_id),
            TrackerFactory::instance(),
            new Cardwall_OnTop_ConfigFactory(TrackerFactory::instance(), Tracker_FormElementFactory::instance()),
            new XML_RNGValidator()
        );
    }

    // TODO : transform into a OnTop_Config_Command, and move code to ConfigFactory
    public function tracker_event_trackers_duplicated($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        foreach ($params['tracker_mapping'] as $from_tracker_id => $to_tracker_id) {
            if ($this->getOnTopDao()->duplicate($from_tracker_id, $to_tracker_id)) {
                $this->getOnTopColumnDao()->duplicate($from_tracker_id, $to_tracker_id, $params);
                $this->getOnTopColumnMappingFieldDao()->duplicate($from_tracker_id, $to_tracker_id, $params['tracker_mapping'], $params['field_mapping']);
                $this->getOnTopColumnMappingFieldValueDao()->duplicate($from_tracker_id, $to_tracker_id, $params['tracker_mapping'], $params['field_mapping'], $params['plugin_cardwall_column_mapping']);
            }
        }
    }

    /**
     * This hook ask for types of report renderer
     *
     * @param array types Input/Output parameter. Expected format: $types['my_type'] => 'Label of the type'
     */
    public function tracker_report_renderer_types($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'][self::RENDERER_TYPE] = $GLOBALS['Language']->getText('plugin_cardwall', 'title');
    }

    /**
     * This hook asks to create a new instance of a renderer
     *
     * @param array $params:
     *              mixed  'instance' Output parameter. must contain the new instance
     *              string 'type' the type of the new renderer
     *              array  'row' the base properties identifying the renderer (id, name, description, rank)
     *              Report 'report' the report
     *
     * @return void
     */
    public function tracker_report_renderer_instance($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['type'] == self::RENDERER_TYPE) {
            $report = $params['report'];
            $config = new Cardwall_OnTop_ConfigEmpty();

            if ($report->tracker_id != 0) {
                $config = $this->getConfigFactory()->getOnTopConfigByTrackerId($report->tracker_id);
            }

            //Build the instance from the row
            $params['instance'] = new Cardwall_Renderer(
                $this,
                $config,
                $params['row']['id'],
                $params['report'],
                $params['row']['name'],
                $params['row']['description'],
                $params['row']['rank'],
                $this->getFieldForInstance($params),
            );

            if ($params['store_in_session']) {
                $params['instance']->initiateSession();
            }
        }
    }

    private function getFieldForInstance(array $params)
    {
        if (isset($params['row']['from_xml_field'])) {
            return $params['row']['from_xml_field'];
        }

        $field_id = null;
        if ($params['store_in_session']) {
            $this->report_session = new Tracker_Report_Session($params['report']->id);
            if (isset($params['row']['id'])) {
                $this->report_session->changeSessionNamespace("renderers.{$params['row']['id']}");
                $field_id = $this->report_session->get("field_id");
            }
        }
        if (! $field_id) {
            $dao = new Cardwall_RendererDao();
            $cardwall_row = $dao->searchByRendererId($params['row']['id'])->getRow();
            if ($cardwall_row) {
                $field_id = $cardwall_row['field_id'];
            }
        }
        if ($field_id !== null) {
            return $this->getFieldForCardwallFromId($field_id);
        }
        return null;
    }

    private function getFieldForCardwallFromId($id)
    {
        $field = Tracker_FormElementFactory::instance()->getFormElementById($id);
        if ($field && $field->userCanRead() && $field instanceof Tracker_FormElement_Field_Selectbox) {
            return $field;
        }
        return null;
    }

    public function importRendererFromXmlEvent(ImportRendererFromXmlEvent $event)
    {
        if ($event->getType() === self::RENDERER_TYPE) {
            $attributes = $event->getXml()->attributes();

            $field_id = (string) $attributes['field_id'];
            if (! $field_id) {
                return;
            }

            $event->setRowKey('from_xml_field', $event->getFieldFromXMLReference($field_id));
        }
    }

    public function getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'CardwallPluginInfo')) {
            $this->pluginInfo = new CardwallPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile($params)
    {
        if ($this->canIncludeStylesheets()) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getCSSURL() . '" />';
        }
    }

    private function getCSSURL()
    {
        return $this->getAssets()->getFileURL('flamingparrot-theme.css');
    }

    private function canIncludeStylesheets()
    {
        return $this->isAgileDashboardOrTrackerUrl()
            || strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0;
    }

    public function javascript_file($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // Only show the js if we're actually in the Cardwall pages.
        // This stops styles inadvertently clashing with the main site.
        if ($this->isAgileDashboardOrTrackerUrl() && $this->canUseStandardJavsacript()) {
            $agiledashboard_plugin = PluginManager::instance()->getAvailablePluginByName('agiledashboard');
            if ($agiledashboard_plugin && $agiledashboard_plugin->currentRequestIsForPlugin()) {
                $assets = new IncludeAssets(__DIR__ . '/../../../src/www/assets/trackers', '/assets/trackers');
                echo $assets->getHTMLSnippet('tracker.js');
            }
            echo $this->getAssets()->getHTMLSnippet('cardwall.js');
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/cardwall/',
            '/assets/cardwall/'
        );
    }

    /**
     * @see Event::TRACKER_EVENT_MANAGE_SEMANTICS
     */
    public function tracker_event_manage_semantics($parameters) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker   = $parameters['tracker'];
        $semantics = $parameters['semantics'];
        \assert($semantics instanceof Tracker_SemanticCollection);

        $semantics->add(Cardwall_Semantic_CardFields::load($tracker));
    }

    public function isFieldUsedInASemanticEvent(IsFieldUsedInASemanticEvent $event)
    {
        $checker = new FieldUsedInSemanticObjectChecker(
            new BackgroundColorDao()
        );

        $event->setIsUsed(
            $checker->isUsedInBackgroundColorSemantic($event->getField())
        );
    }

    /**
     * @see TRACKER_EVENT_SEMANTIC_FROM_XML
     */
    public function tracker_event_semantic_from_xml($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker     = $params['tracker'];
        $xml         = $params['xml'];
        $xml_mapping = $params['xml_mapping'];
        $type        = $params['type'];

        if ($type == Cardwall_Semantic_CardFields::NAME) {
            $params['semantic'] = (new Cardwall_Semantic_CardFieldsFactory())->getInstanceFromXML($xml, $xml_mapping, $tracker);
        }
    }

    /**
     * @see TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS
     */
    public function trackerEventGetSemanticDuplicators($params)
    {
        $params['duplicators'][] = new Tracker_Semantic_CollectionOfFieldsDuplicator(new Cardwall_Semantic_Dao_CardFieldsDao());
        $params['duplicators'][] = new BackgroundColorSemanticFactory(new BackgroundColorDao());
    }

    private function isAgileDashboardOrTrackerUrl()
    {
        return (defined('AGILEDASHBOARD_BASE_DIR') &&
                strpos($_SERVER['REQUEST_URI'], AGILEDASHBOARD_BASE_URL . '/') === 0 ||
                strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL . '/') === 0);
    }

    private function canUseStandardJavsacript()
    {
        $use_standard = true;

        $em = EventManager::instance();
        $em->processEvent(
            CARDWALL_EVENT_USE_STANDARD_JAVASCRIPT,
            array(
                'use_standard' => &$use_standard
            )
        );

        return $use_standard;
    }

    public function javascript($params)
    {
        include $GLOBALS['Language']->getContent('script_locale', null, 'cardwall', '.js');
        echo "var tuleap = tuleap || { };" . PHP_EOL;
        echo "tuleap.cardwall = tuleap.cardwall || { };" . PHP_EOL;
        echo "tuleap.cardwall.base_url = '" . CARDWALL_BASE_URL . "/';" . PHP_EOL;
        echo PHP_EOL;
    }

    /**
     * @see Event::AGILEDASHBOARD_EVENT_GET_CARD_FIELDS
     */
    public function agiledashboard_event_get_card_fields($parameters) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $parameters['card_fields_semantic'] = Cardwall_Semantic_CardFields::load($parameters['tracker']);
    }

    public function agiledashboardEventAdditionalPanesOnMilestone(PaneInfoCollector $collector): void
    {
        if (! $this->isCardwallAllowed($collector->getMilestone()->getProject())) {
            return;
        }

        $pane_info = $this->getPaneInfo($collector->getMilestone());

        if (! $pane_info) {
            return;
        }

        if ($collector->getRequest()->get('pane') === CardwallPaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $collector->setActivePane(
                $this->getCardwallPane(
                    $pane_info,
                    $collector->getMilestone(),
                    $collector->getUser(),
                    $collector->getMilestoneFactory()
                )
            );
        }
        $collector->addPane($pane_info);
    }

    private function getPaneInfo($milestone)
    {
        $tracker  = $milestone->getArtifact()->getTracker();

        if (! $this->getOnTopDao()->isEnabled($tracker->getId())) {
            return;
        }

        return new CardwallPaneInfo($milestone);
    }

    public function agiledashboard_event_index_page($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // Only display a cardwall if there is something to display
        if ($params['milestone'] && $params['milestone']->getPlannedArtifacts() && count($params['milestone']->getPlannedArtifacts()->getChildren()) > 0) {
            $pane_info = new CardwallPaneInfo($params['milestone']);
            $params['pane'] = $this->getCardwallPane($pane_info, $params['milestone'], $params['user'], $params['milestone_factory']);
        }
    }

    protected function getCardwallPane(CardwallPaneInfo $info, Planning_Milestone $milestone, PFUser $user, Planning_MilestoneFactory $milestone_factory)
    {
        $config = $this->getConfigFactory()->getOnTopConfigByPlanning($milestone->getPlanning());
        if ($config) {
            return new Cardwall_Pane(
                $info,
                $milestone,
                $config,
                $user,
                $milestone_factory
            );
        }
        return null;
    }

    public function agiledashboard_event_milestone_selector_redirect($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['milestone']->getArtifact()) {
            $tracker  = $params['milestone']->getArtifact()->getTracker();
            if ($this->getOnTopDao()->isEnabled($tracker->getId())) {
                $params['redirect_parameters']['pane'] = 'cardwall';
            }
        }
    }

    public function tracker_event_redirect_after_artifact_creation_or_update($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $cardwall = $params['request']->get('cardwall');
        $redirect = $params['redirect'];
        if ($cardwall) {
            if (!$redirect->stayInTracker()) {
                $redirect_to     = key($cardwall);
                $redirect_params = current($cardwall);
                switch ($redirect_to) {
                    case 'agile':
                        $this->redirectToAgileDashboard($redirect, $redirect_params);
                        break;
                    case 'renderer':
                        $this->redirectToRenderer($redirect, $redirect_params);
                        break;
                }
            } else {
                $this->appendCardwallParameter($redirect, $cardwall);
            }
        }
    }

    private function redirectToAgileDashboard(Tracker_Artifact_Redirect $redirect, array $redirect_params)
    {
        $planning_id = key($redirect_params);
        $artifact_id = current($redirect_params);
        $planning    = PlanningFactory::build()->getPlanning($planning_id);
        if ($planning) {
            $redirect->base_url         = AGILEDASHBOARD_BASE_URL;
            $redirect->query_parameters = array(
                'group_id'    => $planning->getGroupId(),
                'planning_id' => $planning->getId(),
                'action'      => 'show',
                'aid'         => $artifact_id,
                'pane'        => 'cardwall',
            );
        }
    }

    private function redirectToRenderer(Tracker_Artifact_Redirect $redirect, array $redirect_params)
    {
        $report_id                   = key($redirect_params);
        $renderer_id                 = current($redirect_params);
        $redirect->base_url          = TRACKER_BASE_URL;
        $redirect->query_parameters  = array(
            'report'   => $report_id,
            'renderer' => $renderer_id,
        );
    }

    public function tracker_event_build_artifact_form_action($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $cardwall = $params['request']->get('cardwall');
        if ($cardwall) {
            $this->appendCardwallParameter($params['redirect'], $cardwall);
        }
    }

    private function appendCardwallParameter(Tracker_Artifact_Redirect $redirect, $cardwall)
    {
        [$key, $value] = explode('=', urldecode(http_build_query(array('cardwall' => $cardwall))));
        $redirect->query_parameters[$key] = $value;
    }

    /**
     * @param array $params parameters send by Event
     * Parameters:
     *  'project'  => The given project
     *  'into_xml' => The SimpleXMLElement to fill in
     */
    public function agiledashboard_export_xml($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker_factory = TrackerFactory::instance();

        $cardwall_xml_export = new CardwallConfigXmlExport(
            $params['project'],
            $tracker_factory,
            $this->getConfigFactory(),
            new XML_RNGValidator()
        );

        $cardwall_xml_export->export($params['into_xml']);
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT_TRACKER_DONE
     */
    public function import_xml_project_tracker_done($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $cardwall_ontop_import = new CardwallConfigXmlImport(
            $params['project']->getId(),
            $params['mapping'],
            $params['field_mapping'],
            $params['artifact_id_mapping'],
            new Cardwall_OnTop_Dao,
            new Cardwall_OnTop_ColumnDao,
            new Cardwall_OnTop_ColumnMappingFieldDao(),
            new Cardwall_OnTop_ColumnMappingFieldValueDao(),
            EventManager::instance(),
            new XML_RNGValidator(),
            $params['logger']
        );
        $cardwall_ontop_import->import($params['xml_content']);
    }

    public function agiledashboard_event_rest_get_milestone($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $milestone = $params['milestone'];
        assert($milestone instanceof \Planning_Milestone);

        if (! $this->isCardwallAllowed($milestone->getProject())) {
            return;
        }

        $config = $this->getConfigFactory()->getOnTopConfig($milestone->getPlanning()->getPlanningTracker());
        if ($config && $config->isEnabled()) {
            $params['milestone_representation']->enableCardwall();
        }
    }

    private function buildRightVersionOfMilestonesCardwallResource($version)
    {
        $class_with_right_namespace = '\\Tuleap\\Cardwall\\REST\\' . $version . '\\MilestonesCardwallResource';
        return new $class_with_right_namespace($this->getConfigFactory());
    }

    public function agiledashboard_event_rest_get_cardwall($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $milestones_cardwall = $this->buildRightVersionOfMilestonesCardwallResource($params['version']);

        $params['cardwall'] = $milestones_cardwall->get($params['milestone']);
    }

    /**
     * Fetches HTML
     *
     * @param array $params parameters sent by Event
     *
     * Parameters:
     *  'tracker' => The planning tracker
     *  'view'    => A string of HTML
     */
    public function agiledashboard_event_planning_config($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker = $params['tracker'];
        $config  = $this->getConfigFactory()->getOnTopConfig($tracker);

        $admin_view = new Cardwall_OnTop_Config_View_Admin();
        $params['view'] = $admin_view->displayAdminOnTop($config);
    }

    public function agiledashboard_event_is_cardwall_enabled($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker = $params['tracker'];
        assert($tracker instanceof Tracker);

        if (! $this->isCardwallAllowed($tracker->getProject())) {
            return;
        }

        $params['enabled'] = $this->getConfigFactory()
            ->isOnTopConfigEnabledForPlanning($tracker);
    }

    private function isCardwallAllowed(Project $project): bool
    {
        $event = new Tuleap\Cardwall\CardwallIsAllowedEvent($project);
        EventManager::instance()->processEvent($event);
        return $event->isCardwallAllowed();
    }

    /**
     * @param array $params parameters sent by Event
     *
     * Parameters:
     *  'tracker' => The planning tracker
     *  'request' => The Request
     */
    public function agiledashboard_event_planning_config_update($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = $params['request'];
        $request->set('use_freestyle_columns', 1);

        $this->getConfigFactory()->getOnTopConfigUpdater($params['tracker'])
                ->process($request);
    }

    /**
     * @return Cardwall_OnTop_Dao
     */
    private function getOnTopDao()
    {
        return new Cardwall_OnTop_Dao();
    }

    /**
     * @return Cardwall_OnTop_ColumnDao
     */
    private function getOnTopColumnDao()
    {
        return new Cardwall_OnTop_ColumnDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldDao
     */
    private function getOnTopColumnMappingFieldDao()
    {
        return new Cardwall_OnTop_ColumnMappingFieldDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private function getOnTopColumnMappingFieldValueDao()
    {
        return new Cardwall_OnTop_ColumnMappingFieldValueDao();
    }

    public function agiledashboard_event_rest_resources($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new Cardwall_REST_ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function semanticAllowedFieldTypeRetriever(AllowedFieldTypeChangesRetriever $event_retriever)
    {
        $retriever     = new AllowedFieldRetriever(
            Tracker_FormElementFactory::instance(),
            new FieldUsedInSemanticObjectChecker(new BackgroundColorDao())
        );
        $allowed_types = $retriever->retrieveAllowedFieldType($event_retriever->getField());

        $event_retriever->setAllowedTypes($allowed_types);
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event) : void
    {
        $event->getRouteCollector()->addGroup('/plugins/cardwall', function (FastRoute\RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeLegacyController'));
        });
    }

    public function routeLegacyController() : \Tuleap\Cardwall\CardwallLegacyController
    {
        return new \Tuleap\Cardwall\CardwallLegacyController($this->getConfigFactory());
    }
}
