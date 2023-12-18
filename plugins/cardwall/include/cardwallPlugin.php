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
use Tuleap\AgileDashboard\REST\v1\Milestone\MilestoneRepresentationBuilder;
use Tuleap\AgileDashboard\REST\v1\MilestoneResource;
use Tuleap\Cardwall\Agiledashboard\CardwallPaneInfo;
use Tuleap\Cardwall\AllowedFieldRetriever;
use Tuleap\Cardwall\Cardwall\CardwallUseStandardJavascriptEvent;
use Tuleap\Cardwall\REST\v1\MilestonesCardwallResource;
use Tuleap\Cardwall\Semantic\BackgroundColorDao;
use Tuleap\Cardwall\Semantic\BackgroundColorSemanticFactory;
use Tuleap\Cardwall\Semantic\FieldUsedInSemanticObjectChecker;
use Tuleap\Cardwall\XML\Template\CompleteIssuesTemplate;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;
use Tuleap\Tracker\Artifact\Renderer\BuildArtifactFormActionEvent;
use Tuleap\Tracker\Creation\JiraImporter\Import\Semantic\ExternalSemanticsExportEvent;
use Tuleap\Tracker\Events\AllowedFieldTypeChangesRetriever;
use Tuleap\Tracker\Events\IsFieldUsedInASemanticEvent;
use Tuleap\Tracker\Report\Renderer\ImportRendererFromXmlEvent;
use Tuleap\Tracker\Template\CompleteIssuesTemplateEvent;
use Tuleap\Tracker\TrackerEventTrackersDuplicated;
use Tuleap\Tracker\XML\Exporter\TrackerEventExportFullXML;
use Tuleap\Tracker\XML\Exporter\TrackerEventExportStructureXML;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;

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
        if (! $this->config_factory) {
            $tracker_factory      = TrackerFactory::instance();
            $element_factory      = Tracker_FormElementFactory::instance();
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
            $this->addHook(BuildArtifactFormActionEvent::NAME);
            $this->addHook(RedirectAfterArtifactCreationOrUpdateEvent::NAME);
            $this->addHook(Event::JAVASCRIPT);
            $this->addHook(ImportXMLProjectTrackerDone::NAME);
            $this->addHook(AllowedFieldTypeChangesRetriever::NAME);
            $this->addHook(Tracker_SemanticManager::TRACKER_EVENT_MANAGE_SEMANTICS);
            $this->addHook(Tracker_SemanticFactory::TRACKER_EVENT_SEMANTIC_FROM_XML);
            $this->addHook(Tracker_SemanticFactory::TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS);
            $this->addHook(TrackerEventExportFullXML::NAME);
            $this->addHook(IsFieldUsedInASemanticEvent::NAME);
            $this->addHook(ImportRendererFromXmlEvent::NAME);
            $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);
            $this->addHook(CompleteIssuesTemplateEvent::NAME);

            if (defined('AGILEDASHBOARD_BASE_DIR')) {
                $this->addHook(PaneInfoCollector::NAME);
                $this->addHook(Planning_MilestoneSelectorController::AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT);
                $this->addHook(Planning_Controller::AGILEDASHBOARD_EVENT_PLANNING_CONFIG);
                $this->addHook(Planning_Controller::AGILEDASHBOARD_EVENT_PLANNING_CONFIG_UPDATE);
                $this->addHook(MilestoneResource::AGILEDASHBOARD_EVENT_REST_GET_CARDWALL);
                $this->addHook(MilestoneRepresentationBuilder::AGILEDASHBOARD_EVENT_REST_GET_MILESTONE);
                $this->addHook(AgileDashboardPlugin::AGILEDASHBOARD_EVENT_REST_RESOURCES);
                $this->addHook(AgileDashboard_XMLFullStructureExporter::AGILEDASHBOARD_EXPORT_XML);
            }
        }
        return parent::getHooksAndCallbacks();
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return ['tracker'];
    }

    public function trackerEventExportFullXML(TrackerEventExportFullXML $event): void
    {
        $project_id  = (int) $event->getProject()->getID();
        $xml_content = $event->getXmlElement();

        $this->getCardwallXmlExporter($project_id)->export($xml_content);
    }

    #[ListeningToEventClass]
    public function trackerEventExportStructureXML(TrackerEventExportStructureXML $event): void
    {
        $project_id  = (int) $event->getProject()->getID();
        $xml_content = $event->getXmlElement();

        $this->getCardwallXmlExporter($project_id)->export($xml_content);
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

    /**
     * @todo transform into a OnTop_Config_Command, and move code to ConfigFactory
     */
    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerEventTrackersDuplicated(TrackerEventTrackersDuplicated $event): void
    {
        foreach ($event->tracker_mapping as $from_tracker_id => $to_tracker_id) {
            if ($this->getOnTopDao()->duplicate($from_tracker_id, $to_tracker_id)) {
                $this->getOnTopColumnDao()->duplicate($from_tracker_id, $to_tracker_id, $event);
                $this->getOnTopColumnMappingFieldDao()->duplicate($from_tracker_id, $to_tracker_id, $event->tracker_mapping, $event->field_mapping);
                $this->getOnTopColumnMappingFieldValueDao()->duplicate($from_tracker_id, $to_tracker_id, $event->tracker_mapping, $event->field_mapping, $event->mapping_registry->getCustomMapping('plugin_cardwall_column_mapping'));
            }
        }
        (new \Tuleap\Cardwall\CardwallRendererDuplicatorDao())->duplicate($event->mapping_registry, $event->field_mapping);
    }

    /**
     * This hook ask for types of report renderer
     *
     * @param array types Input/Output parameter. Expected format: $types['my_type'] => 'Label of the type'
     */
    public function tracker_report_renderer_types($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'][self::RENDERER_TYPE] = dgettext('tuleap-cardwall', 'Card Wall');
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
            $dao          = new Cardwall_RendererDao();
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
        if (! $this->pluginInfo instanceof \CardwallPluginInfo) {
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
        return $this->getCssAssets()->getFileURL('flamingparrot-theme.css');
    }

    private function canIncludeStylesheets()
    {
        return $this->isAgileDashboardOrTrackerUrl()
            || strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0;
    }

    public function javascript_file($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $layout = $params['layout'];
        assert($layout instanceof \Tuleap\Layout\BaseLayout);
        // Only show the js if we're actually in the Cardwall pages.
        // This stops styles inadvertently clashing with the main site.
        if ($this->isAgileDashboardOrTrackerUrl() && $this->canUseStandardJavsacript()) {
            $agiledashboard_plugin = PluginManager::instance()->getEnabledPluginByName('agiledashboard');
            if ($agiledashboard_plugin && $agiledashboard_plugin->currentRequestIsForPlugin()) {
                $tracker_legacy_assets = new IncludeAssets(__DIR__ . '/../../tracker/scripts/legacy/frontend-assets', '/assets/trackers/legacy');
                $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($tracker_legacy_assets, 'tracker.js'));
                $assets = new IncludeAssets(__DIR__ . '/../../tracker/frontend-assets', '/assets/trackers');
                $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($assets, 'modal-v2.js'));
            }
            $cardwall_assets = new IncludeAssets(
                __DIR__ . '/../scripts/legacy/frontend-assets/',
                '/assets/cardwall/legacy/'
            );
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($cardwall_assets, 'cardwall.js'));
        }

        if (HTTPRequest::instance()->get('pane') === CardwallPaneInfo::IDENTIFIER) {
            $layout->addJavascriptAsset(RelativeDatesAssetsRetriever::getAsJavascriptAssets());
        }
    }

    private function getCssAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets/',
            '/assets/cardwall/'
        );
    }

    /**
     * @see Tracker_SemanticManager::TRACKER_EVENT_MANAGE_SEMANTICS
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
     * @see Tracker_SemanticFactory::TRACKER_EVENT_SEMANTIC_FROM_XML
     */
    public function tracker_event_semantic_from_xml($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker           = $params['tracker'];
        $xml               = $params['xml'];
        $full_semantic_xml = $params['full_semantic_xml'];
        $xml_mapping       = $params['xml_mapping'];
        $type              = $params['type'];

        if ($type == Cardwall_Semantic_CardFields::NAME) {
            $params['semantic'] = (new Cardwall_Semantic_CardFieldsFactory())->getInstanceFromXML(
                $xml,
                $full_semantic_xml,
                $xml_mapping,
                $tracker,
                []
            );
        }
    }

    /**
     * @see Tracker_SemanticFactory::TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS
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
        return EventManager::instance()
            ->dispatch(new CardwallUseStandardJavascriptEvent())
            ->use_standard_javascript;
    }

    public function javascript($params)
    {
        include $GLOBALS['Language']->getContent('script_locale', null, 'cardwall', '.js');
        echo "var tuleap = tuleap || { };" . PHP_EOL;
        echo "tuleap.cardwall = tuleap.cardwall || { };" . PHP_EOL;
        echo "tuleap.cardwall.base_url = '" . CARDWALL_BASE_URL . "/';" . PHP_EOL;
        echo PHP_EOL;
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

        $active_pane_context = $collector->getActivePaneContext();
        if ($active_pane_context && $active_pane_context->getRequest()->get('pane') === CardwallPaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $collector->setActivePaneBuilder(
                function () use ($pane_info, $collector, $active_pane_context): AgileDashboard_Pane {
                    return $this->getCardwallPane(
                        $pane_info,
                        $collector->getMilestone(),
                        $active_pane_context->getUser(),
                        $active_pane_context->getMilestoneFactory()
                    );
                }
            );
        }
        $collector->addPane($pane_info);
    }

    private function getPaneInfo($milestone)
    {
        $tracker = $milestone->getArtifact()->getTracker();

        if (! $this->getOnTopDao()->isEnabled($tracker->getId())) {
            return;
        }

        return new CardwallPaneInfo($milestone->getGroupId(), $milestone->getPlanningId(), $milestone->getArtifactId());
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
            $tracker = $params['milestone']->getArtifact()->getTracker();
            if ($this->getOnTopDao()->isEnabled($tracker->getId())) {
                $params['redirect_parameters']['pane'] = 'cardwall';
            }
        }
    }

    public function redirectAfterArtifactCreationOrUpdateEvent(RedirectAfterArtifactCreationOrUpdateEvent $event): void
    {
        $cardwall = $event->getRequest()->get('cardwall');
        $redirect = $event->getRedirect();
        if ($cardwall) {
            if (! $redirect->stayInTracker()) {
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
            $redirect->query_parameters = [
                'group_id'    => $planning->getGroupId(),
                'planning_id' => $planning->getId(),
                'action'      => 'show',
                'aid'         => $artifact_id,
                'pane'        => 'cardwall',
            ];
        }
    }

    private function redirectToRenderer(Tracker_Artifact_Redirect $redirect, array $redirect_params)
    {
        $report_id                  = key($redirect_params);
        $renderer_id                = current($redirect_params);
        $redirect->base_url         = TRACKER_BASE_URL;
        $redirect->query_parameters = [
            'report'   => $report_id,
            'renderer' => $renderer_id,
        ];
    }

    public function buildArtifactFormActionEvent(BuildArtifactFormActionEvent $event): void
    {
        $cardwall = $event->getRequest()->get('cardwall');
        if ($cardwall) {
            $this->appendCardwallParameter($event->getRedirect(), $cardwall);
        }
    }

    private function appendCardwallParameter(Tracker_Artifact_Redirect $redirect, $cardwall)
    {
        [$key, $value]                    = explode('=', urldecode(http_build_query(['cardwall' => $cardwall])));
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

    public function importXMLProjectTrackerDone(ImportXMLProjectTrackerDone $event): void
    {
        $cardwall_ontop_import = new CardwallConfigXmlImport(
            $event->getProject()->getId(),
            $event->getCreatedTrackersMapping(),
            $event->getXmlFieldsMapping(),
            $event->getArtifactsIdMapping(),
            new Cardwall_OnTop_Dao(),
            new Cardwall_OnTop_ColumnDao(),
            new Cardwall_OnTop_ColumnMappingFieldDao(),
            new Cardwall_OnTop_ColumnMappingFieldValueDao(),
            EventManager::instance(),
            new XML_RNGValidator(),
            $event->getLogger()
        );
        $cardwall_ontop_import->import($event->getXmlElement());
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
            $milestone_representation_reference_holder                           = $params['milestone_representation_reference_holder'];
            $milestone_representation_reference_holder->milestone_representation = \Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation::buildWithCardwallEnabled($milestone_representation_reference_holder->milestone_representation);
        }
    }

    public function agiledashboard_event_rest_get_cardwall($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $milestones_cardwall = new MilestonesCardwallResource($this->getConfigFactory());
        $params['cardwall']  = $milestones_cardwall->get($params['milestone']);
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

        $admin_view     = new Cardwall_OnTop_Config_View_Admin();
        $params['view'] = $admin_view->displayAdminOnTop($config);
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

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/plugins/cardwall', function (FastRoute\RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeLegacyController'));
        });
    }

    public function routeLegacyController(): \Tuleap\Cardwall\CardwallLegacyController
    {
        return new \Tuleap\Cardwall\CardwallLegacyController($this->getConfigFactory());
    }

    public function completeIssuesTemplate(CompleteIssuesTemplateEvent $event): void
    {
        $event->addAllIssuesRenderers(CompleteIssuesTemplate::getAllIssuesRenderer());
        $event->addMyIssuesRenderers(CompleteIssuesTemplate::getMyIssuesRenderer());
        $event->addOpenIssuesRenderers(CompleteIssuesTemplate::getOpenIssuesRenderer());
    }

    #[ListeningToEventClass]
    public function externalSemanticsExport(ExternalSemanticsExportEvent $event): void
    {
        (new \Tuleap\Cardwall\JiraImport\CardSemanticExporter())->exportCardSemantic(
            $event->semantics_node,
            $event->field_mapping_collection,
        );
    }
}
