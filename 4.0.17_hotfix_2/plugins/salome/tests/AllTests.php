<?php
//We want to be able to run one test AND many tests
if (! defined('CODENDI_RUNNER')) {
    define('CODENDI_RUNNER', __FILE__);
    require_once('../../../codendi_tools/tests/CodendiReporter.class.php');
}

require_once('../../../codendi_tools/tests/simpletest/unit_tester.php');
require_once('../../../codendi_tools/tests/simpletest/mock_objects.php');

//We define a group of test
class SalomeTMFGroupTest extends GroupTest {
    function SalomeTMFGroupTest($name = 'All SalomeTMF Plugin tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/SalomeTMFPermissionsTest.php');
        $this->addTestFile(dirname(__FILE__).'/SalomeTMFTrackerTest.php');
        $this->addTestFile(dirname(__FILE__).'/SalomeTMFURLManagerTest.php');
    }
}
if (CODENDI_RUNNER === __FILE__) {
    $test =& new SalomeGroupTest();
    $test->run(new CodendiReporter());
 }
?>
