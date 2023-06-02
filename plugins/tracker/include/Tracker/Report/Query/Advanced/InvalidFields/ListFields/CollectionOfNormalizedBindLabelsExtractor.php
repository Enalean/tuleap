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

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Null;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;

final class CollectionOfNormalizedBindLabelsExtractor implements BindVisitor
{
    /**
     * @var ListFieldBindValueNormalizer
     */
    private $value_normalizer;
    /**
     * @var UgroupLabelConverter
     */
    private $label_converter;

    public function __construct(
        ListFieldBindValueNormalizer $value_normalizer,
        UgroupLabelConverter $label_converter,
    ) {
        $this->value_normalizer = $value_normalizer;
        $this->label_converter  = $label_converter;
    }

    /** @return array */
    public function extractCollectionOfNormalizedLabels(Tracker_FormElement_Field_List $field)
    {
        return (array) $field->getBind()->accept($this, new BindParameters($field));
    }

    public function visitListBindStatic(Tracker_FormElement_Field_List_Bind_Static $bind, BindParameters $parameters)
    {
        $list_values       = $parameters->getField()->getAllValues();
        $list_label_values = [];

        foreach ($list_values as $value) {
            $list_label_values[] = $this->value_normalizer->normalize($value->getLabel());
        }

        return $list_label_values;
    }

    public function visitListBindUsers(Tracker_FormElement_Field_List_Bind_Users $bind, BindParameters $parameters)
    {
        $list_values       = $parameters->getField()->getAllValues();
        $list_label_values = [];

        foreach ($list_values as $value) {
            $list_label_values[] = $this->value_normalizer->normalize($value->getUserName());
        }

        return $list_label_values;
    }

    public function visitListBindUgroups(Tracker_FormElement_Field_List_Bind_Ugroups $bind, BindParameters $parameters)
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

    public function visitListBindNull(Tracker_FormElement_Field_List_Bind_Null $bind, BindParameters $parameters)
    {
    }
}
