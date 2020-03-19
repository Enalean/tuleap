<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\PostAction\Visitor;

/**
 *
 * Post action occuring when transition is run
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
abstract class Transition_PostAction
{

    /**
     * @var Transition the transition
     */
    protected $transition;

    /**
     * @var int Id of the post action
     */
    protected $id;

    /**
     * @var $bypass_permissions true if permissions on field can be bypassed at submission or update
     */
    protected $bypass_permissions = false;

    /**
     * Constructor
     *
     * @param Transition $transition The transition the post action belongs to
     * @param int $id Id of the post action
     */
    public function __construct(Transition $transition, $id)
    {
        $this->transition = $transition;
        $this->id         = $id;
    }

    /**
     * Return ID of the post-action
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the transition
     *
     * @return Transition
     */
    public function getTransition()
    {
        return $this->transition;
    }

    protected function addFeedback(string $level, string $message)
    {
        $GLOBALS['Response']->addUniqueFeedback($level, $message);
    }

    /**
     * Execute actions before transition happens
     *
     * @param array  &$fields_data Request field data (array[field_id] => data)
     * @param PFUser $current_user The user who are performing the update
     *
     * @return void
     */
    public function before(array &$fields_data, PFUser $current_user)
    {
    }

    /**
     * Execute actions after transition happens
     *
     * @return void
     */
    public function after(Tracker_Artifact_Changeset $changeset)
    {
    }

    /**
     * Get the shortname of the post action
     *
     * @return string
     */
    abstract public function getShortName();

    /**
     * Get the label of the post action
     *
     * @return string
     */
    abstract public static function getLabel();

    /**
     * Say if the action is well defined
     *
     * @return bool
     */
    abstract public function isDefined();

    /**
     * Export postactions to XML
     *
     * @param SimpleXMLElement &$root     the node to which the postaction is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    abstract public function exportToXml(SimpleXMLElement $root, $xmlMapping);

    /**
     * Get the value of bypass_permissions
     *
     *
     * @return bool
     */
    abstract public function bypassPermissions(Tracker_FormElement_Field $field);

    abstract public function accept(Visitor $visitor);
}
