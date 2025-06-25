<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tracker\XML\Importer;

use SimpleXMLElement;

final class XmlTrackersByPriorityOrderer implements OrderXmlTrackersByPriority
{
    public function getAllXmlTrackersOrderedByPriority(SimpleXMLElement $xml_input): array
    {
        $xml_trackers = [];
        foreach ($xml_input->trackers->tracker as $xml_tracker) {
            $xml_trackers[$this->getXmlTrackerAttribute($xml_tracker, 'id')] = $xml_tracker;
        }

        uasort($xml_trackers, function (SimpleXMLElement $xml_tracker_a, SimpleXMLElement $xml_tracker_b) {
            $is_a_inherited_from_tracker = $this->hasTimeframeSemanticInheritedFromAnotherTracker($xml_tracker_a);
            $is_b_inherited_from_tracker = $this->hasTimeframeSemanticInheritedFromAnotherTracker($xml_tracker_b);

            if ($is_a_inherited_from_tracker === $is_b_inherited_from_tracker) {
                return 0;
            }

            if ($is_a_inherited_from_tracker) {
                return 1;
            }
            return -1;
        });

        return $xml_trackers;
    }

    /**
     * @return String | bool the attribute value in String, False if this attribute does not exist
     */
    private function getXmlTrackerAttribute(SimpleXMLElement $xml_tracker, string $attribute_name): bool|string
    {
        $tracker_attributes = $xml_tracker->attributes();
        if ($tracker_attributes === null) {
            return false;
        }
        if ($tracker_attributes[$attribute_name] === null) {
            return false;
        }

        if (! $tracker_attributes[$attribute_name]) {
            return false;
        }

        return (string) $tracker_attributes[$attribute_name];
    }

    private function hasTimeframeSemanticInheritedFromAnotherTracker(SimpleXMLElement $xml_tracker): bool
    {
        if (! $xml_tracker->semantics) {
            return false;
        }

        $inherited_from_tracker_xml_element = $xml_tracker->semantics->xpath("./semantic[@type='timeframe']/inherited_from_tracker");

        return $inherited_from_tracker_xml_element !== null && (is_array($inherited_from_tracker_xml_element) && count($inherited_from_tracker_xml_element) > 0);
    }
}
