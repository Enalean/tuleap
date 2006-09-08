<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require(getenv('CODEX_LOCAL_INC'));
require_once('tests/simpletest/unit_tester.php');

//We define a group of test
class DaoGroupTest extends GroupTest {
    function DaoGroupTest($name = 'All Dao tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/include/DataAccessTest.php');
        $this->addTestFile(dirname(__FILE__).'/include/DataAccessObjectTest.php');
        $this->addTestFile(dirname(__FILE__).'/include/DataAccessResultTest.php');
        $this->addTestFile(dirname(__FILE__).'/CodexDataAccessTest.php');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test =& new DaoGroupTest();
    $test->run(new CodexReporter());
 }
?>
