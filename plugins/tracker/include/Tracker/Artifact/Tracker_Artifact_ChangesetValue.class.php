<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueRepresentationData;

/**
 * Manage values in changeset for fields
 * @abstract
 */
abstract class Tracker_Artifact_ChangesetValue
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Tracker_Artifact_Changeset
     */
    protected $changeset;

    /**
     * @var Tracker_FormElement_Field
     */
    protected $field;

    /**
     * @var bool
     */
    protected $has_changed;

    /**
     * @param int $id
     * @param Tracker_FormElement_Field $field
     * @param bool $has_changed
     */
    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed)
    {
        $this->id          = $id;
        $this->field       = $field;
        $this->has_changed = $has_changed;
        $this->changeset   = $changeset;
    }

    /**
     * Get the id of the value
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the field of the value
     *
     * @return Tracker_FormElement_Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Returns true if the changeset value has changed, false otherwise
     *
     * @return bool true if the changeset value has changed, false otherwise
     */
    public function hasChanged()
    {
        return $this->has_changed;
    }

    /**
     * Returns a diff between current changeset value and changeset value in param
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset value to compare to this changeset value
     * @param string                          $format          The format of the diff (html, text, ...)
     * @param PFUser                          $user            The user or null
     * @param bool $ignore_perms
     *
     * @return string|false The difference between another $changeset_value, false if no differences
     */
    abstract public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false);

    abstract public function nodiff($format = 'html');

    /**
     * Returns a mail format diff between current changeset value and changeset value in param
     *
     * @return string|false The difference between another $changeset_value, false if no differences
     */
    public function mailDiff(
        $changeset_value,
        $artifact_id,
        $changeset_id,
        $ignore_perms,
        $format = 'html',
        ?PFUser $user = null
    ) {
        return $this->diff($changeset_value, $format, $user, $ignore_perms);
    }

    /**
     * Returns a modal format diff between current changeset value and changeset value in param
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset value to compare to this changeset value
     * @param string                          $format          The format of the diff (html, text, ...)
     * @param PFUser                          $user            The user or null
     *
     * @return string|false The difference between another $changeset_value, false if no differences
     */
    public function modalDiff($changeset_value, $format = 'html', ?PFUser $user = null)
    {
        return $this->diff($changeset_value, $format, $user);
    }

    /**
     * Return the REST value of this changeset value
     *
     *
     * @return ArtifactFieldValueRepresentationData|null
     */
    abstract public function getRESTValue(PFUser $user);

    /**
     * Return the full REST value of this changeset value
     *
     *
     * @return ArtifactFieldValueRepresentationData|null
     */
    abstract public function getFullRESTValue(PFUser $user);

    /**
     * @return mixed
     */
    abstract public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor);

    /**
     * Returns the Json value of this changeset value
     *
     * @return string The value of this artifact changeset value for Json format
     */
    public function getJsonValue()
    {
        return $this->getValue();
    }

    /**
     * Returns the value of this changeset value
     *
     * @return mixed The value of this artifact changeset value
     */
    abstract public function getValue();

    /**
     * @return Tracker_Artifact_Changeset
     */
    public function getChangeset()
    {
        return $this->changeset;
    }

    protected function getRESTRepresentation($value)
    {
        $artifact_field_value_representation = new ArtifactFieldValueRepresentation();
        $artifact_field_value_representation->build(
            $this->field->getId(),
            $this->field->getLabel(),
            $value
        );

        return $artifact_field_value_representation;
    }

    protected function getFullRESTRepresentation($value)
    {
        $artifact_field_value_full_representation = new ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $value
        );

        return $artifact_field_value_full_representation;
    }
}
