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
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingValueByDuckTyping;

final class BindValueForSemanticUpdater implements UpdateBindValueForSemantic
{
    public function __construct(private readonly RetrieveMatchingValueByDuckTyping $field_value_matcher,)
    {
    }

    public function updateValueForSemanticMove(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field_List $source_status_field,
        Tracker_FormElement_Field_List $target_status_field,
        int $index,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): void {
        $xml_value = (int) $changeset_xml->field_change[$index]->value;

        if ($xml_value === 0) {
            return;
        }

        $value = $this->field_value_matcher->getMatchingValueByDuckTyping(
            $source_status_field,
            $target_status_field,
            $xml_value
        );

        if ($value === null) {
            $value = $target_status_field->getDefaultValue();
            $feedback_field_collector->addFieldInPartiallyMigrated($source_status_field);
        }

        $changeset_xml->field_change[$index]->value = (int) $value;
    }
}
