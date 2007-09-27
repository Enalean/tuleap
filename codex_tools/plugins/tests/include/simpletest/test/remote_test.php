<?php
    // $Id: remote_test.php,v 1.6 2006/11/20 23:44:37 lastcraft Exp $
    require_once('../remote.php');
    require_once('../reporter.php');

    // The following URL will depend on your own installation.
    if (isset($_SERVER['SCRIPT_URI'])) {
        $base_uri = $_SERVER['SCRIPT_URI'];
    } elseif (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['PHP_SELF'])) {
        $base_uri = 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    };
    $test_url = str_replace('remote_test.php', 'visual_test.php', $base_uri);

    $test = &new TestSuite('Remote tests');
    $test->addTestCase(new RemoteTestCase($test_url . '?xml=yes', $test_url . '?xml=yes&dry=yes'));
    if (SimpleReporter::inCli()) {
        exit ($test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
?>