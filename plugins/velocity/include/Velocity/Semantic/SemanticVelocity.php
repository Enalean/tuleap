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
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use Tracker_HierarchyFactory;
use Tracker_Semantic;
use Tracker_SemanticManager;
use TrackerManager;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;
use Tuleap\AgileDashboard\Semantic\SemanticDoneValueChecker;

class SemanticVelocity extends Tracker_Semantic
{
    public const NAME = 'velocity';

    /**
     * @var SemanticDone
     */
    private $semantic_done;

    /**
     * @var \Tracker_FormElement_Field
     */
    private $velocity_field;

    public function __construct(
        Tracker $tracker,
        SemanticDone $semantic_done,
        ?Tracker_FormElement_Field $velocity_field = null
    ) {
        parent::__construct($tracker);

        $this->semantic_done      = $semantic_done;
        $this->velocity_field     = $velocity_field;
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

    /**
     * @return MissingRequirementRetriever
     */
    private function getMissingRequirementRetriever()
    {
        return new MissingRequirementRetriever(
            Tracker_HierarchyFactory::instance(),
            new SemanticDoneFactory(new SemanticDoneDao(), new SemanticDoneValueChecker()),
            \AgileDashboard_Semantic_InitialEffortFactory::instance(),
            new SemanticVelocityFactory(),
            new BacklogRequiredTrackerCollectionFormatter()
        );
    }

    public function display()
    {
        $renderer           = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');
        $builder            = new SemanticVelocityPresenterBuilder(
            $this->getMissingRequirementRetriever(),
            $this->getBacklogRetriever(),
            new VelocitySemanticChecker()
        );
        $velocity_presenter = $builder->build($this->getTracker(), $this->semantic_done, $this->velocity_field);

        $renderer->renderToPage('velocity-intro', $velocity_presenter);
    }

    public function displayAdmin(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        $sm->displaySemanticHeader($this, $tracker_manager);

        $builder = new SemanticVelocityAdminPresenterBuilder(
            $this->getMissingRequirementRetriever(),
            $this->getBacklogRetriever(),
            new VelocitySemanticChecker()
        );

        $renderer  = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');
        $presenter = $builder->build(
            $this->getTracker(),
            $this->getCSRFSynchronizerToken(),
            $this->semantic_done,
            $this->getPossibleFields(),
            $this->getFieldId()
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
            } elseif ($this->checkFieldIdIsValidForTracker($values)) {
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

            $this->redirectToVelocityAdmin($request->get('tracker'));
        }

        if ($request->exist('delete')) {
            $csrf = $this->getCSRFSynchronizerToken();
            $csrf->check();

            $this->getSemanticDao()->removeField($this->getTracker()->getId());

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-velocity', 'Semantic velocity unset with success')
            );

            $this->redirectToVelocityAdmin($request->get('tracker'));
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
            $cdata = new \XML_SimpleXMLCDATAFactory();
            $cdata->insert($child, 'shortname', $this->getShortName());
            $cdata->insert($child, 'label', $this->getLabel());
            $cdata->insert($child, 'description', $this->getDescription());
            $child->addChild('field')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
        }
    }

    public function isUsedInSemantics(Tracker_FormElement_Field $field)
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

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-velocity', 'Velocity semantic successfully updated.')
        );
    }

    private static $instances;

    /**
     * @return SemanticVelocity
     */
    public static function load(Tracker $tracker)
    {
        if (! isset(self::$instances[$tracker->getId()])) {
            $semantic_dao   = new SemanticVelocityDao();
            $field_velocity = $semantic_dao->searchUsedVelocityField($tracker->getId());
            $field_id = isset($field_velocity['field_id']) ? $field_velocity['field_id'] : 0;

            $factory = Tracker_FormElementFactory::instance();
            $field   = $factory->getFieldById($field_id);

            return self::forceLoad($tracker, $field);
        }

        return self::$instances[$tracker->getId()];
    }

    private static function forceLoad(Tracker $tracker, ?Tracker_FormElement_Field $field = null)
    {
        $semantic_done                      = SemanticDone::load($tracker);
        self::$instances[$tracker->getId()] = new SemanticVelocity($tracker, $semantic_done, $field);

        return self::$instances[$tracker->getId()];
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

    /**
     * @return Tracker_FormElement_Field|null
     */
    public function getVelocityField()
    {
        return $this->velocity_field;
    }

    /**
     * @return BacklogRetriever
     */
    private function getBacklogRetriever()
    {
        return new BacklogRetriever(PlanningFactory::build());
    }

    private function redirectToVelocityAdmin($tracker_id)
    {
        $GLOBALS['Response']->redirect(
            TRACKER_BASE_URL . "?" . http_build_query(
                [
                    "semantic" => "velocity",
                    "func"     => "admin-semantic",
                    "tracker"  => $tracker_id
                ]
            )
        );
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    private function getPossibleFields()
    {
        return Tracker_FormElementFactory::instance()->getUsedFormElementsByType(
            $this->getTracker(),
            array('int', 'float')
        );
    }

    private function checkFieldIdIsValidForTracker($field_id)
    {
        foreach ($this->getPossibleFields() as $field) {
            if ((int) $field->getId() === (int) $field_id) {
                return true;
            }
        }

        return false;
    }
}
