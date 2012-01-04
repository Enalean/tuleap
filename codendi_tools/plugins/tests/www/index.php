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

$coverCode = isset($_REQUEST['cover_code']) ? true  : false;

?>
<html>
    <head>
        <title>Unit Tests - Tuleap</title>
        <style type="text/css">
        body {
            margin:0;
            padding:0;
        }
        body, th, td {
            font-family: Verdana, Arial, sans-serif;
            font-size:10pt;
        }
        #menu,
        #menu ul {
            list-style-type:none;
            padding-left:0px;
        }
        #menu ul li {
            padding-left:40px;
        }
        #menu ul li.categ {
            padding-left:24px;
        }
        #submit_panel {
            text-align:center;
        }
        #submit_panel input {
            width:96px;
            font-size:2em;
            height:100%;
            border:1px solid #ccc;
            background-image:url('bg.png');
            color:transparent;
        }
        #submit_panel input:hover {
            background-image:url('bg-hover.png');
        }
        tr {
            vertical-align:top;
        }
        .fail { 
            color: red; 
        } 
        .pass { 
            color: green; 
        } 
        pre { 
            background-color: lightgray; 
        }
        a img {
            border:none;
        }
        </style>
        <script type="text/javascript" src="/scripts/prototype/prototype.js"></script>
        <script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>
        <script type="text/javascript">
        function uncheck(element) {
            if (element.id != 'menu') {
                var len = element.childNodes.length;
                var found = false;
                for (var i = 0 ; i < len && !found; ++i) {
                    if (element.childNodes[i].tagName == 'INPUT' && element.childNodes[i]['type'] == 'checkbox') {
                        element.childNodes[i].checked = false;
                        found = true;
                    }
                }
                uncheck(element.parentNode);
            }
        }
        function register_events(element) {
            if (element.childNodes) {
                $A(element.childNodes).each(function (child) {
                    var found = false;
                    if (child.tagName == 'INPUT' && child['type'] == 'checkbox') {
                        Event.observe(child, 'change', (function (evt) {
                            var checked = this.checked;
                            var col = this.parentNode.getElementsByTagName('input');
                            var len = col.length;
                            for (var i = 0 ; i < len ; ++i) {
                                if (col[i]['type'] == 'checkbox') {
                                    col[i].checked = checked;
                                }
                            }
                            //On remonte
                            if (!checked && this.parentNode.id != 'menu') {
                                uncheck(this.parentNode.parentNode.parentNode);
                            }
                        }).bind(child));
                        found = true;
                    } else {
                        register_events(child);
                    }
                });
            }
        }
        function init() {
            var plus = 0;
            $$('li.categ').each(function (element) {
                    register_events(element);
                    plus++;
                    new Insertion.Top(element, '<a href="" id="plus_' + plus +'"><img src="minus.png" /></a>');
                    var uls = $A(element.childNodes).findAll(function (element) {
                            return element.tagName == 'UL';
                    });
                    var matchPlus = new RegExp("plus.png$");
                    Event.observe($('plus_'+plus), 'click', function (evt) {
                            uls.each(function (element) {
                                    Element.toggle(element);
                            });
                            if (Event.element(evt).src.match(matchPlus)) {
                                Event.element(evt).src = 'minus.png';
                            } else {
                                Event.element(evt).src = 'plus.png';
                            }
                            Event.stop(evt);
                            return false;
                    });
            });
        }
        Event.observe(window, 'load', init, true);
        </script>
    </head>
    <body>
        <form action="" method="POST">
            <table width="100%">
                <tr>
                    <td width="10%" nowrap="nowrap">
                        <fieldset>
                            <legend>Options</legend>
                            <input type="checkbox" id="show_pass" name="show_pass" value="1" <?= isset($_REQUEST['show_pass']) ? 'checked="checked"' : '' ?> /><label for="show_pass">Show pass</label>
                            <input type="checkbox" id="cover_code" name="cover_code" value="1" <?= $coverCode ? 'checked="checked"' : '' ?> /><label for="cover_code">Code coverage</label>
                            <br />
                            Order: 
                            <input type="radio" id="order_normal" name="order" value="normal" <?= empty($_REQUEST['order']) || $_REQUEST['order'] == 'normal' ? 'checked="checked"' : '' ?> /><label for="order_normal">Normal</label>
                            <input type="radio" id="order_random" name="order" value="random" <?= !empty($_REQUEST['order']) &&  $_REQUEST['order'] == 'random' ? 'checked="checked"' : '' ?> /><label for="order_random">Random</label>
                            <input type="radio" id="order_invert" name="order" value="invert" <?= !empty($_REQUEST['order']) &&  $_REQUEST['order'] == 'invert' ? 'checked="checked"' : '' ?> /><label for="order_invert">Revert</label>
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
                    </form>
                </td>
                    <td width="5%">
                        <div id="submit_panel"><input type="submit" value="Run !" /></div>
                    </td>
                <td width="90%">
                        <script>
                        var inp = $('submit_panel').down('input');
                        if (inp) {
                            var hei = $('submit_panel').up('td').offsetHeight;
                            document.observe('mouseover', function() {
                                if (inp.offsetHeight != hei) {
                                    inp.setStyle({
                                        height:hei + 'px',
                                    });
                                }
                            });
                        }
                        </script>
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
                                        $params['group']->addTestCase($g);
                                    } else if ($test) {
                                        $random[] = $params['path'] . $categ;
                                        $params['group']->addTestFile($params['path'] . $categ);
                                    }
                                }
                            }

                            $reporter = CodendiReporterFactory::reporter('html', $coverCode);

                            $g = get_group_tests($_REQUEST['tests_to_run']);
                            if (isset($_REQUEST['order']) && $_REQUEST['order'] != 'normal') {
                                if ($_REQUEST['order'] == 'random') {
                                    shuffle($random);
                                    $g = new TestSuite("All Tests (random order)");
                                } else if ($_REQUEST['order'] == 'invert') {
                                    rsort($random);
                                    $g = new TestSuite("All Tests (invert order)");
                                }
                                foreach($random as $file) {
                                    $g->addTestFile($file);
                                }
                            }
                            $g->run($reporter);

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
    </body>
</html>
