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
use Tuleap\AgileDashboard\Planning\Admin\AdditionalPlanningConfigurationWarningsRetriever;
use Tuleap\AgileDashboard\Planning\Admin\PlanningWarningPossibleMisconfigurationPresenter;
use Tuleap\JiraImport\JiraAgile\ScrumTrackerStructureEvent;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueChecker;
use Tuleap\Tracker\Semantic\Timeframe\Events\DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\TrackerSemanticCollection;
use Tuleap\Tracker\Semantic\TrackerSemanticFactory;
use Tuleap\Tracker\Semantic\TrackerSemanticManager;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\JiraImporter\AddVelocityToScrumTemplate;
use Tuleap\Velocity\Semantic\SemanticVelocity;
use Tuleap\Velocity\Semantic\SemanticVelocityDao;
use Tuleap\Velocity\Semantic\SemanticVelocityDuplicator;
use Tuleap\Velocity\Semantic\SemanticVelocityFactory;
use Tuleap\Velocity\VelocityChartPresenter;
use Tuleap\Velocity\VelocityComputation;
use Tuleap\Velocity\VelocityComputationChecker;
use Tuleap\Velocity\VelocityDao;
use Tuleap\Velocity\VelocityRepresentationBuilder;

require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'constants.php';

class velocityPlugin extends Plugin // phpcs:ignore
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

    #[\Override]
    public function getDependencies()
    {
        return ['tracker', 'agiledashboard'];
    }

    #[\Override]
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\Velocity\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventName(TrackerSemanticManager::TRACKER_EVENT_MANAGE_SEMANTICS)]
    public function trackerEventManageSemantics($parameters): void // phpcs:ignore
    {
        $user      = $parameters['user'];
        $tracker   = $parameters['tracker'];
        $semantics = $parameters['semantics'];
        \assert($semantics instanceof TrackerSemanticCollection);

        if (! $this->isAPlanningTrackers($user, $tracker)) {
            return;
        }

        $semantics->insertAfter(SemanticDone::NAME, SemanticVelocity::load($tracker));
    }

    private function isAPlanningTrackers(PFUser $user, Tracker $semantic_tracker): bool
    {
        $planning_factory = PlanningFactory::build();
        $planning         = $planning_factory->getPlanningByPlanningTracker($user, $semantic_tracker);

        if ($planning) {
            return $semantic_tracker->getId() === $planning->getPlanningTrackerId();
        }

        return false;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function additionalPlanningConfigurationWarningsRetriever(
        AdditionalPlanningConfigurationWarningsRetriever $event,
    ): void {
        $velocity = SemanticVelocity::load($event->getTracker());

        if ($velocity->getVelocityField()) {
            $semantic_url  = TRACKER_BASE_URL . '?' . http_build_query(
                [
                    'tracker'  => $event->getTracker()->getId(),
                    'func'     => 'admin-semantic',
                    'semantic' => 'velocity',
                ]
            );
            $semantic_name = dgettext('tuleap-velocity', 'Velocity semantic');
            $event->addWarning(
                new PlanningWarningPossibleMisconfigurationPresenter($semantic_url, $semantic_name)
            );
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('cssfile')]
    public function cssfile($params): void
    {
        if ($this->isInAdminSemantics()) {
            $css_file_url = $this->getAssets()->getFileURL('style-fp.css');

            echo '<link rel="stylesheet" type="text/css" href="' . $css_file_url . '" />';
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES)]
    public function burningParrotGetJavascriptFiles(array $params): void
    {
        if ($this->isAPlanningOverviewRequest()) {
            $params['javascript_files'][] = $this->getAssets()->getFileURL('velocity-chart.js');
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::BURNING_PARROT_GET_STYLESHEETS)]
    public function burningParrotGetStylesheets(array $params): void
    {
        if ($this->isAPlanningOverviewRequest()) {
            $params['stylesheets'][] = $this->getAssets()->getFileURL('velocity-style.css');
        }
    }

    private function isAPlanningOverviewRequest()
    {
        $request = HTTPRequest::instance();

        return $request->exist('planning_id') && $request->get('pane') === 'details';
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function beforeEvent(BeforeEvent $before_event): void
    {
        $tracker         = $before_event->getArtifact()->getTracker();
        $semantic_status = CachedSemanticStatusRetriever::instance()->fromTracker($tracker);
        $field           = $semantic_status->getField();
        if (! $field || $field->isMultiple()) {
            return;
        }

        $velocity_computation = new VelocityComputation(
            $this->getVelocityCalculator(),
            new VelocityComputationChecker()
        );

        $tracker = $before_event->getArtifact()->getTracker();

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
            new SemanticDoneFactory(
                new SemanticDoneDao(),
                new SemanticDoneValueChecker(),
                CachedSemanticStatusRetriever::instance(),
            ),
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

    #[\Tuleap\Plugin\ListeningToEventName(TrackerSemanticFactory::TRACKER_EVENT_SEMANTIC_FROM_XML)]
    public function trackerEventSemanticFromXml(&$parameters): void // phpcs:ignore
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

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function detailsChartPresentersRetriever(DetailsChartPresentersRetriever $event): void
    {
        $builder = new VelocityRepresentationBuilder(
            new SemanticVelocityFactory(),
            new SemanticDoneFactory(
                new SemanticDoneDao(),
                new SemanticDoneValueChecker(),
                CachedSemanticStatusRetriever::instance(),
            ),
            SemanticTimeframeBuilder::build(),
            Planning_MilestoneFactory::build()
        );

        $representations_collection = $builder->buildCollectionOfRepresentations(
            $event->getMilestone(),
            $event->getUser()
        );

        $presenter             = new VelocityChartPresenter($representations_collection);
        $renderer              = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');
        $string_representation = $renderer->renderToString('chart-field', $presenter);

        $event->addEscapedChart($string_representation);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
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
            __DIR__ . '/../frontend-assets',
            '/assets/velocity'
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function scrumTrackerStructureEvent(ScrumTrackerStructureEvent $event): void
    {
        $event->tracker = (new AddVelocityToScrumTemplate())
            ->addVelocityToStructure($event->tracker, $event->id_generator);
    }

    #[\Tuleap\Plugin\ListeningToEventName(TrackerSemanticFactory::TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS)]
    public function registerProjectCreationEvent(array &$params): void
    {
        $params['duplicators'][] = new SemanticVelocityDuplicator(new SemanticVelocityDao());
    }
}
