<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');

//We define a group of test
class CollectionGroupTest extends GroupTest {
    function CollectionGroupTest($name = 'All Collection tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/SeekableIteratorTest.php');
        $this->addTestFile(dirname(__FILE__).'/ArrayIteratorTest.php');
        $this->addTestFile(dirname(__FILE__).'/CollectionTest.php');
        $this->addTestFile(dirname(__FILE__).'/MapTest.php');
        $this->addTestFile(dirname(__FILE__).'/MultiMapTest.php');
        $this->addTestFile(dirname(__FILE__).'/LinkedListTest.php');
        $this->addTestFile(dirname(__FILE__).'/PrioritizedListTest.php');
        $this->addTestFile(dirname(__FILE__).'/PrioritizedMultiMapTest.php');
    }
}
if (CODEX_RUNNER === __FILE__) {
    $test =& new CollectionGroupTest();
    $test->run(new CodexReporter());
 }
?>
