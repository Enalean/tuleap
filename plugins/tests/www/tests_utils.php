<?php

require_once 'tests_common.php';

require(getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc');
require($GLOBALS['db_config_file']);

require_once('../include/simpletest/unit_tester.php');
require_once('../include/simpletest/mock_objects.php');
require_once('../include/simpletest/web_tester.php');
require_once('../include/simpletest/expectation.php');
//require_once('../include/TestHelper.class.php');

require_once('CodendiReporter.class.php');
//require_once('TuleapTestCase.class.php');


$GLOBALS['config']['plugins_root'] = $GLOBALS['sys_pluginsroot'];
$GLOBALS['config']['tests_root']   = '/tests/';
$GLOBALS['config']['excludes']     = array('.', '..', '.svn', '.git');
$GLOBALS['config']['suffix']       = 'Test.php';

$GLOBALS['tests']                  = array();

function clean_plugins_root($entry) {
    return substr($entry, strlen($GLOBALS['config']['plugins_root']), -strlen($GLOBALS['config']['tests_root']));
}
function search_tests_rec($dir, &$tab, $entry) {
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (!in_array($file, $GLOBALS['config']['excludes']) && $file[0] !== '_') {
                    if (is_dir("$dir/$file")) {
                        search_tests_rec("$dir/$file", $tab[($entry == 'tests'?'Codendi':$entry)], $file);
                    } else if(substr($file, -strlen($GLOBALS['config']['suffix'])) === $GLOBALS['config']['suffix']) {
                        $tab[($entry == 'tests'?'Codendi':$entry)]['_tests'][] = $file;
                    }
                }
            }
            closedir($dh);
        }
    }
}
function search_tests($entry) {
    search_tests_rec($GLOBALS['config']['plugins_root'] . $entry . $GLOBALS['config']['tests_root'], $GLOBALS['tests'], $entry);
    return $GLOBALS['tests'];
}
$roots = glob($GLOBALS['config']['plugins_root'] .'*'. $GLOBALS['config']['tests_root']);
$roots=array_map('clean_plugins_root', $roots);
$roots=array_map('search_tests', $roots);

//{{{ Tri
function sort_by_key_and_by_value_depending_of_type($a, $b) {
    return strnatcasecmp((is_array($a) ? key($a) : $a), (is_array($b) ? key($b) : $b));
}
function sort_tests(&$entry, $key) {
    if ($key == '_tests') {
        usort($entry, 'strnatcasecmp');
    } else {
        uksort($entry, 'strnatcasecmp');
        array_walk($entry, 'sort_tests');
    }
}
uksort($GLOBALS['tests'], 'strnatcasecmp');
array_walk($GLOBALS['tests'], 'sort_tests');
//}}}

function &get_group_tests($tablo) {
    $g =& new TestSuite("All Tests");
    foreach($tablo as $plugin => $tests) {
        $o =& new TestSuite($plugin .' Tests');
        foreach($tests as $c => $t) {
            add_test_to_group($t, $c, 
                array(
                'group' => &$o, 
                'path' => $GLOBALS['config']['plugins_root'] . ($plugin == 'Codendi' ? 'tests' : $plugin) . $GLOBALS['config']['tests_root']
            ));
        }
        $g->add($o);
    }
    return $g;
}


function display_tests($tests, $categ, $params) {
    $prefixe  = ($params['is_cat'] && $categ !== "_tests") ? $params['prefixe'] .'['. $categ .']' : $params['prefixe'];
    if ($params['is_cat']) {
        if ($categ !== "_tests") {
            echo '<li class="categ">';
            echo '<input type="hidden"   name="'. $prefixe .'[_do_all]" value="0" />';
            echo '<input type="checkbox" name="'. $prefixe .'[_do_all]" value="1" '. ($params['checked'] && isset($params['checked'][$categ]['_do_all']) && $params['checked'][$categ]['_do_all'] ? 'checked="checked"' : '') .' />';
            echo '<b>'. $categ .'</b>';
            echo '<ul>';
        }

        foreach($tests as $c => $t) {
            display_tests($t, $c, array('is_cat' => ($categ !== "_tests"), 'prefixe' => $prefixe, 'checked' => ($params['checked'] && $categ !== "_tests" && isset($params['checked'][$categ]) ? $params['checked'][$categ] : $params['checked'])));
        }

        if ($categ !== "_tests") {
            echo '</ul>';
            echo '</li>';
        }
    } else {
        echo '<li>';
        echo '<input type="hidden"   name="'. $prefixe .'['. $tests .']" value="0" />';
        echo '<input type="checkbox" name="'. $prefixe .'['. $tests .']" value="1" '. ($params['checked'] && isset($params['checked'][$tests]) && $params['checked'][$tests] ? 'checked="checked"' : '') .' />';
        echo $tests;
        echo '</li>';
    }
}
function display_tests_as_javascript($tests, $categ, $params) {
    if ($params['is_cat']) {
        if ($categ !== "_tests") {
            echo "'$categ': {";
        }

        foreach($tests as $c => $t) {
            display_tests_as_javascript($t, $c, array('is_cat' => ($categ !== "_tests")));
        }
        if ($categ !== "_tests") {
            echo '},';
        }
    } else {
        echo "'$tests':true,";
    }
}
?>
