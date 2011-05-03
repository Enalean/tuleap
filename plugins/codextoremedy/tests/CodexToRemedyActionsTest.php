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
Mock::generate('User');
Mock::generate('UserManager');
require_once('common/mail/Codendi_Mail.class.php');
Mock::generatePartial('Codendi_Mail', 'Codendi_MailTestVersion', array('setFrom','setTo','setSubject','setBodyHtml','send'));
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

require_once(dirname(__FILE__).'/../include/CodexToRemedy.class.php');
Mock::generate('CodexToRemedy');
require_once(dirname(__FILE__).'/../include/CodexToRemedyActions.class.php');
Mock::generatePartial('CodexToRemedyActions', 'CodexToRemedyActionsTestVersion', array('_getUserManager'));
Mock::generatePartial('CodexToRemedyActions', 'CodexToRemedyActionsTestVersion2', array('_getUserManager', 'insertTicketInCodexDB', 'sendMail', 'insertTicketInRIFDB', 'getController', 'validateRequest'));
Mock::generatePartial('CodexToRemedyActions', 'CodexToRemedyActionsTestVersion3', array('_getUserManager', '_getCodendiMail', '_getPlugin', 'validateRequest'));

class CodexToRemedyActionsTest extends UnitTestCase {

    function setUp() {
        $GLOBALS['Language']           = new MockBaseLanguage($this);
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
        $actions = new CodexToRemedyActionsTestVersion();
        $params = $actions->validateRequest($request);
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
        $actions = new CodexToRemedyActionsTestVersion();
        $params = $actions->validateRequest($request);
        $validParams = array('cc' => 'john.doe@example.com');
        $this->assertEqual($params, $validParams);
    }

    function testAddTicketCodexDBFail() {
        $um = new MockUserManager();
        $user = new MockUser();
        $um->setReturnValue('getCurrentUser', $user);
        $actions = new CodexToRemedyActionsTestVersion2();
        $c = new MockCodexToRemedy();
        $actions->setReturnValue('getController', $c);
        $actions->setReturnValue('_getUserManager', $um);
        $actions->setReturnValue('insertTicketInCodexDB', false);
        $actions->expectOnce('insertTicketInCodexDB');
        $actions->expectNever('sendMail');
        $actions->expectNever('insertTicketInRIFDB');
        $c->expectOnce('addData');
        $c->expect('addData', array(array('status' => false)));
        $actions->addTicket();
    }

    function testAddTicketRIFDBFail() {
        $um = new MockUserManager();
        $user = new MockUser();
        $um->setReturnValue('getCurrentUser', $user);
        $actions = new CodexToRemedyActionsTestVersion2();
        $c = new MockCodexToRemedy();
        $actions->setReturnValue('getController', $c);
        $actions->setReturnValue('_getUserManager', $um);
        $actions->setReturnValue('insertTicketInCodexDB', true);
        $actions->setReturnValue('insertTicketInRIFDB', false);
        $actions->expectOnce('insertTicketInCodexDB');
        $actions->expectCallCount('sendMail', 3);
        $actions->expectOnce('insertTicketInRIFDB');
        $c->expectOnce('addData');
        $c->expect('addData', array(array('status' => true)));
        $actions->addTicket();
    }

    function testAddTicketSuccess() {
        $um = new MockUserManager();
        $user = new MockUser();
        $um->setReturnValue('getCurrentUser', $user);
        $actions = new CodexToRemedyActionsTestVersion2();
        $c = new MockCodexToRemedy();
        $actions->setReturnValue('getController', $c);
        $actions->setReturnValue('_getUserManager', $um);
        $actions->setReturnValue('insertTicketInCodexDB', true);
        $actions->setReturnValue('insertTicketInRIFDB', true);
        $actions->expectOnce('insertTicketInCodexDB');
        $actions->expectCallCount('sendMail', 2);
        $actions->expectOnce('insertTicketInRIFDB');
        $c->expectOnce('addData');
        $c->expect('addData', array(array('status' => true)));
        $actions->addTicket();
    }

    function testSendMailToUSER() {
        $um = new MockUserManager();
        $user = new MockUser();
        $um->setReturnValue('getCurrentUser', $user);

        $validParams = array('summary' => 'valid summary',
                             'description' => 'valid description',
                             'type' => 1,
                             'text_type' => 'SUPPORT REQUEST',
                             'severity' => 1,
                             'text_severity' => 'Minor',
                             'cc' => 'john.doe@example.com');

        $actions = new CodexToRemedyActionsTestVersion3();
        $actions->setReturnValue('_getUserManager', $um);
        $mail = new Codendi_MailTestVersion();
        $mail->setReturnValue('send',True);

        $actions->setReturnValue('_getCodendiMail', $mail);
        $this->assertTrue($actions->sendMail($validParams, CodexToRemedyActions::RECEPIENT_USER));
    }

}

?>