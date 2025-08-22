<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field\List;

use Tracker_FormElement_Field_List_BindValue;
use Tracker_FormElement_Field_List_OpenValue;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListValue;
use Tuleap\Color\ColorName;
use Tuleap\Option\Option;
use Tuleap\Tracker\FormElement\Field\ListField;

final readonly class StaticListFieldWithValueBuilder
{
    public function buildStaticListFieldWithValue(ConfiguredField $configured_field, ?\Tracker_Artifact_ChangesetValue_List $changeset_value): StaticListFieldWithValue
    {
        return new StaticListFieldWithValue(
            $configured_field->field->getLabel(),
            $configured_field->display_type,
            array_values(
                array_map(
                    function (Tracker_FormElement_Field_List_BindValue|Tracker_FormElement_Field_List_OpenValue $value) use ($configured_field) {
                        assert($configured_field->field instanceof ListField);
                        return new StaticListValue(
                            $value->getLabel(),
                            Option::fromNullable($this->getColorDecoratorIfSupported($configured_field->field, $value))
                        );
                    },
                    $changeset_value?->getListValues() ?? [],
                )
            )
        );
    }

    private function getColorDecoratorIfSupported(
        ListField $configured_field,
        Tracker_FormElement_Field_List_BindValue|Tracker_FormElement_Field_List_OpenValue $value,
    ): ?ColorName {
        $decorators = $configured_field->getDecorators();
        if (! isset($decorators[$value->getId()])) {
            return null;
        }

        $decorator = $decorators[$value->getId()];
        if ($decorator->isUsingOldPalette()) {
            return null;
        }

        return ColorName::fromName($decorator->getCurrentColor());
    }
}
