<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Burndown;
use Tracker_FormElement_Field_CrossReferences;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_LastModifiedBy;
use Tracker_FormElement_Field_OpenList;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tracker_FormElement_Field_Radiobutton;
use Tracker_FormElement_Field_SubmittedBy;
use Tracker_FormElement_FieldVisitor;
use Tuleap\Tracker\FormElement\Field\ArtifactId\ArtifactIdField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\Float\FloatField;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\FormElement\Field\LastUpdateDate\LastUpdateDateField;
use Tuleap\Tracker\FormElement\Field\List\MultiSelectboxField;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\FormElement\Field\List\CheckboxField;
use Tuleap\Tracker\FormElement\Field\PerTrackerArtifactId\PerTrackerArtifactIdField;
use Tuleap\Tracker\FormElement\Field\Priority\PriorityField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\SubmittedOnField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;

final class GreaterThanFieldComparisonVisitor implements Tracker_FormElement_FieldVisitor, FieldComparisonVisitor
{
    /** @return FieldFromWhereBuilder */
    public function getFromWhereBuilder(Tracker_FormElement_Field $field)
    {
        return $field->accept($this);
    }

    public function visitArtifactLink(ArtifactLinkField $field)
    {
        return null;
    }

    public function visitDate(DateField $field)
    {
        return new DateTimeFieldFromWhereBuilder(
            new FromWhereComparisonFieldBuilder(),
            new GreaterThanComparison\ForDateTime(
                new DateTimeValueRounder()
            )
        );
    }

    public function visitFile(Tracker_FormElement_Field_File $field)
    {
        return null;
    }

    public function visitFloat(FloatField $field)
    {
        return new GreaterThanComparison\ForFloat(
            new FromWhereComparisonFieldBuilder()
        );
    }

    public function visitInteger(IntegerField $field)
    {
        return new GreaterThanComparison\ForInteger(
            new FromWhereComparisonFieldBuilder()
        );
    }

    public function visitOpenList(Tracker_FormElement_Field_OpenList $field)
    {
        return null;
    }

    public function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $field)
    {
        return null;
    }

    public function visitString(StringField $field)
    {
        return null;
    }

    public function visitText(TextField $field)
    {
        return null;
    }

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $field)
    {
        return null;
    }

    public function visitCheckbox(CheckboxField $field)
    {
        return null;
    }

    public function visitMultiSelectbox(MultiSelectboxField $field)
    {
        return null;
    }

    public function visitSelectbox(SelectboxField $field)
    {
        return null;
    }

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $field)
    {
        return null;
    }

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $field)
    {
        return null;
    }

    public function visitArtifactId(ArtifactIdField $field)
    {
        return null;
    }

    public function visitPerTrackerArtifactId(PerTrackerArtifactIdField $field)
    {
        return null;
    }

    public function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $field)
    {
        return null;
    }

    public function visitBurndown(Tracker_FormElement_Field_Burndown $field)
    {
        return null;
    }

    public function visitLastUpdateDate(LastUpdateDateField $field)
    {
        return new DateTimeReadOnlyFieldFromWhereBuilder(
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new GreaterThanComparison\ForLastUpdateDate(
                new DateTimeValueRounder()
            )
        );
    }

    public function visitSubmittedOn(SubmittedOnField $field)
    {
        return new DateTimeReadOnlyFieldFromWhereBuilder(
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new GreaterThanComparison\ForSubmittedOn(
                new DateTimeValueRounder()
            )
        );
    }

    public function visitComputed(ComputedField $field)
    {
        return null;
    }

    public function visitExternalField(TrackerFormElementExternalField $element)
    {
        return null;
    }

    public function visitPriority(PriorityField $field)
    {
        return null;
    }
}
