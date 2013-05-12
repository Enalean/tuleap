<?php

require_once('pre.php');

define('CODENDI_WS_API_VERSION', '6.0');

define('LOG_SOAP_REQUESTS', false);

// Check if we the server is in secure mode or not.
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $GLOBALS['sys_force_ssl'] == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}

$uri = $protocol.'://'.$sys_default_domain;

if ($request->exist('wsdl')) {
    header("Location: ".$uri."/soap/codendi.wsdl.php?wsdl");
    exit();
}

$event_manager = EventManager::instance();

try {

    $server = new SoapServer($uri.'/soap/codendi.wsdl.php?wsdl',array('trace' => 1, 'soap_version' => SOAP_1_1));

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
    $server -> handle();
} else {
    $presenter = array('end_points' => array(
        array(
            'title'       => 'Core',
            'wsdl'        => '/soap/?wsdl',
            'wsdl_viewer' => '/soap/wsdl',
            'changelog'   => '/soap/ChangeLog',
            'version'     => CODENDI_WS_API_VERSION,
            'description' => <<<EOT
Historically the sole end point, therefore it groups multiple different functions:
<ul>
    <li>Session management: login, logout, projects, ...</li>
    <li>File Release System access (FRS): addPackage, addRelease, addFile, ...</li>
    <li>Tracker v3 (for historical deployments): get/updateTracker, get/updateArtifact, ...</li>
    <li>Documentation: get/updateDocman, ...</li>
</ul>
EOT
        ),
        array(
            'title'       => 'Subversion',
            'wsdl'        => '/soap/svn/?wsdl',
            'wsdl_viewer' => '/soap/svn/wsdl-viewer',
            'changelog'   => '/soap/svn/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__).'/svn/VERSION'),
            'description' => 'Get informations about Subversion usage in project.',
        ),
        array(
            'title'       => 'Project',
            'wsdl'        => '/soap/project/?wsdl',
            'wsdl_viewer' => '/soap/project/wsdl-viewer',
            'changelog'   => '/soap/project/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__).'/project/VERSION'),
            'description' => 'Create and administrate projects.',
        ),
    ));

    $event_manager->processEvent(Event::SOAP_DESCRIPTION, array('end_points' => &$presenter['end_points']));

    require_once 'common/templating/mustache/MustacheRenderer.class.php';
    site_header(array('title' => "SOAP API"));
    $renderer = new MustacheRenderer('templates');
    $renderer->renderToPage('soap_index', $presenter);
    site_footer(array());
}

?>
