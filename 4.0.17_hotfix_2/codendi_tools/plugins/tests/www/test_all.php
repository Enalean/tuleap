<?php


ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once('./tests_utils.php');
require_once('CodendiReporter.class.php');

function add_test_to_group($test, $categ, $params) {
    if (is_array($test)) {
        if ($categ != '_tests') {
            $g = new TestSuite($categ .' Results');
            foreach($test as $c => $t) {
                add_test_to_group($t, $c, array('group' => &$g, 'path' => $params['path']."/$categ/"));
            }
            $params['group']->addTestCase($g);
        } else {
            foreach($test as $t) {
                $params['group']->addTestFile($params['path'] . '/' . $t);
            }
        }
    } else if ($test) {
        $params['group']->addTestFile($params['path'] . $categ);
    }
}
/**/

$g = get_group_tests($GLOBALS['tests']);

$j_reporter = CodendiReporterFactory::reporter('junit_xml', true);
$g->run($j_reporter);

$xml = $j_reporter->writeXML('codendi_unit_tests_report.xml');

$j_reporter->generateCoverage('clover.xml');

?>