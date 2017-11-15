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

namespace Tuleap\Tracker\Semantic;

use Codendi_Request;
use PFUser;
use SimpleXMLElement;
use TemplateRendererFactory;
use Tracker;
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
        return dgettext('tuleap-tracker', 'Done');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return dgettext('tuleap-tracker', 'Define the closed status that are considered Done');
    }

    /**
     * Display the basic info about this semantic
     *
     * @return string html
     */
    public function display()
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR.'/semantic');
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
        return;
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
        return;
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
