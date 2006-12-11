<?php

set_include_path(get_include_path() .PATH_SEPARATOR. dirname(__FILE__).'/../../../../src' .PATH_SEPARATOR. dirname(__FILE__).'/../../../../src/www/include');

require_once('utils.php');


function add_test_to_group($test, $categ, $params) {
    if (is_array($test)) {
        if ($categ != '_tests') {
            $g =& new GroupTest($categ .' Results');
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
$g =& get_group_tests($GLOBALS['tests']);
exit($g->run(CodeXReporterFactory::reporter("text")) ? 0 : 1);
?>