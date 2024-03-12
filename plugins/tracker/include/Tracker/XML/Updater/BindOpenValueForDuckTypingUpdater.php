<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tracker\XML\Updater;

use SimpleXMLElement;
use Tracker_FormElement_Field_OpenList;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingValueByDuckTyping;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;

final class BindOpenValueForDuckTypingUpdater implements UpdateBindOpenValueByDuckTyping
{
    public function __construct(
        private readonly RetrieveMatchingValueByDuckTyping $field_value_matcher,
        private readonly MoveChangesetXMLUpdater $XML_updater,
        private readonly \XML_SimpleXMLCDATAFactory $cdata_factory,
    ) {
    }

    public function updateOpenValueForDuckTypingMove(
        SimpleXMLElement $changeset_xml,
        \Tracker_FormElement_Field_List $source_field,
        \Tracker_FormElement_Field_List $destination_field,
        int $index,
    ): void {
        if (! $changeset_xml->field_change[$index]) {
            return;
        }
        $list_value_ids = $changeset_xml->field_change[$index]->value;
        if ($list_value_ids === null) {
            return;
        }

        $destinations_values_ids = [];
        foreach ($list_value_ids as $value_id) {
            $value                     = (string) $value_id;
            $destination_list_value_id = null;

            if ($value && ! str_starts_with($value[0], Tracker_FormElement_Field_OpenList::BIND_PREFIX)) {
                $destinations_values_ids[$value]["value"]  = (string) $value_id;
                $destinations_values_ids[$value]["format"] = "label";
            } else {
                $destination_list_value_id = $this->field_value_matcher->getMatchingValueByDuckTyping(
                    $source_field,
                    $destination_field,
                    (int) str_replace(Tracker_FormElement_Field_OpenList::BIND_PREFIX, '', $value)
                );
            }

            if ($destination_list_value_id === null || $destination_list_value_id === 100 || $destination_list_value_id === 0) {
                continue;
            }

            $destinations_values_ids[$destination_list_value_id]["value"]  = $destination_list_value_id;
            $destinations_values_ids[$destination_list_value_id]["format"] = "id";
        }

        if (empty($destinations_values_ids)) {
            $destinations_values_ids[$destination_field->getDefaultValue()]["value"]  = (int) str_replace(Tracker_FormElement_Field_OpenList::BIND_PREFIX, '', (string) $destination_field->getDefaultValue());
            $destinations_values_ids[$destination_field->getDefaultValue()]["format"] = "id";
        }

        $this->XML_updater->deleteFieldChangeValueNode($changeset_xml, $index);

        foreach ($destinations_values_ids as $values_id) {
            if (isset($values_id["value"]) && isset($values_id["format"])) {
                $this->cdata_factory->insertWithAttributes(
                    $changeset_xml->field_change[$index],
                    "value",
                    (string) $values_id["value"],
                    ['format' => $values_id["format"]]
                );
            }
        }
    }
}
