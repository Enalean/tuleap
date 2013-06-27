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
Mock::generate('PFUser');
Mock::generate('UserManager');
require_once('common/mail/Codendi_Mail.class.php');
Mock::generate('Codendi_Mail');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/plugin/PluginManager.class.php');
Mock::generate('PluginManager');
require_once('common/include/Properties.class.php');
Mock::generate('Properties');

require_once(dirname(__FILE__).'/../include/RequestHelp.class.php');
Mock::generate('RequestHelp');
require_once(dirname(__FILE__).'/../include/RequestHelpActions.class.php');
Mock::generatePartial('RequestHelpActions', 'RequestHelpActionsTestVersion', array('_getUserManager', '_getPluginProperty'));
Mock::generatePartial('RequestHelpActions', 'RequestHelpActionsTestVersion2', array('_getUserManager', 'insertTicketInCodexDB', 'sendMail', 'insertTicketInRIFDB', 'getController', 'validateRequest'));
Mock::generatePartial('RequestHelpActions', 'RequestHelpActionsTestVersion3', array('_getUserManager', '_getCodendiMail', '_getPluginManager', 'validateRequest'));

class RequestHelpActionsTest extends UnitTestCase {

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'default description', array('plugin_requesthelp', 'requesthelp_default_description'));
    }

    function tearDown() {
        unset($GLOBALS['Language']);
    }

    function testValidateRequestValid() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 1, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValue('valid', true);
        $request->expectCallCount('valid', 4);
        $actions = new RequestHelpActionsTestVersion();
        $actions->setReturnValue('_getPluginProperty', 'ASSISTANCE REQUEST', array('support_request'));
        $params = $actions->validateRequest($request);
        $validParams = array('status' => true,
                             'params' => array('summary'       => 'valid summary',
                                               'description'   => 'valid description',
                                               'type'          => 1,
                                               'text_type'     => 'ASSISTANCE REQUEST',
                                               'severity'      => 1,
                                               'text_severity' => 'Minor',
                                               'cc'            => 'john.doe@example.com'),
                             'invalid' => array());
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
        $request->expectCallCount('valid', 4);
        $actions = new RequestHelpActionsTestVersion();
        $params = $actions->validateRequest($request);
        $validParams = array('status' => false,
                             'params' => array('cc' => 'john.doe@example.com'),
                             'invalid' => array('', 'Description', 'Type', ''));
        $this->assertEqual($params, $validParams);
    }

    function testValidateRequestNonValidSummary() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 1, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValueAt(0, 'valid', false);
        $request->setReturnValueAt(1, 'valid', true);
        $request->setReturnValueAt(2, 'valid', true);
        $request->setReturnValueAt(3, 'valid', true);
        $request->expectCallCount('valid', 4);
        $actions = new RequestHelpActionsTestVersion();
        $actions->setReturnValue('_getPluginProperty', 'ASSISTANCE REQUEST', array('support_request'));
        $params = $actions->validateRequest($request);
        $validParams = array('status' => false,
                             'params' => array('description'   => 'valid description',
                                               'type'          => 1,
                                               'text_type'     => 'ASSISTANCE REQUEST',
                                               'severity'      => 1,
                                               'text_severity' => 'Minor',
                                               'cc'            => 'john.doe@example.com'),
                             'invalid' => array(''));
        $this->assertEqual($params, $validParams);
    }

    function testValidateRequestNonValidDescription() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 1, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValueAt(0, 'valid', true);
        $request->setReturnValueAt(1, 'valid', false);
        $request->setReturnValueAt(2, 'valid', true);
        $request->setReturnValueAt(3, 'valid', true);
        $request->expectCallCount('valid', 4);
        $actions = new RequestHelpActionsTestVersion();
        $actions->setReturnValue('_getPluginProperty', 'ASSISTANCE REQUEST', array('support_request'));
        $params = $actions->validateRequest($request);
        $validParams = array('status' => false,
                             'params' => array('summary'       => 'valid summary',
                                               'type'          => 1,
                                               'text_type'     => 'ASSISTANCE REQUEST',
                                               'severity'      => 1,
                                               'text_severity' => 'Minor',
                                               'cc'            => 'john.doe@example.com'),
                             'invalid' => array('Description'));
        $this->assertEqual($params, $validParams);
    }

    function testValidateRequestDefaultDescription() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'default description', array('request_description'));
        $request->setReturnValue('get', 1, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValue('valid', true);
        $request->expectCallCount('valid', 4);
        $actions = new RequestHelpActionsTestVersion();
        $actions->setReturnValue('_getPluginProperty', 'ASSISTANCE REQUEST', array('support_request'));
        $params = $actions->validateRequest($request);
        $validParams = array('status' => false,
                             'params' => array('summary'       => 'valid summary',
                                               'type'          => 1,
                                               'text_type'     => 'ASSISTANCE REQUEST',
                                               'severity'      => 1,
                                               'text_severity' => 'Minor',
                                               'cc'            => 'john.doe@example.com'),
                             'invalid' => array('Description'));
        $this->assertEqual($params, $validParams);
    }

    function testValidateRequestNonValidType() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 1, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValueAt(0, 'valid', true);
        $request->setReturnValueAt(1, 'valid', true);
        $request->setReturnValueAt(2, 'valid', false);
        $request->setReturnValueAt(3, 'valid', true);
        $request->expectCallCount('valid', 4);
        $actions = new RequestHelpActionsTestVersion();
        $params = $actions->validateRequest($request);
        $validParams = array('status' => false,
                             'params' => array('summary'       => 'valid summary',
                                               'description'   => 'valid description',
                                               'severity'      => 1,
                                               'text_severity' => 'Minor',
                                               'cc'            => 'john.doe@example.com'),
                             'invalid' => array('Type'));
        $this->assertEqual($params, $validParams);
    }

    function testValidateRequestBadTypeValue() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 3, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValue('valid', true);
        $request->expectCallCount('valid', 4);
        $actions = new RequestHelpActionsTestVersion();
        $params = $actions->validateRequest($request);
        $validParams = array('status' => false,
                             'params' => array('summary'       => 'valid summary',
                                               'description'   => 'valid description',
                                               'type'          => '3',
                                               'severity'      => 1,
                                               'text_severity' => 'Minor',
                                               'cc'            => 'john.doe@example.com'),
                             'invalid' => array('Type'));
        $this->assertEqual($params, $validParams);
    }

    function testValidateRequestNonValidSeverity() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 1, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValueAt(0, 'valid', true);
        $request->setReturnValueAt(1, 'valid', true);
        $request->setReturnValueAt(2, 'valid', true);
        $request->setReturnValueAt(3, 'valid', false);
        $request->expectCallCount('valid', 4);
        $actions = new RequestHelpActionsTestVersion();
        $actions->setReturnValue('_getPluginProperty', 'ASSISTANCE REQUEST', array('support_request'));
        $params = $actions->validateRequest($request);
        $validParams = array('status' => false,
                             'params' => array('summary'     => 'valid summary',
                                               'description' => 'valid description',
                                               'type'        => 1,
                                               'text_type'   => 'ASSISTANCE REQUEST',
                                               'cc'          => 'john.doe@example.com'),
                             'invalid' => array(''));
        $this->assertEqual($params, $validParams);
    }

    function testValidateRequestBadSeverityValue() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 1, array('type'));
        $request->setReturnValue('get', 4, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValue('valid', true);
        $request->expectCallCount('valid', 4);
        $actions = new RequestHelpActionsTestVersion();
        $actions->setReturnValue('_getPluginProperty', 'ASSISTANCE REQUEST', array('support_request'));
        $params = $actions->validateRequest($request);
        $validParams = array('status' => false,
                             'params' => array('summary'     => 'valid summary',
                                               'description' => 'valid description',
                                               'type'        => 1,
                                               'text_type'   => 'ASSISTANCE REQUEST',
                                               'severity'    => 4,
                                               'cc'          => 'john.doe@example.com'),
                             'invalid' => array(''));
        $this->assertEqual($params, $validParams);
    }

    function testAddTicketCodexDBFail() {
        $um = new MockUserManager();
        $user = mock('PFUser');
        $um->setReturnValue('getCurrentUser', $user);
        $actions = new RequestHelpActionsTestVersion2();
        $c = new MockRequestHelp();
        $actions->setReturnValue('getController', $c);
        $actions->setReturnValue('_getUserManager', $um);
        $params = array('status' => true,
                        'params' => array('summary'       => 'valid summary',
                                          'description'   => 'valid description',
                                          'type'          => 1,
                                          'text_type'     => 'ASSISTANCE REQUEST',
                                          'severity'      => 1,
                                          'text_severity' => 'Minor',
                                          'cc'            => 'john.doe@example.com'));
        $actions->setReturnValue('validateRequest', $params);
        $actions->setReturnValue('insertTicketInCodexDB', false);
        $actions->expectNever('insertTicketInCodexDB');
        $actions->expectNever('sendMail');
        $actions->expectOnce('insertTicketInRIFDB');
        $c->expectOnce('addData');
        $actions->addTicket();
    }

    function testAddTicketRIFDBFail() {
        $um = new MockUserManager();
        $user = mock('PFUser');
        $um->setReturnValue('getCurrentUser', $user);
        $actions = new RequestHelpActionsTestVersion2();
        $c = new MockRequestHelp();
        $actions->setReturnValue('getController', $c);
        $actions->setReturnValue('_getUserManager', $um);
        $params = array('status' => true,
                        'params' => array('summary'       => 'valid summary',
                                          'description'   => 'valid description',
                                          'type'          => 1,
                                          'text_type'     => 'ASSISTANCE REQUEST',
                                          'severity'      => 1,
                                          'text_severity' => 'Minor',
                                          'cc'            => 'john.doe@example.com'));
        $actions->setReturnValue('validateRequest', $params);
        $actions->setReturnValue('insertTicketInCodexDB', true);
        $actions->setReturnValue('insertTicketInRIFDB', false);
        $actions->expectNever('insertTicketInCodexDB');
        $actions->expectNever('sendMail');
        $actions->expectOnce('insertTicketInRIFDB');
        $c->expectOnce('addData');
        $actions->addTicket();
    }

    function testAddTicketSuccess() {
        $um = new MockUserManager();
        $user = mock('PFUser');
        $um->setReturnValue('getCurrentUser', $user);
        $actions = new RequestHelpActionsTestVersion2();
        $c = new MockRequestHelp();
        $actions->setReturnValue('getController', $c);
        $actions->setReturnValue('_getUserManager', $um);
        $params = array('status' => true,
                        'params' => array('summary'       => 'valid summary',
                                          'description'   => 'valid description',
                                          'type'          => 1,
                                          'text_type'     => 'ASSISTANCE REQUEST',
                                          'severity'      => 1,
                                          'text_severity' => 'Minor',
                                          'cc'            => 'john.doe@example.com'));
        $actions->setReturnValue('validateRequest', $params);
        $actions->setReturnValue('insertTicketInCodexDB', true);
        $actions->setReturnValue('insertTicketInRIFDB', true);
        $actions->expectOnce('insertTicketInCodexDB');
        $actions->expectCallCount('sendMail', 1);
        $actions->expectOnce('insertTicketInRIFDB');
        $c->expectOnce('addData');
        $c->expect('addData', array(array('status' => true)));
        $actions->addTicket();
    }

    function testSendMailToSDFailure() {
        $um   = new MockUserManager();
        $user = mock('PFUser');
        $um->setReturnValue('getCurrentUser', $user);

        $validParams = array('ticket_id'     => 'QA0000000000001',
                             'summary'       => 'valid summary',
                             'description'   => 'valid description',
                             'type'          => 1,
                             'text_type'     => 'ASSISTANCE REQUEST',
                             'severity'      => 1,
                             'text_severity' => 'Minor',
                             'cc'            => 'john.doe@example.com');

        $GLOBALS['Language']->setReturnValue('getText','Generic subject to SD', array('plugin_requesthelp', 'requesthelp_mail_subject', array('Minor', 'valid summary')));
        $requesthelp_user_mail_content = 'this is fake body';
        $requesthelp_mail_content = 'this is fake body';
        $requesthelp_Failure_mail_content = 'this is fake body';

        $pm = new MockPluginManager();
        $p = new MockProperties();
        $pm->setReturnValue('getPluginByName', $p);
        $p->setReturnValue('getProperty', 'jenny.doe@example.com');
        $actions = new RequestHelpActionsTestVersion3();
        $actions->setReturnValue('_getUserManager', $um);
        $actions->setReturnValue('_getPluginManager',$pm);
        $mail = new MockCodendi_Mail();
        $mail->expect('setTo', array('jenny.doe@example.com'));
        $mail->expect('setSubject', array('Generic subject to SD'));
        $mail->expectOnce('setBodyHtml');
        $mail->setReturnValue('send', false);

        $actions->setReturnValue('_getCodendiMail', $mail);
        $this->assertFalse($actions->sendMail($validParams));
    }

}

?>