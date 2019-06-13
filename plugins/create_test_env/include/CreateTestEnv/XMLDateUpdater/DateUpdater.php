<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\CreateTestEnv\XMLDateUpdater;

use DateTime;
use SimpleXMLElement;

class DateUpdater
{
    /**
     * @var int
     */
    private $timestamp_diff;

    public function __construct(\DateTimeImmutable $old_reference, \DateTimeImmutable $new_reference)
    {
        $this->timestamp_diff = $new_reference->getTimestamp() - $old_reference->getTimestamp();
    }

    public function updateDateValuesInXML(SimpleXMLElement $xml)
    {
        $this->parseTrackerNodes($xml->trackers);
    }

    private function parseTrackerNodes(SimpleXMLElement $trackers_xml)
    {
        foreach ($trackers_xml->tracker as $tracker_xml) {
            $this->parseFormElementNodes($tracker_xml->formElements);
            $this->parseArtifactNodes($tracker_xml->artifacts);
        }
    }

    private function parseFormElementNodes(SimpleXMLElement $form_elements_node)
    {
        foreach ($form_elements_node->formElement as $form_element_node) {
            if (isset($form_element_node->formElements)) {
                $this->parseFormElementNodes($form_element_node->formElements);
            }

            if ((string) $form_element_node['type'] !== 'date') {
                continue;
            }

            if ((string) $form_element_node->properties['default_value'] === 'today') {
                continue;
            }

            $default_field_timestamp = (int) $form_element_node->properties['default_value'];
            $new_timestamp = $this->convertTimestamp($default_field_timestamp);

            $form_element_node->properties['default_value'] = (int) $new_timestamp;
        }
    }

    private function parseArtifactNodes(SimpleXMLElement $artifacts_xml)
    {
        foreach ($artifacts_xml->artifact as $artifact_xml) {
            $this->parseChangesetNodes($artifact_xml);
        }
    }

    private function parseChangesetNodes(SimpleXMLElement $artifact_xml)
    {
        foreach ($artifact_xml->changeset as $changeset_xml) {
            $this->updateXMLNodeDateValue($changeset_xml->submitted_on);
            $this->parseFieldChangeNodes($changeset_xml);
            $this->parseCommentNodes($changeset_xml->comments);
        }
    }

    private function parseFieldChangeNodes(SimpleXMLElement $changeset_xml)
    {
        foreach ($changeset_xml->field_change as $field_change_node) {
            if ((string) $field_change_node['type'] !== 'date') {
                continue;
            }

            $this->updateXMLNodeDateValue($field_change_node->value);
        }
    }

    private function parseCommentNodes(SimpleXMLElement $comments_node)
    {
        foreach ($comments_node->comment as $comment_xml) {
            $this->updateXMLNodeDateValue($comment_xml->submitted_on);
        }
    }

    private function updateXMLNodeDateValue(SimpleXMLElement $node)
    {
        $date_iso = (string) $node;
        $date_time = new DateTime($date_iso);

        $new_timestamp = $this->convertTimestamp($date_time->getTimestamp());
        $new_iso_date = date('c', $new_timestamp);

        $node[0] = (string) $new_iso_date;
    }

    private function convertTimestamp($old_timestamp)
    {
        return $old_timestamp + $this->timestamp_diff;
    }
}
