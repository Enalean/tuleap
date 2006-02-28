<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');
require_once('tests/simpletest/unit_tester.php');

//We define a group of test
class TrackerGroupTest extends GroupTest {
    function TrackerGroupTest($name = 'All Tracker tests') {
        $this->GroupTest($name);

        $this->addTestFile(dirname(__FILE__).'/ArtifactRuleValueTest.php');
        $this->addTestFile(dirname(__FILE__).'/ArtifactRuleValueViewTest.php');
        $this->addTestFile(dirname(__FILE__).'/ArtifactRuleFactoryTest.php');
        $this->addTestFile(dirname(__FILE__).'/ArtifactRulesManagerTest.php');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test =& new TrackerGroupTest();
    $test->run(new CodexReporter());
 }
?>
