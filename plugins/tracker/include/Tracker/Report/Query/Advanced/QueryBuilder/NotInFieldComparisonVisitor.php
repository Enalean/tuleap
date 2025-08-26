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

use BaseLanguageFactory;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Burndown;
use Tracker_FormElement_Field_CrossReferences;
use Tracker_FormElement_FieldVisitor;
use Tuleap\Tracker\FormElement\Field\ArtifactId\ArtifactIdField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
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
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;
use Tuleap\Tracker\FormElement\Field\PerTrackerArtifactId\PerTrackerArtifactIdField;
use Tuleap\Tracker\FormElement\Field\Priority\PriorityField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\SubmittedByField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\SubmittedOnField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use UserManager;

final class NotInFieldComparisonVisitor implements
    Tracker_FormElement_FieldVisitor,
    FieldComparisonVisitor
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
        return null;
    }

    public function visitFile(FilesField $field)
    {
        return null;
    }

    public function visitFloat(FloatField $field)
    {
        return null;
    }

    public function visitInteger(IntegerField $field)
    {
        return null;
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
        return $this->visitList($field);
    }

    public function visitCheckbox(CheckboxField $field)
    {
        return $this->visitList($field);
    }

    public function visitMultiSelectbox(MultiSelectboxField $field)
    {
        return $this->visitList($field);
    }

    public function visitSelectbox(SelectboxField $field)
    {
        return $this->visitList($field);
    }

    private function visitList(ListField $field)
    {
        $static_bind_builder  = new NotInComparison\ForListBindStatic(
            new CollectionOfListValuesExtractor(),
            new FromWhereNotEqualComparisonListFieldBuilder()
        );
        $users_bind_builder   = new NotInComparison\ForListBindUsers(
            new CollectionOfListValuesExtractor(),
            new FromWhereNotEqualComparisonListFieldBuilder()
        );
        $ugroups_bind_builder = new NotInComparison\ForListBindUGroups(
            new CollectionOfListValuesExtractor(),
            new FromWhereNotEqualComparisonListFieldBindUgroupsBuilder(),
            new UgroupLabelConverter(
                new ListFieldBindValueNormalizer(),
                new BaseLanguageFactory()
            )
        );

        $bind_builder = new ListFieldBindVisitor(
            $static_bind_builder,
            $users_bind_builder,
            $ugroups_bind_builder
        );

        return $bind_builder->getFromWhereBuilder($field);
    }

    public function visitSubmittedBy(SubmittedByField $field)
    {
        return new ListReadOnlyFieldFromWhereBuilder(
            new CollectionOfListValuesExtractor(),
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new NotInComparison\ForSubmittedBy(
                UserManager::instance()
            )
        );
    }

    public function visitLastModifiedBy(LastUpdateByField $field)
    {
        return new ListReadOnlyFieldFromWhereBuilder(
            new CollectionOfListValuesExtractor(),
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new NotInComparison\ForLastUpdatedBy(
                UserManager::instance()
            )
        );
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
        return null;
    }

    public function visitSubmittedOn(SubmittedOnField $field)
    {
        return null;
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
