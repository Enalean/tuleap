<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/include/HTTPRequest.class.php');
Mock::generate('HTTPRequest');
require_once('common/valid/ValidFactory.class.php');

require_once(dirname(__FILE__).'/../include/CodexToRemedy.class.php');
require_once(dirname(__FILE__).'/../include/CodexToRemedyActions.class.php');

class CodexToRemedyActionsTest extends UnitTestCase {

    function testValidateRequestValid() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 1, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValue('valid', true);
        $params = CodexToRemedyActions::validateRequest($request);
        $validParams = array('summary' => 'valid summary',
                             'description' => 'valid description',
                             'type' => 1,
                             'text_type' => 'SUPPORT REQUEST',
                             'severity' => 1,
                             'text_severity' => 'Minor',
                             'cc' => 'john.doe@example.com');
        $this->assertEqual($params, $validParams);
    }

    function testValidateRequestNonValid() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 1, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValue('valid', false);
        $params = CodexToRemedyActions::validateRequest($request);
        $validParams = array('cc' => 'john.doe@example.com');
        $this->assertEqual($params, $validParams);
    }

}

?>