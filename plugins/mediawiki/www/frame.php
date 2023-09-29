<?php
/*
 * MediaWiki plugin
 *
 * Copyright 2009, Roland Mas
 * Copyright 2006, Daniel Perez
 *
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';

$group_id   = getIntFromRequest('group_id');
$pluginname = 'mediawiki';

$project_manager = ProjectManager::instance();
$group           = $project_manager->getProject($group_id);
if (! $group) {
    exit_error("Invalid Project", "Invalid Project");
}

if (! $group->usesPlugin($pluginname)) {
    exit_error("Error", "First activate the $pluginname plugin through the Project's Admin Interface");
}

$params             =  [];
$params['toptab']   = $pluginname;
$params['title']    = _('wiki');
$params['pagename'] = $pluginname;

site_project_header($group, $params);

if (file_exists('/var/lib/gforge/plugins/mediawiki/wikidata/' . $group->getUnixName() . '/LocalSettings.php')) {
    echo '<iframe src="' . util_make_url('/plugins/mediawiki/wiki/' . $group->getUnixName() . '/index.php') . '" frameborder="0" width=100% height=700></iframe>';
} else {
    print '<h2>' . _('Wiki not created yet, please wait for a few minutes.') . '</h2>';
}

site_project_footer([]);

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
