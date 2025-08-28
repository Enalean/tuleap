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
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;

final class BetweenFieldComparisonVisitor implements Tracker_FormElement_FieldVisitor, FieldComparisonVisitor
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
            new BetweenComparison\ForDateTime(
                new DateTimeValueRounder()
            )
        );
    }

    public function visitFile(FilesField $field)
    {
        return null;
    }

    public function visitFloat(FloatField $field)
    {
        return new BetweenComparison\ForFloat(
            new FromWhereComparisonFieldBuilder()
        );
    }

    public function visitInteger(IntegerField $field)
    {
        return new BetweenComparison\ForInteger(
            new FromWhereComparisonFieldBuilder()
        );
    }

    public function visitOpenList(OpenListField $field)
    {
        return null;
    }

    public function visitPermissionsOnArtifact(PermissionsOnArtifactField $field)
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

    public function visitRadiobutton(RadioButtonField $field)
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

    public function visitSubmittedBy(SubmittedByField $field)
    {
        return null;
    }

    public function visitLastModifiedBy(LastUpdateByField $field)
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

    public function visitCrossReferences(CrossReferencesField $field)
    {
        return null;
    }

    public function visitBurndown(BurndownField $field)
    {
        return null;
    }

    public function visitLastUpdateDate(LastUpdateDateField $field)
    {
        return new DateTimeReadOnlyFieldFromWhereBuilder(
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new BetweenComparison\ForLastUpdateDate(
                new DateTimeValueRounder()
            )
        );
    }

    public function visitSubmittedOn(SubmittedOnField $field)
    {
        return new DateTimeReadOnlyFieldFromWhereBuilder(
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new BetweenComparison\ForSubmittedOn(
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
