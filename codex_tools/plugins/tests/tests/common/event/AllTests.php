<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');

//We define a group of test
class EventGroupTest extends GroupTest {
    function EventGroupTest($name = 'All Event tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/EventManagerTest.php');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test =& new EventGroupTest();
    $test->run(new CodexReporter());
 }
?>
