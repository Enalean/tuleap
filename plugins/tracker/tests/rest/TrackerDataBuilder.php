<?php
/**
 * Copyright (c) Enalean, 2014- 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'common/autoload.php';

class TrackerDataBuilder extends REST_TestDataBuilder {

    const XML_PROJECT_ID_SHORT_NAME = 'rest-xml-api';

    public function __construct() {
        parent::__construct();

        $this->template_path = dirname(__FILE__).'/_fixtures/';
    }

    public function setUp() {
        echo "Add tracker for XML REST\n";

        $this->setGlobalsForProjectCreation();

        $xml_test = $this->createProject(
            self::XML_PROJECT_ID_SHORT_NAME,
            'RESTXMLAPI',
            true,
            array($this->user_manager->getUserByUserName(self::TEST_USER_1_NAME)),
            array(),
            array()
        );

        $this->importTemplateInProject(
            $xml_test->getID(),
            'Tracker_epic.xml'
        );

        $this->unsetGlobalsForProjectCreation();
    }

}