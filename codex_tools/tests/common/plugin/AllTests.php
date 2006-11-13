<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require(getenv('CODEX_LOCAL_INC'));
require($GLOBALS['db_config_file']);
require_once('tests/simpletest/unit_tester.php');

//We define a group of test
class PluginGroupTest extends GroupTest {
    function PluginGroupTest($name = 'All Plugin tests') {
        $this->GroupTest($name);

        $this->addTestFile(dirname(__FILE__).'/PluginTest.php');
        $this->addTestFile(dirname(__FILE__).'/PluginInfoTest.php');
        $this->addTestFile(dirname(__FILE__).'/PluginFactoryTest.php');
        $this->addTestFile(dirname(__FILE__).'/PluginManagerTest.php');
        $this->addTestFile(dirname(__FILE__).'/PluginDescriptorTest.php');
        $this->addTestFile(dirname(__FILE__).'/PluginHookPriorityManagerTest.php');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test =& new PluginGroupTest();
    $test->run(new CodexReporter());
 }
?>
