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
use SimpleXMLElement;
use TemplateRendererFactory;
use Tracker;
use Tracker_FormElementFactory;
use Tracker_Semantic;
use Tracker_SemanticManager;
use TrackerManager;
use Tuleap\AgileDashboard\Semantic\SemanticDone;

class SemanticVelocity extends Tracker_Semantic
{
    const NAME = 'velocity';

    /**
     * @var SemanticDone
     */
    private $semantic_done;

    /**
     * @var \Tracker_FormElement_Field
     */
    private $velocity_field;

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
        $renderer = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR . '/templates');

        $used_velocity_field = $this->getSemanticDao()->searchUsedVelocityField($this->getTracker()->getId());

        $factory  = Tracker_FormElementFactory::instance();
        $field_id =  $factory->getFormElementById($used_velocity_field['field_id']);

        $velocity_presenter  = new SemanticVelocityPresenter(
            $this->semantic_done->isSemanticDefined(),
            $field_id
        );
        $renderer->renderToPage('velocity-intro', $velocity_presenter);
    }

    public function displayAdmin(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        $sm->displaySemanticHeader($this, $tracker_manager);

        $factory         = Tracker_FormElementFactory::instance();
        $possible_fields =  $factory->getUsedFormElementsByType($this->getTracker(), array('int', 'float'));

        $used_velocity_field = $this->getSemanticDao()->searchUsedVelocityField($this->getTracker()->getId());

        $csrf = $this->getCSRFSynchronizerToken();

        $renderer  = TemplateRendererFactory::build()->getRenderer(VELOCITY_BASE_DIR.'/templates');
        $presenter = new SemanticVelocityAdminPresenter(
            $possible_fields,
            $csrf,
            $this->getTracker(),
            $this->semantic_done->isSemanticDefined(),
            $used_velocity_field['field_id']
        );

        $renderer->renderToPage('velocity-admin', $presenter);

        $sm->displaySemanticFooter($this, $tracker_manager);
    }

    public function process(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
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
        if ($this->velocity_field) {
            return $this->velocity_field->getId();
        } else {
            return 0;
        }
    }

    public function save()
    {
        return;
    }

    protected static $_instances;


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
}
