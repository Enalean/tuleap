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

use Tuleap\Tracker\FormElement\Field\ArtifactId\ArtifactIdField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\Burndown\BurndownField;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tuleap\Tracker\FormElement\Field\CrossReferences\CrossReferencesField;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\Files\FilesField;
use Tuleap\Tracker\FormElement\Field\Float\FloatField;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\FormElement\Field\LastUpdateBy\LastUpdateByField;
use Tuleap\Tracker\FormElement\Field\LastUpdateDate\LastUpdateDateField;
use Tuleap\Tracker\FormElement\Field\List\CheckboxField;
use Tuleap\Tracker\FormElement\Field\List\MultiSelectboxField;
use Tuleap\Tracker\FormElement\Field\List\OpenListField;
use Tuleap\Tracker\FormElement\Field\List\RadioButtonField;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;
use Tuleap\Tracker\FormElement\Field\PerTrackerArtifactId\PerTrackerArtifactIdField;
use Tuleap\Tracker\FormElement\Field\Priority\PriorityField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\SubmittedByField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\SubmittedOnField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\XML\Updater\FieldChange\FieldChangeComputedXMLUpdater;
use Tuleap\Tracker\XML\Updater\FieldChange\FieldChangeExternalFieldXMLUpdater;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
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
        TrackerField $field,
        $submitted_value,
    ) {
        $updater = $field->accept($this);
        \assert($updater instanceof Tracker_XML_Updater_FieldChange_FieldChangeXMLUpdater);
        $updater->update($field_change_xml, $submitted_value);
    }

    #[\Override]
    public function visitArtifactLink(ArtifactLinkField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitDate(DateField $field)
    {
        return $this->date_updater;
    }

    #[\Override]
    public function visitFile(FilesField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitFloat(FloatField $field)
    {
        return $this->float_updater;
    }

    #[\Override]
    public function visitInteger(IntegerField $field)
    {
        return $this->integer_updater;
    }

    #[\Override]
    public function visitOpenList(OpenListField $field)
    {
        return $this->open_list_updater;
    }

    #[\Override]
    public function visitPermissionsOnArtifact(PermissionsOnArtifactField $field)
    {
        return $this->perms_updater;
    }

    #[\Override]
    public function visitString(StringField $field)
    {
        return $this->string_updater;
    }

    #[\Override]
    public function visitText(TextField $field)
    {
        return $this->text_updater;
    }

    #[\Override]
    public function visitArtifactId(ArtifactIdField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitPerTrackerArtifactId(PerTrackerArtifactIdField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitBurndown(BurndownField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitCheckbox(CheckboxField $field)
    {
        return $this->list_updater;
    }

    #[\Override]
    public function visitCrossReferences(CrossReferencesField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitLastUpdateDate(LastUpdateDateField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitMultiSelectbox(MultiSelectboxField $field)
    {
        return $this->list_updater;
    }

    #[\Override]
    public function visitRadiobutton(RadioButtonField $field)
    {
        return $this->list_updater;
    }

    #[\Override]
    public function visitSelectbox(SelectboxField $field)
    {
        return $this->list_updater;
    }

    #[\Override]
    public function visitSubmittedBy(SubmittedByField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitLastModifiedBy(LastUpdateByField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitSubmittedOn(SubmittedOnField $field)
    {
        return $this->unknown_updater;
    }

    #[\Override]
    public function visitComputed(ComputedField $field)
    {
        return $this->computed_updater;
    }

    #[\Override]
    public function visitExternalField(TrackerFormElementExternalField $element)
    {
        return $this->external_field_updater;
    }

    #[\Override]
    public function visitPriority(PriorityField $field)
    {
        return $this->unknown_updater;
    }
}
