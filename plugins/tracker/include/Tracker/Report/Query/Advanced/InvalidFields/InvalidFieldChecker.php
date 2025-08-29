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

final readonly class InvalidFieldChecker
{
    public function __construct(
        private FloatFieldChecker $float_field_checker,
        private IntegerFieldChecker $int_field_checker,
        private TextFieldChecker $text_field_checker,
        private DateFieldChecker $date_field_checker,
        private FileFieldChecker $file_field_checker,
        private ListFieldChecker $list_field_checker,
        private ListFieldChecker $openlist_field_checker,
        private ArtifactSubmitterChecker $submitter_checker,
        private bool $is_cross_tracker_search,
    ) {
    }

    /**
     * @throws ExternalFieldNotSupportedException
     * @throws FieldIsNotSupportedAtAllException
     * @throws FieldIsNotSupportedForComparisonException
     * @throws InvalidFieldException
     */
    public function checkFieldIsValidForComparison(Comparison $comparison, \Tuleap\Tracker\FormElement\Field\TrackerField $field): void
    {
        match ($field::class) {
            \Tuleap\Tracker\FormElement\Field\Float\FloatField::class               => $this->float_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tuleap\Tracker\FormElement\Field\Integer\IntegerField::class           => $this->int_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tuleap\Tracker\FormElement\Field\String\StringField::class,
            \Tuleap\Tracker\FormElement\Field\Text\TextField::class                 => $this->text_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tuleap\Tracker\FormElement\Field\Date\DateField::class,
            \Tuleap\Tracker\FormElement\Field\LastUpdateDate\LastUpdateDateField::class,
            \Tuleap\Tracker\FormElement\Field\SubmittedOn\SubmittedOnField::class   => $this->date_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tuleap\Tracker\FormElement\Field\Files\FilesField::class               => $this->file_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tuleap\Tracker\FormElement\Field\List\RadioButtonField::class,
            \Tuleap\Tracker\FormElement\Field\List\CheckboxField::class,
            \Tuleap\Tracker\FormElement\Field\List\MultiSelectboxField::class,
            \Tuleap\Tracker\FormElement\Field\List\SelectboxField::class            => $this->list_field_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tuleap\Tracker\FormElement\Field\SubmittedBy\SubmittedByField::class,
            \Tuleap\Tracker\FormElement\Field\LastUpdateBy\LastUpdateByField::class => $this->submitter_checker->checkFieldIsValidForComparison($comparison, $field),
            \Tuleap\Tracker\FormElement\Field\List\OpenListField::class             => $this->checkOpenList($comparison, $field),
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class,
            \Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField::class,
            \Tuleap\Tracker\FormElement\Field\ArtifactId\ArtifactIdField::class,
            \Tuleap\Tracker\FormElement\Field\PerTrackerArtifactId\PerTrackerArtifactIdField::class,
            \Tuleap\Tracker\FormElement\Field\CrossReferences\CrossReferencesField::class,
            \Tuleap\Tracker\FormElement\Field\Burndown\BurndownField::class,
            \Tuleap\Tracker\FormElement\Field\Computed\ComputedField::class,
            \Tuleap\Tracker\FormElement\Field\Priority\PriorityField::class         => throw new FieldIsNotSupportedAtAllException($field),
            default                                                                 => throw new ExternalFieldNotSupportedException()
        };
    }

    private function checkOpenList(Comparison $comparison, \Tuleap\Tracker\FormElement\Field\ListField $field): void
    {
        if ($this->is_cross_tracker_search) {
            $this->openlist_field_checker->checkFieldIsValidForComparison($comparison, $field);
        } else {
            throw new FieldIsNotSupportedAtAllException($field);
        }
    }
}
