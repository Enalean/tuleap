<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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


use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsChartPresentersRetriever;
use Tuleap\AgileDashboard\Planning\AdditionalPlanningConfigurationWarningsRetriever;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;
use Tuleap\AgileDashboard\Semantic\SemanticDoneValueChecker;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\Semantic\SemanticFormatter;
use Tuleap\Velocity\Semantic\SemanticVelocity;
use Tuleap\Velocity\Semantic\SemanticVelocityFactory;
use Tuleap\Velocity\VelocityChartPresenter;
use Tuleap\Velocity\VelocityComputationChecker;
use Tuleap\Velocity\VelocityDao;
use Tuleap\Velocity\VelocityRepresentationBuilder;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'constants.php';

class velocityPlugin extends Plugin // @codingStandardsIgnoreLine
{
    /**
     * @var bool[]
     */
    private $already_computed = [];

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindTextDomain('tuleap-velocity', VELOCITY_BASE_DIR . '/site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook('cssfile');
        $this->addHook(TRACKER_EVENT_MANAGE_SEMANTICS);
        $this->addHook(AdditionalPlanningConfigurationWarningsRetriever::NAME);
        $this->addHook(BeforeEvent::NAME);
        $this->addHook(DetailsChartPresentersRetriever::NAME);
        $this->addHook(TRACKER_EVENT_SEMANTIC_FROM_XML);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies()
    {
        return array('tracker', 'agiledashboard');
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\Velocity\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    /**
     * @see Event::TRACKER_EVENT_MANAGE_SEMANTICS
     */
    public function tracker_event_manage_semantics($parameters) // @codingStandardsIgnoreLine
    {
        $tracker = $parameters['tracker'];
        /* @var $semantics Tracker_SemanticCollection */
        $semantics = $parameters['semantics'];

        if (! $this->isAPlanningTrackers($tracker)) {
            return;
        }

        $semantics->insertAfter(SemanticDone::NAME, SemanticVelocity::load($tracker));
    }

    private function isAPlanningTrackers(Tracker $semantic_tracker)
    {
        $planning_factory = PlanningFactory::build();
        $planning         = $planning_factory->getPlanningByPlanningTracker($semantic_tracker);

        if ($planning) {
            return $semantic_tracker->getId() === $planning->getPlanningTrackerId();
        }

        return false;
    }

    public function additionalPlanningConfigurationWarningsRetriever(
        AdditionalPlanningConfigurationWarningsRetriever $event
    ) {
        $velocity = SemanticVelocity::load($event->getTracker());

        if ($velocity->getVelocityField()) {
            $event->addWarnings(
                sprintf(
                    dgettext(
                        'tuleap-velocity',
                        'The tracker %s uses a Velocity semantic. Please check its configuration.'
                    ),
                    $event->getTracker()->getName()
                )
            );
        }
    }

    public function cssfile($params)
    {
        if ($this->isInAdminSemantics()) {
            $theme_include_assets = new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/velocity/FlamingParrot',
                '/assets/velocity/FlamingParrot'
            );
            $css_file_url         = $theme_include_assets->getFileURL('style.css');

            echo '<link rel="stylesheet" type="text/css" href="' . $css_file_url . '" />';
        }
    }

    public function burningParrotGetJavascriptFiles(array $params)
    {
        $include_assets = new IncludeAssets(
            __DIR__ . "/../../../src/www/assets/velocity/scripts",
            "/assets/velocity/scripts"
        );

        if ($this->isAPlanningOverviewRequest()) {
            $params['javascript_files'][] = $include_assets->getFileURL('velocity-chart.js');
        }
    }

    /** @see Event::BURNING_PARROT_GET_STYLESHEETS */
    public function burningParrotGetStylesheets(array $params)
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/velocity/BurningParrot',
            '/assets/velocity/BurningParrot'
        );

        $variant = $params['variant'];

