<?php


ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once('./tests_utils.php');
require_once('CodendiReporter.class.php');

// Usage:
//  -t            Use the Text reporter instead of default junit+coverage
//  -d            Ouptut the execution order of this script in a file 
//  -i            Invert the order of execution
//  -r            Reverse order
//  -f <filename> The php script which contains the execution order. One of the output of this script if -d is given.
//
$options = getopt('dirtf:');

$random = array();
if (!empty($options['f']) && is_file($options['f'])) {
    include $options['f'];
    $g = new TestSuite("All Tests (specified order)");
} else {
    function add_test_to_group($test, $categ, $params) {
        global $random;
        if (is_array($test)) {
            if ($categ != '_tests') {
                $g = new TestSuite($categ .' Results');
                foreach($test as $c => $t) {
                    add_test_to_group($t, $c, array('group' => &$g, 'path' => $params['path']."/$categ/"));
                }
                $params['group']->addTestCase($g);
            } else {
                foreach($test as $t) {
                    $random[] = $params['path'] . '/' . $t;
                    $params['group']->addTestFile($params['path'] . '/' . $t);
                }
            }
        } else if ($test) {
            $random[] = $params['path'] . $categ;
            $params['group']->addTestFile($params['path'] . $categ);
        }
    }
    
    $g = get_group_tests($GLOBALS['tests']);
    if (isset($options['r']) || isset($options['i'])) {
        if (isset($options['r'])) {
            shuffle($random);
            $g = new TestSuite("All Tests (random order)");
        } else if (isset($options['i'])) {
            rsort($random);
            $g = new TestSuite("All Tests (invert order)");
        }
    }
}
/*foreach($random as $file) {
    $g->addTestFile($file);
}*/

if (isset($options['t'])) {
    $reporter = CodendiReporterFactory::reporter('text');
    $g->run($reporter);
} else {
    $j_reporter = CodendiReporterFactory::reporter('junit_xml', true);
    $g->run($j_reporter);
    
    $xml = $j_reporter->writeXML('codendi_unit_tests_report.xml');
    
    $j_reporter->generateCoverage('clover.xml');
}
if (isset($options['d'])) {
    echo "Dumping execution order in unit_tests_order.php...";
    file_put_contents('unit_tests_order.php', '<?php'.PHP_EOL.'$random = '.var_export($random, 1).';'.PHP_EOL.'?>');
    echo "\t[ OK ]\n";
}

?>