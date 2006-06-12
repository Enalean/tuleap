<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('../../../codex_tools/tests/CodexReporter.class');
}

require_once('../../../codex_tools/tests/simpletest/unit_tester.php');
require_once('../../../codex_tools/tests/simpletest/mock_objects.php');

//We define a group of test
class ServerUpdateGroupTest extends GroupTest {
    function ServerUpdateGroupTest($name = 'All ServerUpdate Plugin tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/SVNCommitMetaDataTest.php');
        $this->addTestFile(dirname(__FILE__).'/SVNCommitTest.php');
        $this->addTestFile(dirname(__FILE__).'/SVNUpdateTest.php');
        $this->addTestFile(dirname(__FILE__).'/SVNUpdateFilterTest.php');
    }
}
if (CODEX_RUNNER === __FILE__) {
    $test =& new ServerUpdateGroupTest();
    $test->run(new CodexReporter());
 }
?>
