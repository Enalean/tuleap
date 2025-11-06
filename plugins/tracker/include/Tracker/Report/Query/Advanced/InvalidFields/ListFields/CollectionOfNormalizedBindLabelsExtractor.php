<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use Tuleap\Tracker\FormElement\Field\List\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\List\Bind\BindVisitor;
use Tuleap\Tracker\FormElement\Field\List\Bind\ListFieldNullBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\User\ListFieldUserBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\UserGroup\ListFieldUserGroupBind;
use Tuleap\Tracker\FormElement\Field\List\ListField;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;

final readonly class CollectionOfNormalizedBindLabelsExtractor implements BindVisitor, ExtractCollectionOfNormalizedLabels
{
    public function __construct(
        private ListFieldBindValueNormalizer $value_normalizer,
        private UgroupLabelConverter $label_converter,
    ) {
    }

    #[\Override]
    public function extractCollectionOfNormalizedLabels(ListField $field): array
    {
        return (array) $field->getBind()->accept($this, new BindParameters($field));
    }

    #[\Override]
    public function visitListBindStatic(ListFieldStaticBind $bind, BindParameters $parameters)
    {
        $list_values       = $parameters->getField()->getAllValues();
        $list_label_values = [];

        foreach ($list_values as $value) {
            $list_label_values[] = $this->value_normalizer->normalize($value->getLabel());
        }

        return $list_label_values;
    }

    #[\Override]
    public function visitListBindUsers(ListFieldUserBind $bind, BindParameters $parameters)
    {
        $list_values       = $parameters->getField()->getAllValues();
        $list_label_values = [];

        foreach ($list_values as $value) {
            $list_label_values[] = $this->value_normalizer->normalize($value->getUserName());
        }

        return $list_label_values;
    }

    #[\Override]
    public function visitListBindUgroups(ListFieldUserGroupBind $bind, BindParameters $parameters)
    {
        $list_values       = $parameters->getField()->getAllValues();
        $list_label_values = [];

        foreach ($list_values as $value) {
            $value = $value->getLabel();
            if ($this->label_converter->isASupportedDynamicUgroup($value)) {
                $list_label_values[] = $this->label_converter->convertLabelToTranslationKey($value);
            } else {
                $list_label_values[] = $this->value_normalizer->normalize($value);
            }
        }

        return $list_label_values;
    }

    #[\Override]
    public function visitListBindNull(ListFieldNullBind $bind, BindParameters $parameters)
    {
    }
}
