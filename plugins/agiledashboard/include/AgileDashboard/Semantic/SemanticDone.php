<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use Codendi_Request;
use PFUser;
use SimpleXMLElement;
use TemplateRendererFactory;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_Semantic;
use Tracker_Semantic_Status;
use Tracker_SemanticManager;
use TrackerManager;

class SemanticDone extends Tracker_Semantic
{
    const NAME = 'done';

    /**
     * @var Tracker_Semantic_Status
     */
    private $semantic_status;

    public function __construct(Tracker $tracker, Tracker_Semantic_Status $semantic_status)
    {
        parent::__construct($tracker);

        $this->semantic_status = $semantic_status;
    }

    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    public function getShortName()
    {
        return self::NAME;
    }

    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public function getLabel()
    {
        return dgettext('tuleap-agiledashboard', 'Done');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return dgettext('tuleap-agiledashboard', 'Define the closed status that are considered Done');
    }

    /**
     * Display the basic info about this semantic
     *
     * @return string html
     */
    public function display()
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR.'/semantic');
        $presenter = new SemanticDoneIntroPresenter($this->semantic_status->getField());

        $renderer->renderToPage('done-intro', $presenter);
    }

    /**
     * Display the form to let the admin change the semantic
     *
     * @param Tracker_SemanticManager $sm The semantic manager
     * @param TrackerManager $tracker_manager The tracker manager
     * @param Codendi_Request $request The request
     * @param PFUser $current_user The user who made the request
     *
     * @return string html
     */
    public function displayAdmin(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        $sm->displaySemanticHeader($this, $tracker_manager);

        $semantic_status_field = $this->semantic_status->getField();
        $closed_values         = array();

        if ($semantic_status_field) {
            $closed_values = $this->getClosedValues($semantic_status_field);
        }

        $renderer  = TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR.'/semantic');
        $presenter = new SemanticDoneAdminPresenter(
            $this->tracker,
            $closed_values,
            $semantic_status_field
        );

        $renderer->renderToPage('done-admin', $presenter);

        $sm->displaySemanticFooter($this, $tracker_manager);
    }

    /**
     * @return array
     */
    private function getClosedValues(Tracker_FormElement_Field $semantic_status_field)
    {
        $all_values    = $semantic_status_field->getAllValues();
        $open_values   = $this->semantic_status->getOpenValues();
        $closed_values = array();

        foreach ($all_values as $value_id => $value) {
            if (in_array($value_id, $open_values)) {
                continue;
            }

            $closed_values[] = array(
                'label' => $value->getLabel()
            );
        }

        return $closed_values;
    }

    /**
     * Process the form
     *
     * @param Tracker_SemanticManager $sm The semantic manager
     * @param TrackerManager $tracker_manager The tracker manager
     * @param Codendi_Request $request The request
     * @param PFUser $current_user The user who made the request
     *
     * @return void
     */
    public function process(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        $this->displayAdmin($sm, $tracker_manager, $request, $current_user);
    }

    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root the node to which the semantic is attached (passed by reference)
     * @param array $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        return;
    }

    /**
     * Is the field used in semantics?
     *
     * @param Tracker_FormElement_Field the field to test if it is used in semantics or not
     *
     * @return boolean returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics($field)
    {
        return $this->semantic_status->isUsedInSemantics($field);
    }

    /**
     * Save the semantic in database
     *
     * @return bool true if success, false otherwise
     */
    public function save()
    {
        return;
    }

    protected static $_instances;
    /**
     * Load an instance of a Tracker_Semantic_Status
     *
     * @param Tracker $tracker the tracker
     *
     * @return Tracker_Semantic_Status
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
        $semantic_status = Tracker_Semantic_Status::load($tracker);
        self::$_instances[$tracker->getId()] = new SemanticDone($tracker, $semantic_status);

        return self::$_instances[$tracker->getId()];
    }
}
