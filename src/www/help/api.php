<?php

require_once 'pre.php';

$renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/help');



$presenter = array(
    'should_display_documentation_about_deprecated_soap_api' => ForgeConfig::get('should_display_documentation_about_deprecated_soap_api'),
    'explorer_available' => is_dir('/usr/share/tuleap/src/www/api/explorer'),
    'end_points'         => array(
        array(
            'title'       => 'Core',
            'wsdl'        => '/soap/?wsdl',
            'wsdl_viewer' => '/soap/wsdl.php',
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
            'wsdl_viewer' => '/soap/svn/wsdl-viewer.php',
            'changelog'   => '/soap/svn/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__).'/../soap/svn/VERSION'),
            'description' => 'Get informations about Subversion usage in project.',
        ),
        array(
            'title'       => 'Project',
            'wsdl'        => '/soap/project/?wsdl',
            'wsdl_viewer' => '/soap/project/wsdl-viewer.php',
            'changelog'   => '/soap/project/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__).'/../soap/project/VERSION'),
            'description' => 'Create and administrate projects.',
        ),
    )
);

$GLOBALS['HTML']->header(array('title' => 'API', 'main_classes' => array('tlp-framed')));
$renderer->renderToPage('api', $presenter);
$GLOBALS['HTML']->footer(array());
