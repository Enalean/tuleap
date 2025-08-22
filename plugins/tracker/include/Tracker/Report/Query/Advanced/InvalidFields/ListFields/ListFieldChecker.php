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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;

final readonly class ListFieldChecker
{
    public function __construct(
        private ListFieldBindValueNormalizer $value_normalizer,
        private ExtractCollectionOfNormalizedLabels $bind_labels_extractor,
        private UgroupLabelConverter $label_converter,
    ) {
    }

    /**
     * @throws FieldIsNotSupportedForComparisonException
     * @throws ListToEmptyStringTermException
     * @throws ListToMySelfForAnonymousComparisonException
     * @throws ListToNowComparisonException
     * @throws ListToStatusOpenComparisonException
     * @throws ListValueDoNotExistComparisonException
     */
    public function checkFieldIsValidForComparison(Comparison $comparison, \Tuleap\Tracker\FormElement\Field\ListField $field): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->checkListValueIsValid($comparison, $field, false),
            ComparisonType::In,
            ComparisonType::NotIn    => $this->checkListValueIsValid($comparison, $field, true),
            default                  => throw new FieldIsNotSupportedForComparisonException($field, $comparison->getType()->value),
        };
    }

    /**
     * @throws ListToEmptyStringTermException
     * @throws ListToMySelfForAnonymousComparisonException
     * @throws ListToNowComparisonException
     * @throws ListToStatusOpenComparisonException
     * @throws ListValueDoNotExistComparisonException
     */
    private function checkListValueIsValid(
        Comparison $comparison,
        \Tuleap\Tracker\FormElement\Field\ListField $field,
        bool $is_empty_string_a_problem,
    ): void {
        $values_extractor  = new CollectionOfListValuesExtractor();
        $values            = $values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);
        $normalized_labels = $this->bind_labels_extractor->extractCollectionOfNormalizedLabels($field);

        /** @var ListValueDoNotExistComparisonException[] $exceptions */
        $exceptions = [];
        foreach ($values as $value) {
            if ($is_empty_string_a_problem && $value === '') {
                throw new ListToEmptyStringTermException($comparison, $field);
            }

            if ($this->label_converter->isASupportedDynamicUgroup($value)) {
                $value = $this->label_converter->convertLabelToTranslationKey($value);
            }
            $normalized_value = $this->value_normalizer->normalize((string) $value);

            if ($value !== '' && ! in_array($normalized_value, $normalized_labels, true)) {
                $exceptions[] = new ListValueDoNotExistComparisonException($field, (string) $value);
            }
        }

        if (count($exceptions) > 0 && count($exceptions) === count($values)) {
            throw $exceptions[0];
        }
    }
}
