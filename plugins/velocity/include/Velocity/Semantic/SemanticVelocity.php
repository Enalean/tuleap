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

namespace Tuleap\Velocity\Semantic;

use AgileDashBoard_Semantic_InitialEffort;
use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use PFUser;
use PlanningFactory;
use SimpleXMLElement;
use TemplateRendererFactory;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use Tracker_Semantic;
use Tracker_SemanticManager;
use TrackerManager;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;
use Tuleap\AgileDashboard\Semantic\SemanticDoneValueChecker;

class SemanticVelocity extends Tracker_Semantic
{
    const NAME = 'velocity';

    /**
     * @var SemanticDone
     */
    private $semantic_done;
    /**
     * @var SemanticFormatter
     */
    private $semantic_formatter;

    /**
     * @var \Tracker_FormElement_Field
     */
    private $velocity_field;

    public function __construct(
        Tracker $tracker,
        SemanticDone $semantic_done,
        SemanticFormatter $semantic_formatter,
        Tracker_FormElement_Field $velocity_field = null
    ) {
        parent::__construct($tracker);

        $this->semantic_done      = $semantic_done;
        $this->velocity_field     = $velocity_field;
        $this->semantic_formatter = $semantic_formatter;
    }

    public function getShortName()
    {
        return self::NAME;
    }

    public function getLabel()
    {
        return dgettext('tuleap-velocity', 'Velocity');
    }

    public function getDescription()
    {
        return dgettext('tuleap-velocity', 'Define the field to use to compute velocity.');
    }

