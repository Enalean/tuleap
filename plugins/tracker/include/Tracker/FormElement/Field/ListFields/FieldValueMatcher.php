<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields;

use SimpleXMLElement;
use Tracker_FormElement_Field_List;
use User\XML\Import\IFindUserFromXMLReference;

class FieldValueMatcher implements RetrieveMatchingBindValueByDuckTyping, RetrieveMatchingValueByDuckTyping
{
    public function __construct(private readonly IFindUserFromXMLReference $user_finder)
    {
    }

    public function getMatchingValueByDuckTyping(
        Tracker_FormElement_Field_List $source_field,
        Tracker_FormElement_Field_List $target_field,
        int $source_value_id,
    ): ?int {
        if (! $source_value_id || $source_value_id === Tracker_FormElement_Field_List::NONE_VALUE) {
            return Tracker_FormElement_Field_List::NONE_VALUE;
        }

        $source_bind = $source_field->getBind();
        if ($source_bind === null) {
            return null;
        }
        $source_value = $source_bind->getValue($source_value_id);
        if ($source_value === null || is_array($source_value)) {
            return null;
        }
        $target_value = $this->getMatchingBindValueByDuckTyping($source_value, $target_field);
        return ($target_value !== null) ? $target_value->getId() : null;
    }

    public function getMatchingBindValueByDuckTyping(
        \Tracker_FormElement_Field_List_BindValue $source_value,
        \Tracker_FormElement_Field_List $target_field,
    ): ?\Tracker_FormElement_Field_List_BindValue {
        $source_value_label = strtolower($source_value->getLabel());
        foreach ($target_field->getBind()->getAllValues() as $target_value) {
            if ($source_value_label === strtolower($target_value->getLabel())) {
                return $target_value;
            }
        }
        return null;
    }

    public function isSourceUserValueMatchingATargetUserValue(Tracker_FormElement_Field_List $target_contributor_field, SimpleXMLElement $value): bool
    {
        $user = $this->user_finder->getUser($value);

        return ! $user->isAnonymous() && $target_contributor_field->checkValueExists((string) $user->getId());
    }
}
