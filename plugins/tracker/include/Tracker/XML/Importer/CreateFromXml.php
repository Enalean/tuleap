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

namespace Tuleap\Tracker\Tracker\XML\Importer;

use Project;
use SimpleXMLElement;
use Tracker_Exception;
use TrackerFromXmlException;
use Tuleap\Tracker\Tracker;
use XML_ParseException;

interface CreateFromXml
{
    /**
     * First, creates a new Tracker Object by importing its structure from an XML file,
     * then, imports it into the Database, before verifying the consistency
     *
     * @throws TrackerFromXmlException
     * @throws Tracker_Exception
     * @throws XML_ParseException
     * @throws \Tuleap\Tracker\TrackerIsInvalidException
     */
    public function createFromXML(
        SimpleXMLElement $xml_element,
        Project $project,
        string $name,
        string $description,
        string $itemname,
        ?string $color,
        array $created_trackers_mapping,
        array &$xml_fields_mapping,
        array &$reports_xml_mapping,
        array &$renderers_xml_mapping,
    ): Tracker;
}
