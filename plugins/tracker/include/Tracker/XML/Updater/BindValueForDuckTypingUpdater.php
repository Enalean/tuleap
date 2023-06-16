<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingValueByDuckTyping;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;

final class BindValueForDuckTypingUpdater implements UpdateBindValueByDuckTyping
{
    public function __construct(
        private readonly RetrieveMatchingValueByDuckTyping $field_value_matcher,
        private readonly MoveChangesetXMLUpdater $XML_updater,
        private readonly \XML_SimpleXMLCDATAFactory $cdata_factory,
    ) {
    }

    public function updateValueForDuckTypingMove(
        SimpleXMLElement $changeset_xml,
        \Tracker_FormElement_Field_List $source_field,
        \Tracker_FormElement_Field_List $target_field,
        int $index,
    ): void {
        $list_value_ids = $changeset_xml->field_change[$index]->value;
        if ($list_value_ids === null) {
            return;
        }

        $destinations_values_ids = [];
        foreach ($list_value_ids as $value_id) {
            $destination_list_value_id = $this->field_value_matcher->getMatchingValueByDuckTyping(
                $source_field,
                $target_field,
                (int) $value_id
            );

            if ($destination_list_value_id === null) {
                continue;
            }

            $destinations_values_ids[] = $destination_list_value_id;
        }

        if (empty($destinations_values_ids)) {
            $changeset_xml->field_change[$index]->value = (int) $target_field->getDefaultValue();
            return;
        }

        $this->XML_updater->deleteFieldChangeValueNode($changeset_xml, $index);

        foreach (array_unique($destinations_values_ids) as $value_id) {
            $this->cdata_factory->insertWithAttributes(
                $changeset_xml->field_change[$index],
                "value",
                (string) $value_id,
                ['format' => "id"]
            );
        }
    }
}
