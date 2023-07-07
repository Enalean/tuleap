<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\XML;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField;
use Tuleap\Tracker\FormElement\Field\XML\ReadPermission;
use Tuleap\Tracker\XML\XMLTracker;

final class JiraXMLNodeBuilderTest extends TestCase
{
    public function testItAppendsTrackerNode(): void
    {
        $trackers_node = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><trackers></trackers>');
        $tracker_node  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker id="1"></tracker>');

        JiraXMLNodeBuilder::appendTrackerXML($trackers_node, $tracker_node);

        self::assertNotNull($trackers_node->tracker);

        $appended_tracker_node = $trackers_node->tracker[0];
        self::assertNotNull($appended_tracker_node);
        self::assertSame("1", (string) $appended_tracker_node["id"]);
    }

    public function testItBuildsProjectSimpleXmlElement(): void
    {
        $tracker_node = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker id="1"></tracker>');

        $project_node = JiraXMLNodeBuilder::buildProjectSimpleXmlElement($tracker_node);

        self::assertSame("project", $project_node->getName());

        self::assertNotNull($project_node->trackers);
        $built_trackers_node = $project_node->trackers[0];
        self::assertNotNull($built_trackers_node);

        self::assertNotNull($built_trackers_node->tracker);
        $appended_tracker_node = $built_trackers_node->tracker[0];
        self::assertNotNull($appended_tracker_node);
        self::assertSame("1", (string) $appended_tracker_node["id"]);
    }

    public function testItBuildsTrackerXMLNode(): void
    {
        $xml_tracker = (new XMLTracker("1", "test"))->withName("Test tracker");

        $field = XMLStringField::fromTrackerAndName($xml_tracker, "field")
            ->withLabel("field")
            ->withRank(1)
            ->withPermissions(new ReadPermission('UGROUP_ANONYMOUS'));

        $xml_tracker = $xml_tracker->withFormElement($field);

        $tracker_node = JiraXMLNodeBuilder::buildTrackerXMLNode(
            $xml_tracker,
            new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker><semantics></semantics></tracker>'),
            new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker><reports></reports></tracker>'),
        );

        self::assertSame("tracker", $tracker_node->getName());
        self::assertSame("1", (string) $tracker_node['id']);

        self::assertNotNull($tracker_node->name);
        self::assertSame("Test tracker", (string) $tracker_node->name);

        self::assertNotNull($tracker_node->item_name);
        self::assertSame("test", (string) $tracker_node->item_name);

        self::assertNotNull($tracker_node->formElements);
        $built_form_elements_node = $tracker_node->formElements[0];
        self::assertNotNull($built_form_elements_node->formElement);

        self::assertNotNull($tracker_node->semantics);
        self::assertNotNull($tracker_node->reports);
        self::assertNotNull($tracker_node->permissions);
    }
}
