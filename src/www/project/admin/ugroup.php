<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

// Show/manage ugroup list

use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\UGroupBuilder;
use Tuleap\User\UserGroup\NameTranslator;

require_once('pre.php');
require_once('www/project/admin/permissions.php');

function format_html_row($row, &$row_num) {
    echo "<tr class=\"". util_get_alt_row_color($row_num++) ."\">\n";
    foreach($row as $cell) {
        $htmlattrs = '';
        $value = '';
        if(is_array($cell)) {
            if(isset($cell['value'])) {
                $value = $cell['value'];
            }
            if(isset($cell['html_attrs'])) {
                $htmlattrs = ' '.$cell['html_attrs'];
            }
        } else {
            $value = $cell;
        }

        echo '  <td>'.$value."</td>\n";
    }
    echo "</tr>\n";
}

$em      = EventManager::instance();
$request = HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'GroupId', 0);

$csrf = new CSRFSynchronizerToken('/project/admin/ugroup.php');

session_require(array('group' => $group_id, 'admin_flags' => 'A'));

if ($request->existAndNonEmpty('func')) {
    $ugroup_id   = $request->getValidated('ugroup_id', 'UInt', 0);

    switch($request->get('func')) {
        case 'delete':
            $csrf->check();
            ugroup_delete($group_id, $ugroup_id);
            break;
        case 'do_update':
            $name = $request->getValidated('ugroup_name', 'String', '');
            $desc = $request->getValidated('ugroup_description', 'String', '');
            ugroup_update($group_id, $ugroup_id, $name, $desc);
            $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='.urlencode($group_id).'&ugroup_id='.urlencode($ugroup_id).'&func=edit&pane=settings');
            break;
        case 'do_create':
            $name     = $request->getValidated('ugroup_name', 'String', '');
            $desc     = $request->getValidated('ugroup_description', 'String', '');
            $template = $request->getValidated('group_templates', 'String', '');

            $ugroup_id = ugroup_create($group_id, $name, $desc, $template);
            $GLOBALS['Response']->redirect(
                '/project/admin/editugroup.php?group_id=' . urlencode($group_id) .
                '&ugroup_id=' . urlencode( $ugroup_id) . '&func=edit'
            );
            break;
    }
    $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id='. urlencode($group_id));
}

$pm = ProjectManager::instance();
$project=$pm->getProject($group_id);

$title = $Language->getText('project_admin_ugroup', 'manage_ug');

$include_assets = new IncludeAssets(ForgeConfig::get('tuleap_dir') . '/src/www/assets', '/assets');
$GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('project-admin-ugroups.js'));

$navigation_displayer = new HeaderNavigationDisplayer();
$navigation_displayer->displayBurningParrotNavigation($title, $project, 'groups');

$ugroups = array();
$result = db_query("SELECT * FROM ugroup WHERE group_id=$group_id ORDER BY name");
while ($row = db_fetch_array($result)) {
    $res2 = db_query("SELECT count(*) FROM ugroup_user WHERE ugroup_id=".$row['ugroup_id']);
    $nb_members = db_result($res2,0,0);
    $ugroups[] = array(
        'id'          => $row['ugroup_id'],
        'name'        => util_translate_name_ugroup($row['name']),
        'description' => util_translate_name_ugroup($row['description']),
        'nb_members'  => $nb_members
    );
}

$ugroup_builder   = new UGroupBuilder(new UGroupManager(), new NameTranslator());
$template_ugroups = $ugroup_builder->getUGroupsThatCanBeUsedAsTemplate($project);

$templates_dir = ForgeConfig::get('codendi_dir') . '/src/templates/project/admin/';
TemplateRendererFactory::build()
    ->getRenderer($templates_dir)
    ->renderToPage('list-groups', array(
        'ugroups'            => $ugroups,
        'template_ugroups'   => $template_ugroups,
        'project_id'         => $project->getID(),
        'csrf'               => $csrf
    ));

project_admin_footer(array());
