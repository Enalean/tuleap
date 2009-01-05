<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('../../../codex_tools/tests/CodexReporter.class.php');
}

require_once('../../../codex_tools/tests/simpletest/unit_tester.php');
require_once('../../../codex_tools/tests/simpletest/mock_objects.php');

//We define a group of test
class HudsonGroupTest extends GroupTest {
    function HudsonGroupTest($name = 'All Hudson Plugin tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/HudsonJobTest.php');
        $this->addTestFile(dirname(__FILE__).'/HudsonTestResultTest.php');
        $this->addTestFile(dirname(__FILE__).'/HudsonBuildTest.php');

    }
}
if (CODEX_RUNNER === __FILE__) {
    $test =& new HudsonGroupTest();
    $test->run(new CodexReporter());
 }
?>