    public function display()
    {
        $backlog_trackers                                 = $this->getBacklogTrackers();
        $backlog_trackers_without_done_semantic           = $this->getTrackersIdsAndDoneSemanticUndefined(
            $backlog_trackers
        );
        $backlog_trackers_without_initial_effort_semantic = $this->getTrackersIdsAndInitialEffortUndefined(
            $backlog_trackers
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');

        $backlog_trackers = $this->semantic_formatter->formatBacklogTrackers(
            $backlog_trackers_without_done_semantic,
            $backlog_trackers_without_initial_effort_semantic,
            $backlog_trackers
        );

        $velocity_presenter = new SemanticVelocityPresenter(
            $this->semantic_done->isSemanticDefined(),
            $backlog_trackers_without_done_semantic,
            $backlog_trackers,
            $this->getTracker(),
            $this->semantic_formatter->getSemanticMisconfiguredForAllTrackers(
                $backlog_trackers,
                $backlog_trackers_without_done_semantic,
                $backlog_trackers_without_initial_effort_semantic
            ),
            $this->hasAMissingSemanticForAllBacklogTrackers(
                $backlog_trackers,
                $backlog_trackers_without_done_semantic,
                $backlog_trackers_without_initial_effort_semantic
            ),
            $this->velocity_field
        );
        $renderer->renderToPage('velocity-intro', $velocity_presenter);
    }

    public function displayAdmin(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        $sm->displaySemanticHeader($this, $tracker_manager);

        $factory         = Tracker_FormElementFactory::instance();
        $possible_fields = $factory->getUsedFormElementsByType($this->getTracker(), array('int', 'float'));

        $backlog_trackers                                 = $this->getBacklogTrackers();
        $backlog_trackers_without_done_semantic           = $this->getTrackersIdsAndDoneSemanticUndefined(
            $backlog_trackers
        );
        $backlog_trackers_without_initial_effort_semantic = $this->getTrackersIdsAndInitialEffortUndefined(
            $backlog_trackers
        );

        $backlog_trackers = $this->semantic_formatter->formatBacklogTrackers(
            $backlog_trackers_without_done_semantic,
            $backlog_trackers_without_initial_effort_semantic,
            $backlog_trackers
        );

        $csrf = $this->getCSRFSynchronizerToken();

        $renderer  = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');
        $presenter = new SemanticVelocityAdminPresenter(
            $possible_fields,
            $csrf,
            $this->getTracker(),
            $this->semantic_done->isSemanticDefined(),
            $this->getFieldId(),
            $backlog_trackers,
            $this->semantic_formatter->getSemanticMisconfiguredForAllTrackers(
                $backlog_trackers,
                $backlog_trackers_without_done_semantic,
                $backlog_trackers_without_initial_effort_semantic
            ),
            $this->hasAMissingSemanticForAllBacklogTrackers(
                $backlog_trackers,
                $backlog_trackers_without_done_semantic,
                $backlog_trackers_without_initial_effort_semantic
            )
        );

        $renderer->renderToPage('velocity-admin', $presenter);

        $sm->displaySemanticFooter($this, $tracker_manager);
    }

    public function process(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        if ($request->exist('submit')) {
            $csrf = $this->getCSRFSynchronizerToken();
            $csrf->check();

            $values = $request->get('velocity_field');

            if (! $this->semantic_done->isSemanticDefined()) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    dgettext('tuleap-velocity', 'Semantic done is not defined.')
                );
            } elseif (isset($values)) {
                $this->getSemanticDao()->addField($this->getTracker()->getId(), $values);
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    dgettext('tuleap-velocity', 'Semantic updated successfully.')
                );
            } else {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-velocity', 'The request is not valid.')
                );
            }
        }

        if ($request->exist('delete')) {
            $csrf = $this->getCSRFSynchronizerToken();
            $csrf->check();

            $this->getSemanticDao()->removeField($this->getTracker()->getId());
        }

        $this->displayAdmin($sm, $tracker_manager, $request, $current_user);
    }

    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if (! $this->semantic_done->isSemanticDefined()) {
            return;
        }

        $status_field = $this->semantic_done->getSemanticStatus()->getField();
        if (in_array($status_field->getId(), $xmlMapping) && $this->getFieldId() > 0) {
            $child = $root->addChild('semantic');
            $child->addAttribute('type', $this->getShortName());
            $child->addChild('shortname', $this->getShortName());
            $child->addChild('label', $this->getLabel());
            $child->addChild('description', $this->getDescription());
            $child->addChild('field')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
        }
    }

    public function isUsedInSemantics($field)
    {
        return $this->getFieldId() == $field->getId();
    }

    public function getFieldId()
    {
        if (! $this->velocity_field) {
            return 0;
        }

        return $this->velocity_field->getId();
    }

    public function save()
    {
        $this->getSemanticDao()->addField($this->getTracker()->getId(), $this->getFieldId());
    }

    protected static $_instances;

    /**
     * @return SemanticVelocity
     */
    public static function load(Tracker $tracker)
    {
        if (! isset(self::$_instances[$tracker->getId()])) {
            $semantic_dao   = new SemanticVelocityDao();
            $field_velocity = $semantic_dao->searchUsedVelocityField($tracker->getId());
            $field_id = isset($field_velocity['field_id'])? $field_velocity['field_id'] : 0;

            $factory = Tracker_FormElementFactory::instance();
            $field   = $factory->getFieldById($field_id);

            return self::forceLoad($tracker, $field);
        }

        return self::$_instances[$tracker->getId()];
    }

    private static function forceLoad(Tracker $tracker, Tracker_FormElement_Field $field = null)
    {
        $semantic_done                       = SemanticDone::load($tracker);
        $semantic_formatter                  = new SemanticFormatter();
        self::$_instances[$tracker->getId()] = new SemanticVelocity($tracker, $semantic_done, $semantic_formatter, $field);

        return self::$_instances[$tracker->getId()];
    }

    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFSynchronizerToken()
    {
        return new CSRFSynchronizerToken(
            TRACKER_BASE_URL . "?" . http_build_query(
                [
                    "semantic" => "velocity",
                    "func"     => "admin-semantic"
                ]
            )
        );
    }

    private function getSemanticDao()
    {
        return new SemanticVelocityDao();
    }

    private function getTrackersIdsAndDoneSemanticUndefined(array $all_baklog_trackers)
    {
        $trackers = [];

        $semantic_done_factory = new SemanticDoneFactory(new SemanticDoneDao(), new SemanticDoneValueChecker());

        foreach ($all_baklog_trackers as $tracker) {
            $semantic_done = $semantic_done_factory->getInstanceByTracker($tracker);
            if (! $semantic_done->isSemanticDefined()) {
                $trackers[$tracker->getId()] = $semantic_done;
            }
        }

        return $trackers;
    }

    private function getTrackersIdsAndInitialEffortUndefined(array $all_baklog_trackers)
    {
        $trackers = [];

        foreach ($all_baklog_trackers as $tracker) {
            $initial_effort = AgileDashBoard_Semantic_InitialEffort::load($tracker);
            if ($initial_effort->getFieldId() === 0) {
                $trackers[$tracker->getId()] = $initial_effort;
            }
        }

        return $trackers;
    }

    /**
     * @return mixed
     */
    public function getVelocityField()
    {
        return $this->velocity_field;
    }

    /**
     * @return Tracker[]
     */
    private function getBacklogTrackers()
    {
        $planning_factory  = PlanningFactory::build();
        $planning_trackers = $planning_factory->getPlanningByPlanningTracker($this->getTracker());

        return $planning_trackers->getBacklogTrackers();
    }

    private function hasAMissingSemanticForAllBacklogTrackers(
        array $backlog_trackers,
        array $backlog_trackers_without_done_semantic,
        array $backlog_trackers_without_initial_effort_semantic
    ) {
        return count($backlog_trackers) === count($backlog_trackers_without_done_semantic) ||
            count($backlog_trackers) === count($backlog_trackers_without_initial_effort_semantic);
    }
}
