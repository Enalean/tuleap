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
use ParagonIE\EasyDB\EasyDB;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_ArtifactId;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_Burndown;
use Tracker_FormElement_Field_Checkbox;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElement_Field_CrossReferences;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_LastModifiedBy;
use Tracker_FormElement_Field_LastUpdateDate;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_MultiSelectbox;
use Tracker_FormElement_Field_OpenList;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tracker_FormElement_Field_PerTrackerArtifactId;
use Tracker_FormElement_Field_Priority;
use Tracker_FormElement_Field_Radiobutton;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_SubmittedBy;
use Tracker_FormElement_Field_SubmittedOn;
use Tracker_FormElement_Field_Text;
use Tracker_FormElement_FieldVisitor;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use UserManager;

final class NotEqualFieldComparisonVisitor implements
    Tracker_FormElement_FieldVisitor,
    FieldComparisonVisitor
{
    public function __construct(
        private readonly EasyDB $db,
    ) {
    }

    /** @return FieldFromWhereBuilder */
    public function getFromWhereBuilder(Tracker_FormElement_Field $field)
    {
        return $field->accept($this);
    }

    public function visitArtifactLink(Tracker_FormElement_Field_ArtifactLink $field)
    {
        return null;
    }

    public function visitDate(Tracker_FormElement_Field_Date $field)
    {
        return new DateTimeFieldFromWhereBuilder(
            new FromWhereComparisonFieldBuilder(),
            new NotEqualComparison\ForDateTime(
                new DateTimeValueRounder()
            )
        );
    }

    public function visitFile(Tracker_FormElement_Field_File $field)
    {
        return new NotEqualComparison\ForFile(
            new FromWhereComparisonFieldBuilder()
        );
    }

    public function visitFloat(Tracker_FormElement_Field_Float $field)
    {
        return new NotEqualComparison\ForFloat(
            new FromWhereComparisonFieldBuilder()
        );
    }

    public function visitInteger(Tracker_FormElement_Field_Integer $field)
    {
        return new NotEqualComparison\ForInteger(
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

    public function visitString(Tracker_FormElement_Field_String $field)
    {
        return $this->visitText($field);
    }

    public function visitText(Tracker_FormElement_Field_Text $field)
    {
        return new NotEqualComparison\ForText(
            new FromWhereComparisonFieldBuilder(),
            $this->db,
        );
    }

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $field)
    {
        return $this->visitList($field);
    }

    public function visitCheckbox(Tracker_FormElement_Field_Checkbox $field)
    {
        return $this->visitList($field);
    }

    public function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $field)
    {
        return $this->visitList($field);
    }

    public function visitSelectbox(Tracker_FormElement_Field_Selectbox $field)
    {
        return $this->visitList($field);
    }

    private function visitList(Tracker_FormElement_Field_List $field)
    {
        $static_bind_builder  = new NotEqualComparison\ForListBindStatic(
            new FromWhereEmptyNotEqualComparisonFieldBuilder(),
            new FromWhereNotEqualComparisonListFieldBuilder()
        );
        $users_bind_builder   = new NotEqualComparison\ForListBindUsers(
            new CollectionOfListValuesExtractor(),
            new FromWhereEmptyNotEqualComparisonFieldBuilder(),
            new FromWhereNotEqualComparisonListFieldBuilder()
        );
        $ugroups_bind_builder = new NotEqualComparison\ForListBindUgroups(
            new CollectionOfListValuesExtractor(),
            new FromWhereEmptyNotEqualComparisonFieldBuilder(),
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

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $field)
    {
        return new ListReadOnlyFieldFromWhereBuilder(
            new CollectionOfListValuesExtractor(),
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new NotEqualComparison\ForSubmittedBy(
                UserManager::instance()
            )
        );
    }

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $field)
    {
        return new ListReadOnlyFieldFromWhereBuilder(
            new CollectionOfListValuesExtractor(),
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new NotEqualComparison\ForLastUpdatedBy(
                UserManager::instance()
            )
        );
    }

    public function visitArtifactId(Tracker_FormElement_Field_ArtifactId $field)
    {
        return null;
    }

    public function visitPerTrackerArtifactId(Tracker_FormElement_Field_PerTrackerArtifactId $field)
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

    public function visitLastUpdateDate(Tracker_FormElement_Field_LastUpdateDate $field)
    {
        return new DateTimeReadOnlyFieldFromWhereBuilder(
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new NotEqualComparison\ForLastUpdateDate(
                new DateTimeValueRounder()
            )
        );
    }

    public function visitSubmittedOn(Tracker_FormElement_Field_SubmittedOn $field)
    {
        return new DateTimeReadOnlyFieldFromWhereBuilder(
            new FromWhereComparisonFieldReadOnlyBuilder(),
            new NotEqualComparison\ForSubmittedOn(
                new DateTimeValueRounder()
            )
        );
    }

    public function visitComputed(Tracker_FormElement_Field_Computed $field)
    {
        return null;
    }

    public function visitExternalField(TrackerFormElementExternalField $element)
    {
        return null;
    }

    public function visitPriority(Tracker_FormElement_Field_Priority $field)
    {
        return null;
    }
}
