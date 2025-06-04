<?php
/*
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Status\Done;

use SimpleXMLElement;
use Tracker_FormElement_Field_List_Value;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;

class SemanticDoneValueChecker
{
    /**
     * @return bool
     */
    public function isValueAPossibleDoneValue(
        Tracker_FormElement_Field_List_Value $value,
        TrackerSemanticStatus $semantic_status,
    ) {
        return $this->isValueNotOpenAndVisible($value, $semantic_status->getOpenValues());
    }

    /**
     * @return bool
     */
    public function isValueAPossibleDoneValueInXMLImport(
        Tracker_FormElement_Field_List_Value $value,
        SimpleXMLElement $xml_semantic_status,
    ) {
        return $this->isValueNotOpenAndVisible($value, $this->getOpenValuesFromXMLSemanticStatus($xml_semantic_status));
    }

    /**
     * @return bool
     */
    private function isValueNotOpenAndVisible(Tracker_FormElement_Field_List_Value $value, array $open_values)
    {
        return ! in_array($value->getId(), $open_values) && ! $value->isHidden();
    }

    /**
     * @return array
     */
    private function getOpenValuesFromXMLSemanticStatus(SimpleXMLElement $xml_semantic_status)
    {
        $open_values_from_xml = [];

        foreach ($xml_semantic_status->open_values->open_value as $xml_open_value) {
            $open_values_from_xml[] = (string) $xml_open_value['REF'];
        }

        return $open_values_from_xml;
    }
}
