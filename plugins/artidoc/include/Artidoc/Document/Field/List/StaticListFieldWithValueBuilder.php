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

final readonly class StaticListFieldWithValueBuilder implements BuildStaticListFieldWithValue
{
    public function buildStaticListFieldWithValue(ConfiguredField $configured_field, \Tracker_Artifact_ChangesetValue_List $changeset_value): StaticListFieldWithValue
    {
        assert($configured_field->field instanceof \Tracker_FormElement_Field_List);

        return new StaticListFieldWithValue(
            $configured_field->field->getLabel(),
            $configured_field->display_type,
            array_values(
                array_map(
                    function (Tracker_FormElement_Field_List_BindValue|Tracker_FormElement_Field_List_OpenValue $value) use ($configured_field) {
                        $decorators = $configured_field->field->getDecorators();

                        return new StaticListValue(
                            $value->getLabel(),
                            isset($decorators[$value->getId()]) ? ColorName::fromName($decorators[$value->getId()]->getCurrentColor()) : null,
                        );
                    },
                    $changeset_value->getListValues()
                )
            )
        );
    }
}
