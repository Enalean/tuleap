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

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use PFUser;
use PlanningFactory;
use SimpleXMLElement;
use TemplateRendererFactory;
use Tracker;
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

    public function __construct(Tracker $tracker, SemanticDone $semantic_done)
    {
        parent::__construct($tracker);

        $this->semantic_done = $semantic_done;
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
        $backlog_trackers                       = $this->getBacklogTrackers();
        $backlog_trackers_without_done_semantic = $this->getPresentersOfBacklogTrackersWithoutDoneSemantic(
            $backlog_trackers
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');

        $used_velocity_field = $this->getVelocityField();

        $factory  = Tracker_FormElementFactory::instance();
        $field_id = $factory->getFormElementById($used_velocity_field['field_id']);

        $backlog_trackers = $this->formatBacklogTrackers($backlog_trackers_without_done_semantic, $backlog_trackers);

        $velocity_presenter = new SemanticVelocityPresenter(
            $this->semantic_done->isSemanticDefined(),
            $backlog_trackers_without_done_semantic,
            $backlog_trackers,
            $this->getTracker(),
            count($backlog_trackers) === count($backlog_trackers_without_done_semantic),
            $field_id
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

        $used_velocity_field = $this->getVelocityField();

        $planning_trackers                      = $this->getBacklogTrackers();
        $backlog_trackers_without_done_semantic = $this->getPresentersOfBacklogTrackersWithoutDoneSemantic(
            $planning_trackers
        );

        $backlog_trackers = $this->formatBacklogTrackers($backlog_trackers_without_done_semantic, $planning_trackers);

        $csrf = $this->getCSRFSynchronizerToken();

        $renderer  = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');
        $presenter = new SemanticVelocityAdminPresenter(
            $possible_fields,
            $csrf,
            $this->getTracker(),
            $this->semantic_done->isSemanticDefined(),
            $used_velocity_field['field_id'],
            $backlog_trackers,
            count($planning_trackers) === count($backlog_trackers_without_done_semantic)
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
        return;
    }

    public function isUsedInSemantics($field)
    {
        return $this->getFieldId() == $field->getId();
    }

    public function getFieldId()
    {
        $used_velocity_field = $this->getSemanticDao()->searchUsedVelocityField($this->getTracker()->getId());
        if ($used_velocity_field) {
            return $used_velocity_field['field_id'];
        } else {
            return 0;
        }
    }

    public function save()
    {
        return;
    }

    protected static $_instances;

    /**
     * @return SemanticVelocity
     */
    public static function load(Tracker $tracker)
    {
        if (! isset(self::$_instances[$tracker->getId()])) {
            return self::forceLoad($tracker);
        }

        return self::$_instances[$tracker->getId()];
    }

    private static function forceLoad(Tracker $tracker)
    {
        $semantic_done                       = SemanticDone::load($tracker);
        self::$_instances[$tracker->getId()] = new SemanticVelocity($tracker, $semantic_done);

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

    private function getPresentersOfBacklogTrackersWithoutDoneSemantic(array $all_baklog_trackers)
    {
        $trackers = [];

        $semantic_done_factory = new SemanticDoneFactory(new SemanticDoneDao(), new SemanticDoneValueChecker());

        foreach ($all_baklog_trackers as $tracker) {
            $semantic_done = $semantic_done_factory->getInstanceByTracker($tracker);
            if (! $semantic_done->isSemanticDefined()) {
                $trackers[] = $tracker->getId();
            }
        }

        return $trackers;
    }

    /**
     * @return mixed
     */
    public function getVelocityField()
    {
        return $this->getSemanticDao()->searchUsedVelocityField($this->getTracker()->getId());
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

    /**
     * @param Tracker[] $incorrect_backlog_trackers
     * @param Tracker[] $backlog_trackers
     *
     * @return array
     */
    private function formatBacklogTrackers(array $incorrect_backlog_trackers, array $backlog_trackers)
    {
        $formatted_tracker = [];
        foreach ($backlog_trackers as $tracker) {
            $formatted_tracker[] = [
                "name"              => $tracker->getName(),
                "is_missconfigured" => in_array($tracker->getId(), $incorrect_backlog_trackers),
                "semantic_url"       => TRACKER_BASE_URL . "?" . http_build_query(
                    [
                        "tracker"  => $tracker->getId(),
                        "func"     => "admin-semantic",
                        "semantic" => "done"
                    ]
                ),
                "tracker_url"      => TRACKER_BASE_URL . "?" . http_build_query(
                    [
                        "tracker" => $tracker->getId(),
                        "func"    => "admin"
                    ]
                )
            ];
        }

        return $formatted_tracker;
    }
}
