<?php

use Tuleap\Templating\TemplateCache;

require_once('pre.php');

\Tuleap\Request\RequestInstrumentation::incrementSoap();

define('CODENDI_WS_API_VERSION', file_get_contents(dirname(__FILE__).'/VERSION'));

define('LOG_SOAP_REQUESTS', false);

// Check if we the server is in secure mode or not.
$request = HTTPRequest::instance();
if ($request->isSecure()) {
    $protocol = "https";
} else {
    $protocol = "http";
}

$default_domain = ForgeConfig::get('sys_default_domain');

$uri = $protocol.'://'.$default_domain;

if ($request->exist('wsdl')) {
    header("Location: ".$uri."/soap/codendi.wsdl.php?wsdl");
    exit();
}

$event_manager = EventManager::instance();

try {

    $server = new TuleapSOAPServer($uri.'/soap/codendi.wsdl.php?wsdl',array('trace' => 1));

    require_once('utils_soap.php');
    require_once('common/session.php');
    require_once('common/group.php');
    require_once('common/users.php');
    require_once('tracker/tracker.php');
    require_once('frs/frs.php');
    
    // include the <Plugin> API (only if plugin is available)
    $event_manager->processEvent('soap', array());
} catch (Exception $e) {
    echo $e;
}


// if POST was used to send this request, we handle it
// else, we display a list of available methods
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (LOG_SOAP_REQUESTS) {
        error_log('SOAP Request :');
        error_log($HTTP_RAW_POST_DATA);
    }
    $xml_security = new XML_Security();
    $xml_security->enableExternalLoadOfEntities();
    $server->handle();
    $xml_security->disableExternalLoadOfEntities();
} else {
    require_once 'common/templating/mustache/MustacheRenderer.class.php';
    site_header(array('title' => "SOAP API"));
    $renderer = new MustacheRenderer(new TemplateCache(),'templates');
    $renderer->renderToPage('soap_index', array());
    site_footer(array());
}
