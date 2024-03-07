<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields;

use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;

final readonly class FlatInvalidFieldChecker implements \Tracker_FormElement_FieldVisitor, IProvideTheInvalidFieldCheckerForAComparison
{
    public function __construct(
        private FloatFieldChecker $float_field_checker,
        private IntegerFieldChecker $int_field_checker,
        private TextFieldChecker $text_field_checker,
        private DateFieldChecker $date_field_checker,
        private FileFieldChecker $file_field_checker,
        private ListFieldChecker $list_field_checker,
        private ArtifactSubmitterChecker $submitter_checker,
    ) {
    }

    /**
     * @throws FieldIsNotSupportedForComparisonException
     * @throws FieldIsNotSupportedAtAllException
     * @throws ExternalFieldNotSupportedException
     */
    public function getInvalidFieldChecker(\Tracker_FormElement_Field $field): InvalidFieldChecker
    {
        return $field->accept($this);
    }

    public function visitFloat(\Tracker_FormElement_Field_Float $field): InvalidFieldChecker
    {
        return $this->float_field_checker;
    }

    public function visitInteger(\Tracker_FormElement_Field_Integer $field): InvalidFieldChecker
    {
        return $this->int_field_checker;
    }

    public function visitString(\Tracker_FormElement_Field_String $field): InvalidFieldChecker
    {
        return $this->text_field_checker;
    }

    public function visitText(\Tracker_FormElement_Field_Text $field): InvalidFieldChecker
    {
        return $this->text_field_checker;
    }

    public function visitDate(\Tracker_FormElement_Field_Date $field): InvalidFieldChecker
    {
        return $this->date_field_checker;
    }

    public function visitLastUpdateDate(\Tracker_FormElement_Field_LastUpdateDate $field): InvalidFieldChecker
    {
        return $this->date_field_checker;
    }

    public function visitSubmittedOn(\Tracker_FormElement_Field_SubmittedOn $field): InvalidFieldChecker
    {
        return $this->date_field_checker;
    }

    public function visitFile(\Tracker_FormElement_Field_File $field): InvalidFieldChecker
    {
        return $this->file_field_checker;
    }

    public function visitRadiobutton(\Tracker_FormElement_Field_Radiobutton $field): InvalidFieldChecker
    {
        return $this->list_field_checker;
    }

    public function visitCheckbox(\Tracker_FormElement_Field_Checkbox $field): InvalidFieldChecker
    {
        return $this->list_field_checker;
    }

    public function visitMultiSelectbox(\Tracker_FormElement_Field_MultiSelectbox $field): InvalidFieldChecker
    {
        return $this->list_field_checker;
    }

    public function visitSelectbox(\Tracker_FormElement_Field_Selectbox $field): InvalidFieldChecker
    {
        return $this->list_field_checker;
    }

    public function visitSubmittedBy(\Tracker_FormElement_Field_SubmittedBy $field): InvalidFieldChecker
    {
        return $this->submitter_checker;
    }

    public function visitLastModifiedBy(\Tracker_FormElement_Field_LastModifiedBy $field): InvalidFieldChecker
    {
        return $this->submitter_checker;
    }

    public function visitArtifactLink(\Tracker_FormElement_Field_ArtifactLink $field): never
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitOpenList(\Tracker_FormElement_Field_OpenList $field): never
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitPermissionsOnArtifact(\Tracker_FormElement_Field_PermissionsOnArtifact $field): never
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitArtifactId(\Tracker_FormElement_Field_ArtifactId $field): never
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitPerTrackerArtifactId(\Tracker_FormElement_Field_PerTrackerArtifactId $field): never
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitCrossReferences(\Tracker_FormElement_Field_CrossReferences $field): never
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitBurndown(\Tracker_FormElement_Field_Burndown $field): never
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitComputed(\Tracker_FormElement_Field_Computed $field): never
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitPriority(\Tracker_FormElement_Field_Priority $field): never
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitExternalField(TrackerFormElementExternalField $element): never
    {
        throw new ExternalFieldNotSupportedException();
    }
}
