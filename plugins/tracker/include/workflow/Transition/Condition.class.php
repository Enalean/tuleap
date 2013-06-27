<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Condition on a transition
 */
abstract class Workflow_Transition_Condition {

    /** @var string */
    public $identifier = 'generic_condition';

    /** @var Transition */
    protected $transition;

    public function __construct(Transition $transition) {
        $this->transition = $transition;
    }

    /**
     * Save the condition object in database
     */
    public abstract function saveObject();

    /**
     * Export condition to XML
     *
     * @param SimpleXMLElement &$root     the node to which the condition is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public abstract function exportToXml(SimpleXMLElement $root, $xmlMapping);

    /**
     * Get the html code needed to display the condition in workflow admin
     *
     * @return string html
     */
    public abstract function fetch();

    /**
     * Validate the condition
     *
     * @return boolean
     */
    public abstract function validate($fields_data, Tracker_Artifact $artifact);

    public function getTransition() {
        return $this->transition;
    }
}

?>
