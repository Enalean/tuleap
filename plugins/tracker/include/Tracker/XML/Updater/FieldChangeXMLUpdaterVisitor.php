<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\XML\Updater\FieldChange\FieldChangeComputedXMLUpdater;
use Tuleap\Tracker\XML\Updater\FieldChange\FieldChangeExternalFieldXMLUpdater;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_XML_Updater_FieldChangeXMLUpdaterVisitor implements Tracker_FormElement_FieldVisitor
{
    public function __construct(
        private Tracker_XML_Updater_FieldChange_FieldChangeDateXMLUpdater $date_updater,
        private Tracker_XML_Updater_FieldChange_FieldChangeFloatXMLUpdater $float_updater,
        private Tracker_XML_Updater_FieldChange_FieldChangeIntegerXMLUpdater $integer_updater,
        private Tracker_XML_Updater_FieldChange_FieldChangeTextXMLUpdater $text_updater,
        private Tracker_XML_Updater_FieldChange_FieldChangeStringXMLUpdater $string_updater,
        private Tracker_XML_Updater_FieldChange_FieldChangePermissionsOnArtifactXMLUpdater $perms_updater,
        private Tracker_XML_Updater_FieldChange_FieldChangeListXMLUpdater $list_updater,
        private Tracker_XML_Updater_FieldChange_FieldChangeOpenListXMLUpdater $open_list_updater,
        private FieldChangeComputedXMLUpdater $computed_updater,
        private Tracker_XML_Updater_FieldChange_FieldChangeUnknownXMLUpdater $unknown_updater,
        private FieldChangeExternalFieldXMLUpdater $external_field_updater,
    ) {
    }

    public function update(
        SimpleXMLElement $field_change_xml,
        Tracker_FormElement_Field $field,
        $submitted_value,
    ) {
        $updater = $field->accept($this);
        \assert($updater instanceof Tracker_XML_Updater_FieldChange_FieldChangeXMLUpdater);
        $updater->update($field_change_xml, $submitted_value);
    }

    public function visitArtifactLink(ArtifactLinkField $field)
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
        return $this->external_field_updater;
    }

    public function visitPriority(Tracker_FormElement_Field_Priority $field)
    {
        return $this->unknown_updater;
    }
}
