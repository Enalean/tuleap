<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');

//We define a group of test
class IncludeGroupTest extends GroupTest {
    function IncludeGroupTest($name = 'All Include tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/SimpleSanitizerTest.php');
        $this->addTestFile(dirname(__FILE__).'/StringTest.php');
        $this->addTestFile(dirname(__FILE__).'/HTTPRequestTest.php');
        $this->addTestFile(dirname(__FILE__).'/UserTest.class');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test =& new IncludeGroupTest();
    $test->run(new CodexReporter());
 }
?>
