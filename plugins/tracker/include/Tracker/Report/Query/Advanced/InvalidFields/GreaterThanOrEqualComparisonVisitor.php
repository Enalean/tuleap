<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields;

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
use Tracker_FormElement_Field_MultiSelectbox;
use Tracker_FormElement_Field_OpenList;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tracker_FormElement_Field_PerTrackerArtifactId;
use Tracker_FormElement_Field_Radiobutton;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_SubmittedBy;
use Tracker_FormElement_Field_SubmittedOn;
use Tracker_FormElement_Field_Text;
use Tracker_FormElement_FieldVisitor;

class GreaterThanOrEqualComparisonVisitor implements Tracker_FormElement_FieldVisitor, ICheckThatFieldIsAllowedForComparison
{
    /** @throws FieldIsNotSupportedForComparisonException */
    public function checkThatFieldIsAllowed(Tracker_FormElement_Field $field)
    {
        $field->accept($this);
    }

    public function visitArtifactLink(Tracker_FormElement_Field_ArtifactLink $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitDate(Tracker_FormElement_Field_Date $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitFile(Tracker_FormElement_Field_File $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitFloat(Tracker_FormElement_Field_Float $field)
    {
        // allowed, do nothing.
    }

    public function visitInteger(Tracker_FormElement_Field_Integer $field)
    {
        // allowed, do nothing.
    }

    public function visitOpenList(Tracker_FormElement_Field_OpenList $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitString(Tracker_FormElement_Field_String $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitText(Tracker_FormElement_Field_Text $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitCheckbox(Tracker_FormElement_Field_Checkbox $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitSelectbox(Tracker_FormElement_Field_Selectbox $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitArtifactId(Tracker_FormElement_Field_ArtifactId $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitPerTrackerArtifactId(Tracker_FormElement_Field_PerTrackerArtifactId $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitBurndown(Tracker_FormElement_Field_Burndown $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitLastUpdateDate(Tracker_FormElement_Field_LastUpdateDate $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitSubmittedOn(Tracker_FormElement_Field_SubmittedOn $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }

    public function visitComputed(Tracker_FormElement_Field_Computed $field)
    {
        throw new FieldIsNotSupportedForComparisonException();
    }
}
