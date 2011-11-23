<?php
//We want to be able to run one test AND many tests
if (! defined('CODENDI_RUNNER')) {
    define('CODENDI_RUNNER', __FILE__);
    require_once('../../../tools/tests/CodendiReporter.class.php');
}

require_once('../../../tools/tests/simpletest/unit_tester.php');
require_once('../../../tools/tests/simpletest/mock_objects.php');

//We define a group of test
class HudsonGroupTest extends GroupTest {
    function HudsonGroupTest($name = 'All Hudson Plugin tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/HudsonJobTest.php');
        $this->addTestFile(dirname(__FILE__).'/HudsonTestResultTest.php');
        $this->addTestFile(dirname(__FILE__).'/HudsonBuildTest.php');
        $this->addTestFile(dirname(__FILE__).'/HudsonJobWidgetTest.php');

    }
}
if (CODENDI_RUNNER === __FILE__) {
    $test =& new HudsonGroupTest();
    $test->run(new CodendiReporter());
 }
?>
