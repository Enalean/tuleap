<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

abstract class Tracker_Semantic {

    /**
     * @var Tracker
     */
    protected $tracker;

    /**
     * Cosntructor
     *
     * @param Tracker $tracker    The tracker
     */
    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
    }

    /**
     * Set the tracker of this semantic
     *
     * @param Tracker $tracker The tracker
     *
     * @return void
     */
    public function setTracker($tracker) {
        $this->tracker = $tracker;
    }
    /**
     * Get the tracker
     *
     * @return Tracker The tracker
     */
     public function getTracker() {
         return $this->tracker;
     }

    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    public abstract function getShortName();

    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public abstract function getLabel();

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public abstract function getDescription();

    /**
     * Display the basic info about this semantic
     *
     * @return string html
     */
    public abstract function display();

    /**
     * Display the form to let the admin change the semantic
     *
     * @param Tracker_SemanticManager $sm              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param User                    $current_user    The user who made the request
     *
     * @return string html
     */
    public abstract function displayAdmin(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, User $current_user);

    /**
     * Process the form
     *
     * @param Tracker_SemanticManager $sm              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param User                    $current_user    The user who made the request
     *
     * @return void
     */
    public abstract function process(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, User $current_user);

    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root      the node to which the semantic is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public abstract function exportToXML(&$root, $xmlMapping);

    /**
     * Is the field used in semantics?
     *
     * @param Tracker_FormElement_Field the field to test if it is used in semantics or not
     *
     * @return boolean returns true if the field is used in semantics, false otherwise
     */
    public abstract function isUsedInSemantics($field);

    /**
     * Get the url to this semantic
     *
     * @return string url (for html)
     */
    public function getUrl() {
        $query = http_build_query(array(
                'tracker'  => $this->tracker->getId(),
                'func'     => 'admin-semantic',
                'semantic' => $this->getShortName(),
            ));
        return TRACKER_BASE_URL.'/?'. $query;
    }

    /**
     * Save the semantic in database
     *
     * @return bool true if success, false otherwise
     */
    public abstract function save();

    /**
    * Export the semantic to SOAP format
    * @return array the SOAPification of the semantic
    */
    public function exportToSOAP() {
        $field_name = "";
        $field      = $this->getField();
        if ($field) {
            $field_name = $field->getName();
        }

        return array('field_name' => $field_name);
    }

}
?>
