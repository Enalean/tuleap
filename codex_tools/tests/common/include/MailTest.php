<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}


require_once('tests/simpletest/unit_tester.php');
require_once('common/include/Mail.class');

require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/dao/include/DataAccessResult.class');
Mock::generate('DataAccessResult');
require_once('common/dao/UserDao.class');
Mock::generate('UserDao');
Mock::generatePartial('Mail', 'MailTestVersion', array('_getUserDao'));
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Test the class Mail
 */
class MailTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function MailTest() {
        $this->UnitTestCase('Mail test');
    }

    function testEncoding() {
        $mail =& new MailTestVersion($this);
        $mail->Mail();
        
        $mail->setSubject("été");
        $this->assertNoUnwantedPattern("/é/", $mail->getEncodedSubject());
        
        $this->assertEqual($mail->getSubject(), $mail->_decodeHeader($mail->getEncodedSubject()));
    }
    
    function testValidateRecipient() {
        $dao =& new MockUserDao($this);
        $dao->expectArgumentsAt(0, 'searchStatusByEmail', array('exists@A.com'));
        $dao->expectArgumentsAt(1, 'searchStatusByEmail', array('exists@R.com'));
        $dao->expectArgumentsAt(2, 'searchStatusByEmail', array('exists@S.com'));
        $dao->expectArgumentsAt(3, 'searchStatusByEmail', array('does@not.exist'));
        
        
        $exists_a = new MockDataAccessResult($this);
        $exists_a->setReturnValue('rowCount', 1);
        $exists_a->setReturnValue('getRow', false);
        $exists_a->setReturnValueAt(0, 'getRow', array('email' => 'exists@A.com', 'status' => 'A'));
        $dao->setReturnValueAt(0, 'searchStatusByEmail', $exists_a);
        
        $exists_r = new MockDataAccessResult($this);
        $exists_r->setReturnValue('rowCount', 1);
        $exists_r->setReturnValue('getRow', false);
        $exists_r->setReturnValueAt(0, 'getRow', array('email' => 'exists@R.com', 'status' => 'R'));
        $dao->setReturnValueAt(1, 'searchStatusByEmail', $exists_r);
        
        $exists_s = new MockDataAccessResult($this);
        $exists_s->setReturnValue('rowCount', 1);
        $exists_s->setReturnValue('getRow', false);
        $exists_s->setReturnValueAt(0, 'getRow', array('email' => 'exists@S.com', 'status' => 'S'));
        $dao->setReturnValueAt(2, 'searchStatusByEmail', $exists_s);
        
        $does_not_exist = new MockDataAccessResult($this);
        $does_not_exist->setReturnValue('rowCount', 0);
        $does_not_exist->setReturnValue('getRow', false);
        $dao->setReturnValueAt(3, 'searchStatusByEmail', $does_not_exist);
        
        $mail =& new MailTestVersion($this);
        $mail->setReturnReference('_getUserDao', $dao);
        $mail->Mail();
        
        $recipients = $mail->_validateRecipient('exists@A.com, exists@R.com ; exists@S.com, does@not.exist');
        $this->assertEqual($recipients, 'exists@A.com, exists@R.com, does@not.exist');
        
        $dao->tally();
    }
    function testValidateRecipientEmpty() {
        $dao =& new MockUserDao($this);
        $dao->expectArgumentsAt(0, 'searchStatusByEmail', array('exists@1.com'));
        $dao->expectArgumentsAt(1, 'searchStatusByEmail', array('exists@2.com'));
        $dao->expectArgumentsAt(2, 'searchStatusByEmail', array('exists@3.com'));
        
        
        $exists_a = new MockDataAccessResult($this);
        $exists_a->setReturnValue('rowCount', 1);
        $exists_a->setReturnValue('getRow', false);
        $exists_a->setReturnValueAt(0, 'getRow', array('email' => 'exists@1.com', 'status' => 'S'));
        $dao->setReturnValueAt(0, 'searchStatusByEmail', $exists_a);
        
        $exists_r = new MockDataAccessResult($this);
        $exists_r->setReturnValue('rowCount', 1);
        $exists_r->setReturnValue('getRow', false);
        $exists_r->setReturnValueAt(0, 'getRow', array('email' => 'exists@2.com', 'status' => 'S'));
        $dao->setReturnValueAt(1, 'searchStatusByEmail', $exists_r);
        
        $mail =& new MailTestVersion($this);
        $mail->setReturnReference('_getUserDao', $dao);
        $mail->Mail();
        
        $recipients = $mail->_validateRecipient('exists@1.com, exists@2.com');
        $this->assertEqual($recipients, '');
        
        $dao->tally();
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new MailTest();
    $test->run(new CodexReporter());
 }
?>
