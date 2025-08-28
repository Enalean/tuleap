<?php
/**
 * Copyright (c) Enalean SAS, 2017 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\CustomColumn;

use Codendi_HTMLPurifier;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_FieldVisitor;
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

/**
 * I am responsible of building the replacement string for a given field to be rendered in ValueFormatter
 */
class ReplaceValueVisitor implements Tracker_FormElement_FieldVisitor
{
    /**
     * @var Tracker_Artifact_Changeset
     */
    private $changeset;

    /**
     * @var TrackerField
     */
    private $field;

    public function __construct(TrackerField $field, Tracker_Artifact_Changeset $changeset)
    {
        $this->field     = $field;
        $this->changeset = $changeset;
    }

    public function getReplacement()
    {
        return $this->field->accept($this);
    }

    public function visitArtifactLink(ArtifactLinkField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitDate(DateField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitFile(FilesField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitFloat(FloatField $field)
    {
        $changeset_value = $this->changeset->getValue($field);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getFloat();
    }

    public function visitInteger(IntegerField $field)
    {
        $changeset_value = $this->changeset->getValue($field);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getInteger();
    }

    public function visitOpenList(OpenListField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitPermissionsOnArtifact(PermissionsOnArtifactField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitString(StringField $field)
    {
        $purifier        = Codendi_HTMLPurifier::instance();
        $changeset_value = $this->changeset->getValue($field);
        if (! $changeset_value) {
            return '';
        }

        return $purifier->purify($changeset_value->getText(), CODENDI_PURIFIER_STRIP_HTML);
    }

    public function visitText(TextField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitArtifactId(ArtifactIdField $field)
    {
        return $this->changeset->getArtifact()->getId();
    }

    public function visitPerTrackerArtifactId(PerTrackerArtifactIdField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitBurndown(BurndownField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitCheckbox(CheckboxField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitCrossReferences(CrossReferencesField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitLastUpdateDate(LastUpdateDateField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitMultiSelectbox(MultiSelectboxField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitRadiobutton(RadioButtonField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitSelectbox(SelectboxField $field)
    {
        $changeset_value = $this->changeset->getValue($field);
        if (! $changeset_value) {
            return '';
        }

        $values = $changeset_value->getListValues();
        if (count($values) === 0) {
            return '';
        }

        reset($values);
        $first_value = current($values);

        return $first_value->getLabel();
    }

    public function visitSubmittedBy(SubmittedByField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitLastModifiedBy(LastUpdateByField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitSubmittedOn(SubmittedOnField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitComputed(ComputedField $field)
    {
        throw new UnsupportedFieldException();
    }

    public function visitExternalField(TrackerFormElementExternalField $element)
    {
        throw new UnsupportedFieldException();
    }

    public function visitPriority(PriorityField $field)
    {
        throw new UnsupportedFieldException();
    }
}
