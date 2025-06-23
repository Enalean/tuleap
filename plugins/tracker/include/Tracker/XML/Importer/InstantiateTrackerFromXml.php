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
use TrackerFromXmlImportCannotBeCreatedException;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker;
use XML_ParseException;

interface InstantiateTrackerFromXml
{
    /**
     * @throws TrackerFromXmlException
     * @throws TrackerFromXmlImportCannotBeCreatedException
     * @throws Tracker_Exception
     * @throws XML_ParseException
     */
    public function instantiateTrackerFromXml(
        Project $project,
        SimpleXMLElement $xml_tracker,
        ImportConfig $configuration,
        array $created_trackers_mapping,
        TrackerXMLFieldMappingFromExistingTracker $existing_tracker_field_mapping,
        array &$xml_fields_mapping,
        array &$reports_xml_mapping,
        array &$renderers_xml_mapping,
    ): Tracker;
}
