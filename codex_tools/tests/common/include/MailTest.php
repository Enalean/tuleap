<?php
require_once('tests/simpletest/unit_tester.php');
require_once('common/include/Mail.class');

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
        $mail =& new Mail();
        
        $mail->setSubject("été");
        $this->assertNoUnwantedPattern("/é/", $mail->getEncodedSubject());
        
        $this->assertEqual($mail->getSubject(), $mail->_decodeHeader($mail->getEncodedSubject()));
    }
}

//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', true);
    require_once('tests/CodexReporter.class');	
        	
    $test = &new MailTest();
    $test->run(new CodexReporter());
 }
?>
