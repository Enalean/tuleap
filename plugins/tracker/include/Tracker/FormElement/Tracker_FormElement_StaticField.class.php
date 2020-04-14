<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright Enalean (c) 2015. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * The base class for static fields in trackers.
 * Static Fields are not real fields, as they don't have a specific value for each artifact.
 * The value can be updated, but is the same for every artifact.
 */
abstract class Tracker_FormElement_StaticField extends Tracker_FormElement
{
    // TODO : remove these functions (no need for that kind of "fields"
    public function fetchAddCriteria($used, $prefix = '')
    {
        return '';
    }

    public function fetchAddColumn($used, $prefix = '')
    {
        return '';
    }

    public function fetchAddTooltip($used, $prefix = '')
    {
        return '';
    }

    /**
     * Fetch the element for the update artifact form
     *
     *
     * @return string html
     */
    public function fetchArtifact(
        Tracker_Artifact $artifact,
        array $submitted_values,
        array $additional_classes
    ) {
        return $this->fetchReadOnly();
    }

    /**
     *
     * @return string html
     */
    public function fetchArtifactForOverlay(Tracker_Artifact $artifact, array $submitted_values)
    {
        return $this->fetchArtifact($artifact, $submitted_values, []);
    }

    public function fetchSubmitForOverlay(array $submitted_values)
    {
        return $this->fetchSubmit($submitted_values);
    }

    /**
     * Fetch the element for the artifact in read only mode
     *
     *
     * @return string html
     */
    public function fetchArtifactReadOnly(Tracker_Artifact $artifact, array $submitted_values)
    {
        return $this->fetchReadOnly();
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmit(array $submitted_values)
    {
        return $this->fetchReadOnly();
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmitMasschange()
    {
        return $this->fetchReadOnly();
    }
    /**
     * Say if the field is updateable
     *
     * @return bool
     */
    public function isUpdateable()
    {
        return false;
    }

    /**
     * Say if the field is submitable
     *
     * @return bool
     */
    public function isSubmitable()
    {
        return false;
    }

    /**
     * Is the form element can be removed from usage?
     * This method is to prevent tracker inconsistency
     *
     * @return string returns null if the field can be unused, a message otherwise
     */
    public function getCannotRemoveMessage()
    {
        return '';
    }

    public function canBeRemovedFromUsage()
    {
        return true;
    }

    /**
     * return true if user has Read or Update permission on this field
     *
     * @param PFUser $user The user. if not given or null take the current user
     *
     * @return bool
     */
    public function userCanRead(?PFUser $user = null)
    {
        return true;
    }

    abstract protected function fetchReadOnly();

    /**
     * @see Tracker_FormElement::fetchArtifactCopyMode
     */
    public function fetchArtifactCopyMode(Tracker_Artifact $artifact, array $submitted_values)
    {
        return $this->fetchArtifactReadOnly($artifact, $submitted_values);
    }

    /**
     * Accessor for visitors
     *
     */
    public function accept(Tracker_FormElement_Visitor $visitor)
    {
        $visitor->visit($this);
    }

    /**
     * Get available values of this field for REST usage
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getRESTAvailableValues()
    {
        return null;
    }

    public function isCollapsed()
    {
        return false;
    }

    public function getDefaultValue()
    {
        return null;
    }

    public function getDefaultRESTValue()
    {
        return $this->getDefaultValue();
    }
}
