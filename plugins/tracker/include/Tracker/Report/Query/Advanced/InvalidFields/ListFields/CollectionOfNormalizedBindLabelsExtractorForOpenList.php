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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use Tuleap\Tracker\FormElement\Field\List\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\List\Bind\BindVisitor;
use Tuleap\Tracker\FormElement\Field\List\Bind\ListFieldNullBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\User\ListFieldUserBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\UserGroup\ListFieldUserGroupBind;
use Tuleap\Tracker\FormElement\Field\List\ListField;
use Tuleap\Tracker\FormElement\Field\List\OpenListValueDao;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;

final readonly class CollectionOfNormalizedBindLabelsExtractorForOpenList implements BindVisitor, ExtractCollectionOfNormalizedLabels
{
    public function __construct(
        private BindVisitor $bind_labels_extractor,
        private OpenListValueDao $open_list_value_dao,
        private ListFieldBindValueNormalizer $value_normalizer,
    ) {
    }

    #[\Override]
    public function extractCollectionOfNormalizedLabels(ListField $field): array
    {
        return $field->getBind()->accept($this, new BindParameters($field));
    }

    #[\Override]
    public function visitListBindStatic(ListFieldStaticBind $bind, BindParameters $parameters): array
    {
        return array_merge(
            $this->bind_labels_extractor->visitListBindStatic($bind, $parameters),
            $this->getOpenValuesForField($parameters->getField()->getId()),
        );
    }

    #[\Override]
    public function visitListBindUsers(ListFieldUserBind $bind, BindParameters $parameters): array
    {
        return array_merge(
            $this->bind_labels_extractor->visitListBindUsers($bind, $parameters),
            $this->getOpenValuesForField($parameters->getField()->getId()),
        );
    }

    #[\Override]
    public function visitListBindUgroups(ListFieldUserGroupBind $bind, BindParameters $parameters): array
    {
        return $this->bind_labels_extractor->visitListBindUgroups($bind, $parameters);
    }

    #[\Override]
    public function visitListBindNull(ListFieldNullBind $bind, BindParameters $parameters): array
    {
        return [];
    }

    private function getOpenValuesForField(int $field_id): array
    {
        $result = [];
        foreach ($this->open_list_value_dao->searchByFieldId($field_id) as $row_value) {
            $result[] = $this->value_normalizer->normalize($row_value['label']);
        }

        return $result;
    }
}
