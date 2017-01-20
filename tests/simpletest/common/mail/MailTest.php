<?php
require_once('common/mail/Mail.class.php');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/dao/UserDao.class.php');
Mock::generate('UserDao');
Mock::generatePartial('Mail', 'MailTestVersion', array('_getUserDao'));
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Test the class Mail
 */
class MailTest extends TuleapTestCase {

    function testEncoding() {
        $mail =& new MailTestVersion($this);
        $mail->__construct();
        
        $mail->setSubject("été");
        $this->assertNoPattern("/é/", $mail->getEncodedSubject());
        
        $this->assertEqual($mail->getSubject(), $mail->_decodeHeader($mail->getEncodedSubject()));
        
        $mail->setSubject("è é"); //SR #1167
        $this->assertEqual($mail->getSubject(), $mail->_decodeHeader($mail->getEncodedSubject()));
    }
    
    function testValidateRecipient() {
        $dao =& new MockUserDao($this);
        $dao->expectAt(0, 'searchStatusByEmail', array('exists@A.com'));
        $dao->expectAt(1, 'searchStatusByEmail', array('exists@R.com'));
        $dao->expectAt(2, 'searchStatusByEmail', array('exists@S.com'));
        $dao->expectAt(3, 'searchStatusByEmail', array('exists@P.com'));
        $dao->expectAt(4, 'searchStatusByEmail', array('does@not.exist'));
        
        
        $exists_a = new MockDataAccessResult($this);
        $exists_a->setReturnValue('rowCount', 1);
        $exists_a->setReturnValue('getRow', false);
        $exists_a->setReturnValueAt(0, 'getRow', array('realname' => 'Exists A', 'email' => 'exists@A.com', 'status' => 'A'));
        $dao->setReturnValueAt(0, 'searchStatusByEmail', $exists_a);
        
        $exists_r = new MockDataAccessResult($this);
        $exists_r->setReturnValue('rowCount', 1);
        $exists_r->setReturnValue('getRow', false);
        $exists_r->setReturnValueAt(0, 'getRow', array('realname' => 'Exists R', 'email' => 'exists@R.com', 'status' => 'R'));
        $dao->setReturnValueAt(1, 'searchStatusByEmail', $exists_r);
        
        $exists_s = new MockDataAccessResult($this);
        $exists_s->setReturnValue('rowCount', 1);
        $exists_s->setReturnValue('getRow', false);
        $exists_s->setReturnValueAt(0, 'getRow', array('realname' => 'Exists S', 'email' => 'exists@S.com', 'status' => 'S'));
        $dao->setReturnValueAt(2, 'searchStatusByEmail', $exists_s);
        
        $exists_s = new MockDataAccessResult($this);
        $exists_s->setReturnValue('rowCount', 1);
        $exists_s->setReturnValue('getRow', false);
        $exists_s->setReturnValueAt(0, 'getRow', array('realname' => 'Exists P (S)', 'email' => 'exists@P.com', 'status' => 'S'));
        $exists_s->setReturnValueAt(1, 'getRow', array('realname' => 'Exists P (S)', 'email' => 'exists@P.com', 'status' => 'S'));
        $exists_s->setReturnValueAt(2, 'getRow', array('realname' => 'Exists P (!S)', 'email' => 'exists@P.com', 'status' => 'P'));
        $exists_s->setReturnValueAt(3, 'getRow', array('realname' => 'Exists P2 (!S)', 'email' => 'exists@P.com', 'status' => 'P'));
        $dao->setReturnValueAt(3, 'searchStatusByEmail', $exists_s);
        
        $does_not_exist = new MockDataAccessResult($this);
        $does_not_exist->setReturnValue('rowCount', 0);
        $does_not_exist->setReturnValue('getRow', false);
        $dao->setReturnValueAt(4, 'searchStatusByEmail', $does_not_exist);
        
        $mail =& new MailTestVersion($this);
        $mail->setReturnReference('_getUserDao', $dao);
        $mail->__construct();
        
        $recipients = $mail->_validateRecipient('exists@A.com, exists@R.com ; exists@S.com, exists@P.com, does@not.exist');
        $this->assertEqual($recipients, '"Exists A" <exists@A.com>, "Exists R" <exists@R.com>, "Exists P (!S)" <exists@P.com>, does@not.exist');
    }
    function testValidateRecipientEmpty() {
        $dao =& new MockUserDao($this);
        $dao->expectAt(0, 'searchStatusByEmail', array('exists@1.com'));
        $dao->expectAt(1, 'searchStatusByEmail', array('exists@2.com'));
        $dao->expectAt(2, 'searchStatusByEmail', array('exists@3.com'));
        
        
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
        $mail->__construct();
        
        $recipients = $mail->_validateRecipient('exists@1.com, exists@2.com');
        $this->assertEqual($recipients, '');
    }
}
?>
