<?php

require_once 'pre.php';

$renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/help');



$presenter = array(
    'explorer_available' => is_dir('/usr/share/restler/vendor/Luracast/Restler/explorer'),
    'end_points'         => array(
        array(
            'title'       => 'Core',
            'wsdl'        => '/soap/?wsdl',
            'wsdl_viewer' => '/soap/wsdl',
            'changelog'   => '/soap/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__).'/../soap/VERSION'),
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
            'version'     => file_get_contents(dirname(__FILE__).'/../soap/svn/VERSION'),
            'description' => 'Get informations about Subversion usage in project.',
        ),
        array(
            'title'       => 'Project',
            'wsdl'        => '/soap/project/?wsdl',
            'wsdl_viewer' => '/soap/project/wsdl-viewer',
            'changelog'   => '/soap/project/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__).'/../soap/project/VERSION'),
            'description' => 'Create and administrate projects.',
        ),
    )
);

EventManager::instance()->processEvent(Event::SOAP_DESCRIPTION, array('end_points' => &$presenter['end_points']));

$GLOBALS['HTML']->header(array('title' => 'API'));
$renderer->renderToPage('api', $presenter);
$GLOBALS['HTML']->footer(array());
