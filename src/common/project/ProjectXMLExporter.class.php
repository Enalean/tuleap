<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'Project.class.php';

/** This class export a project to xml format */
class ProjectXMLExporter {

    /** @var EventManager */
    private $event_manager;

    public function __construct(EventManager $event_manager) {
        $this->event_manager = $event_manager;
    }

    /**
     * @return SimpleXMLElement
     */
    public function export(Project $project, SimpleXMLElement $into_xml) {
        $this->event_manager->processEvent(
            Event::EXPORT_XML_PROJECT,
            array(
                'project'  => $project,
                'into_xml' => $into_xml
            )
        );
    }
}
?>
