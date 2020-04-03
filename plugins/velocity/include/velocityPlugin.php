<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\AdditionalPlanningConfigurationWarningsRetriever;
use Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker;
use Tuleap\AgileDashboard\Planning\Presenters\PlanningWarningPossibleMisconfigurationPresenter;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;
use Tuleap\AgileDashboard\Semantic\SemanticDoneValueChecker;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Semantic\Timeframe\Events\DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\Semantic\SemanticVelocity;
use Tuleap\Velocity\Semantic\SemanticVelocityFactory;
use Tuleap\Velocity\VelocityChartPresenter;
use Tuleap\Velocity\VelocityComputation;
use Tuleap\Velocity\VelocityComputationChecker;
use Tuleap\Velocity\VelocityDao;
use Tuleap\Velocity\VelocityRepresentationBuilder;

require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.php';
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
        $this->addHook(DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent::NAME);

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
        $semantics = $parameters['semantics'];
        \assert($semantics instanceof Tracker_SemanticCollection);

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
            $semantic_url = TRACKER_BASE_URL . "?" . http_build_query(
                [
                    "tracker"  => $event->getTracker()->getId(),
                    "func"     => "admin-semantic",
                    "semantic" => "velocity"
                ]
            );
            $semantic_name = dgettext('tuleap-velocity', 'Velocity semantic');
            $event->addWarnings(
                new PlanningWarningPossibleMisconfigurationPresenter($semantic_url, $semantic_name)
            );
        }
    }

    public function cssfile($params)
    {
        if ($this->isInAdminSemantics()) {
            $css_file_url         = $this->getAssets()->getFileURL('style-fp.css');

            echo '<link rel="stylesheet" type="text/css" href="' . $css_file_url . '" />';
        }
    }

    public function burningParrotGetJavascriptFiles(array $params)
    {
        if ($this->isAPlanningOverviewRequest()) {
            $params['javascript_files'][] = $this->getAssets()->getFileURL('velocity-chart.js');
        }
    }

    /** @see Event::BURNING_PARROT_GET_STYLESHEETS */
    public function burningParrotGetStylesheets(array $params)
    {
        $variant = $params['variant'];

        if ($this->isAPlanningOverviewRequest()) {
            $params['stylesheets'][] = $this->getAssets()->getFileURL('velocity-' . $variant->getName() . '.css');
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

        $velocity_computation = new VelocityComputation(
            $this->getVelocityCalculator(),
            new VelocityComputationChecker()
        );

        $tracker = $before_event->getArtifact()->getTracker();

        $semantic_status   = Tracker_Semantic_Status::load($tracker);
        $semantic_done     = SemanticDone::load($tracker);
        $semantic_velocity = SemanticVelocity::load($tracker);

        $velocity_computation->compute(
            $before_event,
            $this->already_computed,
            $semantic_status,
            $semantic_done,
            $semantic_velocity
        );
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
            $factory                = new SemanticVelocityFactory();
            $parameters['semantic'] = $factory->getInstanceFromXML($xml, $tracker, $mapping);
        }
    }

    public function detailsChartPresentersRetriever(DetailsChartPresentersRetriever $event)
    {
        $form_element_factory = Tracker_FormElementFactory::instance();

        $builder = new VelocityRepresentationBuilder(
            new SemanticVelocityFactory(),
            new SemanticDoneFactory(
                new SemanticDoneDao(),
                new SemanticDoneValueChecker()
            ),
            $this->getSemanticTimeframeBuilder($form_element_factory),
            new Planning_MilestoneFactory(
                PlanningFactory::build(),
                Tracker_ArtifactFactory::instance(),
                $form_element_factory,
                new AgileDashboard_Milestone_MilestoneStatusCounter(
                    new AgileDashboard_BacklogItemDao(),
                    new Tracker_ArtifactDao(),
                    Tracker_ArtifactFactory::instance()
                ),
                new PlanningPermissionsManager(),
                new AgileDashboard_Milestone_MilestoneDao(),
                new ScrumForMonoMilestoneChecker(
                    new ScrumForMonoMilestoneDao(),
                    PlanningFactory::build()
                ),
                new TimeframeBuilder(
                    $this->getSemanticTimeframeBuilder($form_element_factory),
                    \BackendLogger::getDefaultLogger()
                ),
                new MilestoneBurndownFieldChecker($form_element_factory)
            )
        );

        $representations_collection = $builder->buildCollectionOfRepresentations(
            $event->getMilestone(),
            $event->getUser()
        );

        $presenter             = new VelocityChartPresenter($representations_collection);
        $renderer              = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');
        $string_representation = $renderer->renderToString("chart-field", $presenter);

        $event->addEscapedChart($string_representation);
    }

    private function getSemanticTimeframeBuilder(Tracker_FormElementFactory $form_element_factory): SemanticTimeframeBuilder
    {
        return new SemanticTimeframeBuilder(
            new SemanticTimeframeDao(),
            $form_element_factory
        );
    }

    public function doesAPluginRenderAChartBasedOnSemanticTimeframeForTracker(DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent $event): void
    {
        $semantic_velocity = SemanticVelocity::load($event->getTracker());

        if ($semantic_velocity->getVelocityField() !== null) {
            $event->setItRendersAChartForTracker();
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/velocity',
            '/assets/velocity'
        );
    }
}