        if ($this->isAPlanningOverviewRequest()) {
            $params['stylesheets'][] = $include_assets ->getFileURL('velocity-' . $variant->getName() . '.css');
        }
    }

    private function isAPlanningOverviewRequest()
    {
        $request = HTTPRequest::instance();

        return $request->exist('planning_id') && $request->get('pane') === 'details';
    }

    public function beforeEvent(BeforeEvent $before_event)
    {
        $tracker           = $before_event->getArtifact()->getTracker();
        $semantic_status   = Tracker_Semantic_Status::load($tracker);
        if (! $semantic_status->getField() || $semantic_status->getField()->isMultiple()) {
            return;
        }

        $semantic_done     = SemanticDone::load($tracker);
        $semantic_velocity = SemanticVelocity::load($tracker);

        $field_id = $semantic_velocity->getFieldId();
        if (! $semantic_status->getFieldId() || ! $semantic_done->isSemanticDefined() || ! $field_id) {
            return;
        }

        $computation_checker = new VelocityComputationChecker();
        if (! $computation_checker->shouldComputeCapacity($semantic_status, $semantic_done, $before_event)) {
            return;
        }

        $artifact_id  = $before_event->getArtifact()->getId();
        $changeset    = $before_event->getArtifact()->getLastChangeset();
        $changeset_id = $changeset ? $changeset->getId() : 0;

        if (! isset($this->already_computed[$artifact_id][$changeset_id])) {
            $calculator                                          = $this->getVelocityCalculator();
            $computed_velocity                                   = $calculator->calculate($before_event->getArtifact());
            $this->already_computed[$artifact_id][$changeset_id] = $computed_velocity;

            $current_user = UserManager::instance()->getCurrentUser();
            $factory      = Tracker_FormElementFactory::instance();
            $field        = $factory->getFormElementById($field_id);

            if ($field->userCanRead($current_user)) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    sprintf(
                        dgettext(
                            'tuleap-velocity',
                            'The field %s will be automatically set to %s'
                        ),
                        $field->getName(),
                        $computed_velocity
                    )
                );
            }
        } else {
            $computed_velocity = $this->already_computed[$artifact_id][$changeset_id];
        }

        $before_event->forceFieldData($field_id, $computed_velocity);
    }

    /**
     * @return \Tuleap\Velocity\VelocityCalculator
     */
    private function getVelocityCalculator()
    {
        $calculator = new \Tuleap\Velocity\VelocityCalculator(
            Tracker_ArtifactFactory::instance(),
            AgileDashboard_Semantic_InitialEffortFactory::instance(),
            new SemanticDoneFactory(new SemanticDoneDao(), new SemanticDoneValueChecker()),
            new VelocityDao()
        );

        return $calculator;
    }

    /**
     * @return bool
     */
    private function isInAdminSemantics()
    {
        return strpos($_SERVER['REQUEST_URI'], '/plugins/tracker/') === 0
            && strpos($_SERVER['REQUEST_URI'], 'func=admin-semantic') !== false;
    }

    public function tracker_event_semantic_from_xml(&$parameters) // @codingStandardsIgnoreLine
    {
        $tracker = $parameters['tracker'];
        $type    = $parameters['type'];
        $xml     = $parameters['xml'];
        $mapping = $parameters['xml_mapping'];

        if ($type == SemanticVelocity::NAME) {
            $factory                = new SemanticVelocityFactory(new SemanticFormatter());
            $parameters['semantic'] = $factory->getInstanceFromXML($xml, $tracker, $mapping);
        }
    }

    public function detailsChartPresentersRetriever(DetailsChartPresentersRetriever $event)
    {
        $builder        = new VelocityRepresentationBuilder(new VelocityDao(), Tracker_ArtifactFactory::instance(), Tracker_FormElementFactory::instance());
        $representation = $builder->buildRepresentations($event->getMilestone(), $event->getUser());

        $presenter             = new VelocityChartPresenter($representation);
        $renderer              = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');
        $string_representation = $renderer->renderToString("chart-field", $presenter);

        $event->addEscapedChart($string_representation);
    }
}
