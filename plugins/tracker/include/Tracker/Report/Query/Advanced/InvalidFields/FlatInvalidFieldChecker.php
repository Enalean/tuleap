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

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;

final readonly class FlatInvalidFieldChecker
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
     * @throws ExternalFieldNotSupportedException
     * @throws FieldIsNotSupportedAtAllException
     * @throws FieldIsNotSupportedForComparisonException
     * @throws InvalidFieldException
     */
    public function checkFieldIsValidForComparison(Comparison $comparison, \Tracker_FormElement_Field $field): void
    {
        match ($field::class) {
            \Tracker_FormElement_Field_Float::class => $this->float_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tracker_FormElement_Field_Integer::class => $this->int_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tracker_FormElement_Field_String::class,
            \Tracker_FormElement_Field_Text::class => $this->text_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tracker_FormElement_Field_Date::class,
            \Tracker_FormElement_Field_LastUpdateDate::class,
            \Tracker_FormElement_Field_SubmittedOn::class => $this->date_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tracker_FormElement_Field_File::class => $this->file_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tracker_FormElement_Field_Radiobutton::class,
            \Tracker_FormElement_Field_Checkbox::class,
            \Tracker_FormElement_Field_MultiSelectbox::class,
            \Tracker_FormElement_Field_Selectbox::class => $this->list_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tracker_FormElement_Field_SubmittedBy::class,
            \Tracker_FormElement_Field_LastModifiedBy::class => $this->submitter_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tracker_FormElement_Field_ArtifactLink::class,
            \Tracker_FormElement_Field_OpenList::class,
            \Tracker_FormElement_Field_PermissionsOnArtifact::class,
            \Tracker_FormElement_Field_ArtifactId::class,
            \Tracker_FormElement_Field_PerTrackerArtifactId::class,
            \Tracker_FormElement_Field_CrossReferences::class,
            \Tracker_FormElement_Field_Burndown::class,
            \Tracker_FormElement_Field_Computed::class,
            \Tracker_FormElement_Field_Priority::class => throw new FieldIsNotSupportedAtAllException($field),
            default => throw new ExternalFieldNotSupportedException()
        };
    }
}
