<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\XML;

use SimpleXMLElement;
use Tuleap\Tracker\XML\XMLTracker;

final class JiraXMLNodeBuilder
{
    public static function buildProjectSimpleXmlElement(\SimpleXMLElement $tracker_xml): \SimpleXMLElement
    {
        $project_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers></trackers></project>');

        self::appendTrackerXML($project_xml->trackers, $tracker_xml);

        return $project_xml;
    }

    public static function appendTrackerXML(SimpleXMLElement $all_trackers_node, SimpleXMLElement $one_tracker_node): void
    {
        $dom_trackers = dom_import_simplexml($all_trackers_node);
        if (! ($dom_trackers instanceof \DOMElement)) {
            throw new \Exception('Cannot get DOM from trackers XML');
        }
        $dom_tracker = dom_import_simplexml($one_tracker_node);
        if (! ($dom_tracker instanceof \DOMElement)) {
            throw new \Exception('Cannot get DOM from tracker XML');
        }
        if (! $dom_trackers->ownerDocument) {
            throw new \Exception('No ownerDocument node in trackers');
        }

        $dom_tracker = $dom_trackers->ownerDocument->importNode($dom_tracker, true);
        $dom_trackers->appendChild($dom_tracker);
    }

    public static function buildTrackerXMLNode(
        XMLTracker $xml_tracker,
        \SimpleXMLElement $tracker_for_semantic_xml,
        \SimpleXMLElement $tracker_for_reports_xml,
    ): \SimpleXMLElement {
        $xml          = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $trackers_xml = $xml->addChild('trackers');
        assert($trackers_xml instanceof \SimpleXMLElement);
        $node_tracker = $xml_tracker->export($trackers_xml);

        $dom_tracker = dom_import_simplexml($node_tracker);
        if (! ($dom_tracker instanceof \DOMElement)) {
            throw new \RuntimeException('Impossible to create DOMElement from tracker');
        }
        $permissions_tags = $dom_tracker->getElementsByTagName('permissions');
        if (count($permissions_tags) !== 1) {
            throw new \LogicException('There must be only one permission node');
        }
        $dom_tracker->insertBefore(self::getNodeToInsert($tracker_for_semantic_xml, '/tracker/semantics', $dom_tracker), $permissions_tags[0]);
        $dom_tracker->insertBefore(self::getNodeToInsert($tracker_for_reports_xml, '/tracker/reports', $dom_tracker), $permissions_tags[0]);

        return $node_tracker;
    }

    private static function getNodeToInsert(\SimpleXMLElement $xml_tracker, string $path, \DOMElement $dom_tracker): \DOMNode
    {
        $requested_node = $xml_tracker->xpath($path);
        if (! is_array($requested_node)) {
            throw new \LogicException('Request node in path ' . $path . ' not found in tracker XML');
        }

        if (count($requested_node) !== 1) {
            throw new \LogicException('there should be only one ' . $path . ' in tracker XML');
        }
        $dom_node = dom_import_simplexml($requested_node[0]);
        if (! ($dom_node instanceof \DOMElement)) {
            throw new \RuntimeException('Impossible to convert ' . $path . ' node to DOM');
        }
        if (! $dom_tracker->ownerDocument) {
            throw new \RuntimeException('No ownerDocument on DOM tracker');
        }
        return $dom_tracker->ownerDocument->importNode($dom_node, true);
    }
}
