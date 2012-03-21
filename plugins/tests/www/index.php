<?php
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'tests_common.php';

require_once dirname(__FILE__).'/../include/TestsPluginRunnerPresenter.class.php';
require_once dirname(__FILE__).'/../include/TestsPluginSuitePresenter.class.php';
require_once dirname(__FILE__).'/../include/TestsPluginRequest.class.php';
require_once dirname(__FILE__).'/../include/mustache/MustacheRenderer.class.php';
require_once dirname(__FILE__).'/../include/TestsPluginRunner.class.php';

$request  = new TestsPluginRequest($_REQUEST);
$request->setDisplay('testsPluginRunnerHTML');

$runner   = new TestsPluginRunner($request);


$runner->runAndDisplay();

?>