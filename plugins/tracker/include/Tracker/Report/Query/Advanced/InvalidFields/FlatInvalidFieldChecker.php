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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FlatFloatFieldChecker;

final readonly class FlatInvalidFieldChecker implements \Tracker_FormElement_FieldVisitor, IProvideTheInvalidFieldCheckerForAComparison
{
    public function __construct(
        private Comparison $comparison,
        private FlatFloatFieldChecker $float_field_checker,
        private EqualComparisonVisitor $equal_checker,
        private NotEqualComparisonVisitor $not_equal_checker,
        private LesserThanComparisonVisitor $lesser_than_checker,
        private LesserThanOrEqualComparisonVisitor $lesser_than_or_equal_checker,
        private GreaterThanComparisonVisitor $greater_than_checker,
        private GreaterThanOrEqualComparisonVisitor $greater_than_or_equal_checker,
        private BetweenComparisonVisitor $between_checker,
        private InComparisonVisitor $in_checker,
        private NotInComparisonVisitor $not_in_checker,
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

    private function matchComparisonToFieldChecker(\Tracker_FormElement_Field $field): InvalidFieldChecker
    {
        return match ($this->comparison->getType()) {
            ComparisonType::Equal => $this->equal_checker->getInvalidFieldChecker($field),
            ComparisonType::NotEqual => $this->not_equal_checker->getInvalidFieldChecker($field),
            ComparisonType::LesserThan => $this->lesser_than_checker->getInvalidFieldChecker($field),
            ComparisonType::LesserThanOrEqual => $this->lesser_than_or_equal_checker->getInvalidFieldChecker($field),
            ComparisonType::GreaterThan => $this->greater_than_checker->getInvalidFieldChecker($field),
            ComparisonType::GreaterThanOrEqual => $this->greater_than_or_equal_checker->getInvalidFieldChecker($field),
            ComparisonType::Between => $this->between_checker->getInvalidFieldChecker($field),
            ComparisonType::In => $this->in_checker->getInvalidFieldChecker($field),
            ComparisonType::NotIn => $this->not_in_checker->getInvalidFieldChecker($field)
        };
    }

    public function visitInteger(\Tracker_FormElement_Field_Integer $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitDate(\Tracker_FormElement_Field_Date $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitFile(\Tracker_FormElement_Field_File $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitString(\Tracker_FormElement_Field_String $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitText(\Tracker_FormElement_Field_Text $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitRadiobutton(\Tracker_FormElement_Field_Radiobutton $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitCheckbox(\Tracker_FormElement_Field_Checkbox $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitMultiSelectbox(\Tracker_FormElement_Field_MultiSelectbox $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitSelectbox(\Tracker_FormElement_Field_Selectbox $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitSubmittedBy(\Tracker_FormElement_Field_SubmittedBy $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitLastModifiedBy(\Tracker_FormElement_Field_LastModifiedBy $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitLastUpdateDate(\Tracker_FormElement_Field_LastUpdateDate $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
    }

    public function visitSubmittedOn(\Tracker_FormElement_Field_SubmittedOn $field): InvalidFieldChecker
    {
        return $this->matchComparisonToFieldChecker($field);
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
