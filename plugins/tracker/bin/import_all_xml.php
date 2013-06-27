#!/usr/share/codendi/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once dirname(__FILE__) .'/../include/Tracker/TrackerFactory.class.php';

define('ANSI_NOCOLOR', "\033[0m");
define('ANSI_GREEN', "\033[32m");
define('ANSI_YELLOW', "\033[35m");
define('ANSI_RED', "\033[31m");

if ($argc < 2) {
    echo <<< EOT
Usage: $argv[0] project_id filepath.xml
Create all trackers defined in XML file

EOT;
    exit(1);
}

$project = ProjectManager::instance()->getProject($argv[1]);
if ($project && !$project->isError()) {
    $oxml = simplexml_load_file($argv[2]);
    if ($oxml !== false) {
        $tracker_factory = TrackerFactory::instance();
        foreach($oxml->tracker as $tracker_xml) {
            $name        = $tracker_xml->name;
            $description = $tracker_xml->description;
            $item_name   = $tracker_xml->item_name;

            $tracker = $tracker_factory->createFromXML($tracker_xml, $project->getID(), $name, $description, $item_name);
            if ($tracker) {
                echo_success("Tracker $name successfully created");
            } else {
                if ($GLOBALS['Response']->feedbackHasErrors()) {
                    echo $GLOBALS['Response']->getRawFeedback();
                    $GLOBALS['Response']->clearFeedback();
                }
                echo_error("Tracker $name not created");
            }
        }
    } else {
        die("*** ERROR: invalid XML file");
    }
} else {
    die("*** ERROR: invalid project_id\n");
}

function echo_error($msg) {
    echo ANSI_RED.$msg.ANSI_NOCOLOR.PHP_EOL;
}

function echo_success($msg) {
    echo ANSI_GREEN.$msg.ANSI_NOCOLOR.PHP_EOL;
}


?>
