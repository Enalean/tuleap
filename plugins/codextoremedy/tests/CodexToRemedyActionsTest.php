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
Mock::generate('Codendi_Mail');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/plugin/PluginManager.class.php');
Mock::generate('PluginManager');
require_once('common/include/Properties.class.php');
Mock::generate('Properties');

require_once(dirname(__FILE__).'/../include/CodexToRemedy.class.php');
Mock::generate('CodexToRemedy');
require_once(dirname(__FILE__).'/../include/CodexToRemedyActions.class.php');
Mock::generatePartial('CodexToRemedyActions', 'CodexToRemedyActionsTestVersion', array('_getUserManager'));
Mock::generatePartial('CodexToRemedyActions', 'CodexToRemedyActionsTestVersion2', array('_getUserManager', 'insertTicketInCodexDB', 'sendMail', 'insertTicketInRIFDB', 'getController', 'validateRequest'));
Mock::generatePartial('CodexToRemedyActions', 'CodexToRemedyActionsTestVersion3', array('_getUserManager', '_getCodendiMail', '_getPluginManager', 'validateRequest'));

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
        $request->expectCallCount('valid', 4);
        $actions = new CodexToRemedyActionsTestVersion();
        $params = $actions->validateRequest($request);
        $validParams = array('summary'       => 'valid summary',
                             'description'   => 'valid description',
                             'type'          => 1,
                             'text_type'     => 'SUPPORT REQUEST',
                             'severity'      => 1,
                             'text_severity' => 'Minor',
                             'cc'            => 'john.doe@example.com');
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
        $request->expectCallCount('valid', 1);
        $actions = new CodexToRemedyActionsTestVersion();
        $params = $actions->validateRequest($request);
        $this->assertFalse($params);
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
        $request->expectCallCount('valid', 2);
        $actions = new CodexToRemedyActionsTestVersion();
        $params = $actions->validateRequest($request);
        $this->assertFalse($params);
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
        $request->expectCallCount('valid', 3);
        $actions = new CodexToRemedyActionsTestVersion();
        $params = $actions->validateRequest($request);
        $this->assertFalse($params);
    }

    function testValidateRequestBadTypeValue() {
        $request = new MockHTTPRequest();
        $request->setReturnValue('get', 'valid summary', array('request_summary'));
        $request->setReturnValue('get', 'valid description', array('request_description'));
        $request->setReturnValue('get', 3, array('type'));
        $request->setReturnValue('get', 1, array('severity'));
        $request->setReturnValue('get', 'john.doe@example.com', array('cc'));
        $request->setReturnValue('valid', true);
        $request->expectCallCount('valid', 3);
        $actions = new CodexToRemedyActionsTestVersion();
        $params = $actions->validateRequest($request);
        $this->assertFalse($params);
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
        $actions = new CodexToRemedyActionsTestVersion();
        $params = $actions->validateRequest($request);
        $this->assertFalse($params);
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
        $actions = new CodexToRemedyActionsTestVersion();
        $params = $actions->validateRequest($request);
        $this->assertFalse($params);
    }

    function testAddTicketCodexDBFail() {
        $um = new MockUserManager();
        $user = new MockUser();
        $um->setReturnValue('getCurrentUser', $user);
        $actions = new CodexToRemedyActionsTestVersion2();
        $c = new MockCodexToRemedy();
        $actions->setReturnValue('getController', $c);
        $actions->setReturnValue('_getUserManager', $um);
        $params = array('summary'       => 'valid summary',
                        'description'   => 'valid description',
                        'type'          => 1,
                        'text_type'     => 'SUPPORT REQUEST',
                        'severity'      => 1,
                        'text_severity' => 'Minor',
                        'cc'            => 'john.doe@example.com');
        $actions->setReturnValue('validateRequest', $params);
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
        $params = array('summary'       => 'valid summary',
                        'description'   => 'valid description',
                        'type'          => 1,
                        'text_type'     => 'SUPPORT REQUEST',
                        'severity'      => 1,
                        'text_severity' => 'Minor',
                        'cc'            => 'john.doe@example.com');
        $actions->setReturnValue('validateRequest', $params);
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
        $params = array('summary'       => 'valid summary',
                        'description'   => 'valid description',
                        'type'          => 1,
                        'text_type'     => 'SUPPORT REQUEST',
                        'severity'      => 1,
                        'text_severity' => 'Minor',
                        'cc'            => 'john.doe@example.com');
        $actions->setReturnValue('validateRequest', $params);
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
        $um   = new MockUserManager();
        $user = new MockUser();
        $user->setReturnValue('getEmail', 'requester@example.com');
        $um->setReturnValue('getCurrentUser', $user);

        $validParams = array('summary'       => 'valid summary',
                             'description'   => 'valid description',
                             'type'          => 1,
                             'text_type'     => 'SUPPORT REQUEST',
                             'severity'      => 1,
                             'text_severity' => 'Minor',
                             'cc'            => 'john.doe@example.com');

        // Create content file
        $content_mail = '<?php '. PHP_EOL;
        $content_mail .= '$codextoremedy_user_mail_content = "this is fake body";'. PHP_EOL;
        $content_mail .= '$codextoremedy_mail_content = "this is fake body";'. PHP_EOL;
        $content_mail .= '$codextoremedy_Failure_mail_content = "this is fake body";'. PHP_EOL;
        $content_mail .='?>'.PHP_EOL;

        $filepath = dirname(__FILE__).'/_fixtures/mail_content.txt';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        file_put_contents($filepath, $content_mail);
        $GLOBALS['Language']->setReturnValue('getContent',$filepath);


        $pm = new MockPluginManager();
        $p = new MockProperties();
        $pm->setReturnValue('getPluginByName', $p);
        $p->setReturnValue('getProperty', 'jenny.doe@example.com');
        $actions = new CodexToRemedyActionsTestVersion3();
        $actions->setReturnValue('_getUserManager', $um);
        $actions->setReturnValue('_getPluginManager',$pm);
        $mail = new MockCodendi_Mail();
        $mail->expect('setTo', array('requester@example.com'));
        $mail->expect('setBodyHtml', array('this is fake body'));
        $mail->setReturnValue('send', true);

        $actions->setReturnValue('_getCodendiMail', $mail);
        $this->assertTrue($actions->sendMail($validParams, CodexToRemedyActions::RECEPIENT_USER));

        unlink(dirname(__FILE__).'/_fixtures/mail_content.txt');
        rmdir(dirname(__FILE__).'/_fixtures');
    }

    function testSendMailToSDFailure() {
        $um   = new MockUserManager();
        $user = new MockUser();
        $um->setReturnValue('getCurrentUser', $user);

        $validParams = array('summary'       => 'valid summary',
                             'description'   => 'valid description',
                             'type'          => 1,
                             'text_type'     => 'SUPPORT REQUEST',
                             'severity'      => 1,
                             'text_severity' => 'Minor',
                             'cc'            => 'john.doe@example.com');

        // Create content file
        $content_mail = '<?php '. PHP_EOL;
        $content_mail .= '$codextoremedy_user_mail_content = "this is fake body";'. PHP_EOL;
        $content_mail .= '$codextoremedy_mail_content = "this is fake body";'. PHP_EOL;
        $content_mail .= '$codextoremedy_Failure_mail_content = "this is fake body";'. PHP_EOL;
         $content_mail .='?>'.PHP_EOL;

        $filepath = dirname(__FILE__).'/_fixtures/mail_content.txt';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        file_put_contents($filepath, $content_mail);
        $GLOBALS['Language']->setReturnValue('getContent',$filepath);
                $codextoremedy_user_mail_content = 'this is fake body';
        $codextoremedy_mail_content = 'this is fake body';
        $codextoremedy_Failure_mail_content = 'this is fake body';

        $pm = new MockPluginManager();
        $p = new MockProperties();
        $pm->setReturnValue('getPluginByName', $p);
        $p->setReturnValue('getProperty', 'jenny.doe@example.com');
        $actions = new CodexToRemedyActionsTestVersion3();
        $actions->setReturnValue('_getUserManager', $um);
        $actions->setReturnValue('_getPluginManager',$pm);
        $mail = new MockCodendi_Mail();
        $mail->expect('setTo', array('jenny.doe@example.com'));
        $mail->expect('setBodyHtml', array('this is fake body'));
        $mail->setReturnValue('send', false);

        $actions->setReturnValue('_getCodendiMail', $mail);
        $this->assertFalse($actions->sendMail($validParams, CodexToRemedyActions::RECEPIENT_SD));

        unlink(dirname(__FILE__).'/_fixtures/mail_content.txt');
        rmdir(dirname(__FILE__).'/_fixtures');
    }

    function testSendMailToSDInsertRifDBFail() {
        $um   = new MockUserManager();
        $user = new MockUser();
        $um->setReturnValue('getCurrentUser', $user);

        $validParams = array('summary'       => 'valid summary',
                             'description'   => 'valid description',
                             'type'          => 1,
                             'text_type'     => 'SUPPORT REQUEST',
                             'severity'      => 1,
                             'text_severity' => 'Minor',
                             'cc'            => 'john.doe@example.com');

        // Create content file
        $content_mail = '<?php '. PHP_EOL;
        $content_mail .= '$codextoremedy_user_mail_content = "this is fake body";'. PHP_EOL;
        $content_mail .= '$codextoremedy_mail_content = "this is fake body";'. PHP_EOL;
        $content_mail .= '$codextoremedy_Failure_mail_content = "this is fake body";'. PHP_EOL;
         $content_mail .='?>'.PHP_EOL;

        $filepath = dirname(__FILE__).'/_fixtures/mail_content.txt';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        file_put_contents($filepath, $content_mail);
        $GLOBALS['Language']->setReturnValue('getContent',$filepath);


        $GLOBALS['Language']->setReturnValue('getText','Failure');
        $pm = new MockPluginManager();
        $p = new MockProperties();
        $pm->setReturnValue('getPluginByName', $p);
        $p->setReturnValue('getProperty', 'jenny.doe@example.com');
        $actions = new CodexToRemedyActionsTestVersion3();
        $actions->setReturnValue('_getUserManager', $um);
        $actions->setReturnValue('_getPluginManager',$pm);
        $mail = new MockCodendi_Mail();
        $mail->expect('setTo', array('jenny.doe@example.com'));
        $mail->expect('setSubject', array('Failure'));
        $mail->expect('setBodyHtml', array('this is fake body'));
        $mail->setReturnValue('send', true);

        $actions->setReturnValue('_getCodendiMail', $mail);
        $this->assertTrue($actions->sendMail($validParams, CodexToRemedyActions::RECEPIENT_FAILURE_SD));

        unlink(dirname(__FILE__).'/_fixtures/mail_content.txt');
        rmdir(dirname(__FILE__).'/_fixtures');
    }

}

?>