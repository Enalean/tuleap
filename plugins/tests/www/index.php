<?php

ini_set('display_errors', 'on');
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('./tests_utils.php');

$coverCode = isset($_REQUEST['cover_code']) ? true  : false;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Unit Tests - Tuleap</title>
        <link rel="stylesheet" type="text/css" href="/themes/common/css/style.css" />
        <link rel="stylesheet" type="text/css" href="/plugins/tests/themes/default/css/style.css" />
        <script type="text/javascript" src="/scripts/prototype/prototype.js"></script>
        <script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>
        <script type="text/javascript" src="/plugins/tests/scripts/testsUnitView.js"></script>
    </head>
    <body class="main_body_row" style="background:white">
        <form action="" method="post">
            <table class="testsRunner">
                <tr>
                    <td class="testsControl">
                        <fieldset>
                            <legend>Options</legend>
                            <input type="checkbox" id="show_pass" name="show_pass" value="1" <?= isset($_REQUEST['show_pass']) ? 'checked="checked"' : '' ?> />
                            <label for="show_pass">Show pass</label>
                            <input type="checkbox" id="cover_code" name="cover_code" value="1" <?= $coverCode ? 'checked="checked"' : '' ?> />
                            <label for="cover_code">Code coverage</label>
                            <br />
                            Order: 
                            <input type="radio" id="order_normal" name="order" value="normal" <?= empty($_REQUEST['order']) || $_REQUEST['order'] == 'normal' ? 'checked="checked"' : '' ?> />
                            <label for="order_normal">Normal</label>
                            <input type="radio" id="order_random" name="order" value="random" <?= !empty($_REQUEST['order']) &&  $_REQUEST['order'] == 'random' ? 'checked="checked"' : '' ?> />
                            <label for="order_random">Random</label>
                            <input type="radio" id="order_invert" name="order" value="invert" <?= !empty($_REQUEST['order']) &&  $_REQUEST['order'] == 'invert' ? 'checked="checked"' : '' ?> />
                            <label for="order_invert">Revert</label>
                        </fieldset>
                        <fieldset>
                            <legend>Tests</legend>
                            <ul id="menu">
                            <?php foreach($GLOBALS['tests'] as $c => $t) {
                                display_tests($t, $c, array('is_cat' => true, 'prefixe' => 'tests_to_run', 'checked' => @$_REQUEST['tests_to_run']));
                            } ?>
                            </ul>
                            <script type="text/javascript">
                            //<!--
                            var tests_to_run = {
                            <?php foreach($GLOBALS['tests'] as $c => $t) {
                                display_tests_as_javascript($t, $c, array('is_cat' => true));
                            } ?>
                            };
                            //-->
                            </script>
                        </fieldset>
                </td>
                <td class="testsLauncher">
                    <div id="submit_panel"><input type="submit" value="Run !" /></div>
                </td>
                <td class="testsResult">
                    <fieldset>
                        <legend>Results</legend>
                        <?php
                        flush();
                        if (isset($_REQUEST['tests_to_run'])) {
                            $random = array();
                            function add_test_to_group($test, $categ, $params) {
                                global $random;
                                if ($categ != '_do_all') {
                                    if (is_array($test)) {
                                        $g = new TestSuite($categ .' Results');
                                        foreach($test as $c => $t) {
                                            add_test_to_group($t, $c, array('group' => &$g, 'path' => $params['path']."/$categ/"));
                                        }
                                        $params['group']->add($g);
                                    } else if ($test) {
                                        $random[] = $params['path'] . $categ;
                                        $params['group']->addFile($params['path'] . $categ);
                                    }
                                }
                            }

                            $reporter = CodendiReporterFactory::reporter('html', $coverCode);

                            $testSuite = get_group_tests($_REQUEST['tests_to_run']);
                            if (isset($_REQUEST['order']) && $_REQUEST['order'] != 'normal') {
                                if ($_REQUEST['order'] == 'random') {
                                    shuffle($random);
                                    $testSuite = new TestSuite("All Tests (random order)");
                                } else if ($_REQUEST['order'] == 'invert') {
                                    rsort($random);
                                    $testSuite = new TestSuite("All Tests (invert order)");
                                }
                                foreach($random as $file) {
                                    $testSuite->addTestFile($file);
                                }
                            }
                            $testSuite->run($reporter);
                            if ($reporter->generateCoverage(dirname(__FILE__).'/code-coverage-report')) {
                                echo '<p><a href="code-coverage-report">Code coverage results:</a></p>';
                                echo '<iframe src="code-coverage-report" style="width: 100%; height: 500px;" />';
                            }
                        }
                        ?>
                    </fieldset>
                </td>
            </tr>
        </table>
     </form>
    </body>
</html>
