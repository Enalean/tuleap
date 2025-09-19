<?php
/**
 * Copyright Enalean (c) 2015-Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

namespace Tuleap\Tracker\FormElement\StaticField;

use PFUser;
use Tracker_FormElement_Visitor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\TrackerFormElement;

/**
 * The base class for static fields in trackers.
 * Static Fields are not real fields, as they don't have a specific value for each artifact.
 * The value can be updated, but is the same for every artifact.
 */
abstract class TrackerStaticField extends TrackerFormElement
{
    // TODO : remove these functions (no need for that kind of "fields"
    #[\Override]
    public function fetchAddCriteria($used, $prefix = '')
    {
        return '';
    }

    #[\Override]
    public function fetchAddColumn($used, $prefix = '')
    {
        return '';
    }

    #[\Override]
    public function fetchAddCardFields(array $used_fields, string $prefix = ''): string
    {
        return '';
    }

    /**
     * Fetch the element for the update artifact form
     *
     *
     * @return string html
     */
    #[\Override]
    public function fetchArtifact(
        Artifact $artifact,
        array $submitted_values,
        array $additional_classes,
    ) {
        return $this->fetchReadOnly();
    }

    /**
     *
     * @return string html
     */
    #[\Override]
    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchArtifact($artifact, $submitted_values, []);
    }

    #[\Override]
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
    #[\Override]
    public function fetchArtifactReadOnly(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchReadOnly();
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    #[\Override]
    public function fetchSubmit(array $submitted_values)
    {
        return $this->fetchReadOnly();
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    #[\Override]
    public function fetchSubmitMasschange()
    {
        return $this->fetchReadOnly();
    }

    /**
     * Say if the field is updateable
     *
     * @return bool
     */
    #[\Override]
    public function isUpdateable()
    {
        return false;
    }

    /**
     * Say if the field is submitable
     *
     * @return bool
     */
    #[\Override]
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
    #[\Override]
    public function getCannotRemoveMessage()
    {
        return '';
    }

    #[\Override]
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
    #[\Override]
    public function userCanRead(?PFUser $user = null)
    {
        return true;
    }

    abstract protected function fetchReadOnly();

    /**
     * @see TrackerFormElement::fetchArtifactCopyMode
     */
    #[\Override]
    public function fetchArtifactCopyMode(Artifact $artifact, array $submitted_values)
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
    #[\Override]
    public function getRESTAvailableValues()
    {
        return null;
    }

    public function getDefaultValue()
    {
        return null;
    }

    #[\Override]
    public function getDefaultRESTValue()
    {
        return $this->getDefaultValue();
    }
}
