<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('../../../codendi_tools/tests/CodexReporter.class.php');
}

require_once('../../../codendi_tools/tests/simpletest/unit_tester.php');
require_once('../../../codendi_tools/tests/simpletest/mock_objects.php');

//We define a group of test
class IMGroupTest extends GroupTest {
    function IMGroupTest($name = 'All IM Plugin tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/IMPluginTest.php');
    }
}
if (CODEX_RUNNER === __FILE__) {
    $test =& new IMGroupTest();
    $test->run(new CodexReporter());
 }
?>
