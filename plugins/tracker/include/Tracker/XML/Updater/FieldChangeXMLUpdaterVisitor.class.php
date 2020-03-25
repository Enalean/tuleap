<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\XML\Updater\FieldChange\FieldChangeComputedXMLUpdater;

class Tracker_XML_Updater_FieldChangeXMLUpdaterVisitor implements Tracker_FormElement_FieldVisitor
{

    /**
     * @var Tracker_XML_Updater_FieldChange_FieldChangeOpenListXMLUpdater
     */
    private $open_list_updater;

    /**
     * @var Tracker_XML_Updater_FieldChange_FieldChangePermissionsOnArtifactXMLUpdater
     */
    private $perms_updater;

    /**
     * @var Tracker_XML_Updater_FieldChange_FieldChangeUnknownXMLUpdater
     */
    private $unknown_updater;

    /**
     * @var Tracker_XML_Updater_FieldChange_FieldChangeTextXMLUpdater
     */
    private $text_updater;

    /**
     * @var Tracker_XML_Updater_FieldChange_FieldChangeStringXMLUpdater
     */
    private $string_updater;

    /**
     * @var Tracker_XML_Updater_FieldChange_FieldChangeIntegerXMLUpdater
     */
    private $integer_updater;

    /**
     * @var Tracker_XML_Updater_FieldChange_FieldChangeFloatXMLUpdater
     */
    private $float_updater;

    /**
     * @var Tracker_XML_Updater_FieldChange_FieldChangeDateXMLUpdater
     */
    private $date_updater;

    /**
     * @var FieldChangeComputedXMLUpdater
     */
    private $computed_updater;

    /**
     * @var Tracker_XML_Updater_FieldChange_FieldChangeListXMLUpdater
     */
    private $list_updater;

    public function __construct(
        Tracker_XML_Updater_FieldChange_FieldChangeDateXMLUpdater $date_updater,
        Tracker_XML_Updater_FieldChange_FieldChangeFloatXMLUpdater $float_updater,
        Tracker_XML_Updater_FieldChange_FieldChangeIntegerXMLUpdater $integer_updater,
        Tracker_XML_Updater_FieldChange_FieldChangeTextXMLUpdater $text_updater,
        Tracker_XML_Updater_FieldChange_FieldChangeStringXMLUpdater $string_updater,
        Tracker_XML_Updater_FieldChange_FieldChangePermissionsOnArtifactXMLUpdater $perms_updater,
        Tracker_XML_Updater_FieldChange_FieldChangeListXMLUpdater $list_updater,
        Tracker_XML_Updater_FieldChange_FieldChangeOpenListXMLUpdater $open_list_updater,
        FieldChangeComputedXMLUpdater $computed_updater,
        Tracker_XML_Updater_FieldChange_FieldChangeUnknownXMLUpdater $unknown_updater
    ) {
        $this->date_updater      = $date_updater;
        $this->float_updater     = $float_updater;
        $this->integer_updater   = $integer_updater;
        $this->text_updater      = $text_updater;
        $this->string_updater    = $string_updater;
        $this->unknown_updater   = $unknown_updater;
        $this->perms_updater     = $perms_updater;
        $this->list_updater      = $list_updater;
        $this->open_list_updater = $open_list_updater;
        $this->computed_updater  = $computed_updater;
    }

    public function update(
        SimpleXMLElement $field_change_xml,
        Tracker_FormElement_Field $field,
        $submitted_value
    ) {
        $updater = $field->accept($this);
        \assert($updater instanceof Tracker_XML_Updater_FieldChange_FieldChangeXMLUpdater);
        $updater->update($field_change_xml, $submitted_value);
    }

    public function visitArtifactLink(Tracker_FormElement_Field_ArtifactLink $field)
    {
        return $this->unknown_updater;
    }

    public function visitDate(Tracker_FormElement_Field_Date $field)
    {
        return $this->date_updater;
    }

    public function visitFile(Tracker_FormElement_Field_File $field)
    {
        return $this->unknown_updater;
    }

    public function visitFloat(Tracker_FormElement_Field_Float $field)
    {
        return $this->float_updater;
    }

    public function visitInteger(Tracker_FormElement_Field_Integer $field)
    {
        return $this->integer_updater;
    }

    public function visitOpenList(Tracker_FormElement_Field_OpenList $field)
    {
        return $this->open_list_updater;
    }

    public function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $field)
    {
        return $this->perms_updater;
    }

    public function visitString(Tracker_FormElement_Field_String $field)
    {
        return $this->string_updater;
    }

    public function visitText(Tracker_FormElement_Field_Text $field)
    {
        return $this->text_updater;
    }

    public function visitArtifactId(Tracker_FormElement_Field_ArtifactId $field)
    {
        return $this->unknown_updater;
    }

    public function visitPerTrackerArtifactId(Tracker_FormElement_Field_PerTrackerArtifactId $field)
    {
        return $this->unknown_updater;
    }

    public function visitBurndown(Tracker_FormElement_Field_Burndown $field)
    {
        return $this->unknown_updater;
    }

    public function visitCheckbox(Tracker_FormElement_Field_Checkbox $field)
    {
        return $this->list_updater;
    }

    public function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $field)
    {
        return $this->unknown_updater;
    }

    public function visitLastUpdateDate(Tracker_FormElement_Field_LastUpdateDate $field)
    {
        return $this->unknown_updater;
    }

    public function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $field)
    {
        return $this->list_updater;
    }

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $field)
    {
        return $this->list_updater;
    }

    public function visitSelectbox(Tracker_FormElement_Field_Selectbox $field)
    {
        return $this->list_updater;
    }

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $field)
    {
        return $this->unknown_updater;
    }

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $field)
    {
        return $this->unknown_updater;
    }

    public function visitSubmittedOn(Tracker_FormElement_Field_SubmittedOn $field)
    {
        return $this->unknown_updater;
    }

    public function visitComputed(Tracker_FormElement_Field_Computed $field)
    {
        return $this->computed_updater;
    }

    public function visitExternalField(TrackerFormElementExternalField $element)
    {
        return $this->unknown_updater;
    }

    public function visitPriority(Tracker_FormElement_Field_Priority $field)
    {
        return $this->unknown_updater;
    }
}
