<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once 'pre.php';

try {
    $tracker = TrackerFactory::instance()->getTrackerById($argv[1]);
    if ($tracker) {
        $xml_import = new Tracker_Artifact_XMLImport(
            new XML_RNGValidator()
        );
        $xml_import->importFromFile($tracker, $argv[2]);
    }
} catch (XML_ParseException $exception) {
    echo $exception->getMessage().PHP_EOL;
    echo $exception->getIndentedXml().PHP_EOL;
    echo implode(PHP_EOL, $exception->getErrors()).PHP_EOL;
}

