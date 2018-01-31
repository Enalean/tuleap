<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Service\AdminRouter;
use Tuleap\Project\Service\DeleteController;
use Tuleap\Project\Service\IndexController;
use Tuleap\Project\Service\ServicesPresenterBuilder;

require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');

$request = HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'uint', 0);

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$service_manager = ServiceManager::instance();
$pm = ProjectManager::instance();
$project = $pm->getProject($group_id);

$func = $request->getValidated('func', 'string', '');

if ($func=='do_create') {
    $builder = new \Tuleap\Project\Service\ServicePOSTDataBuilder();
    try {
        $service_data = $builder->buildFromRequest($request);
    } catch (\Tuleap\Project\Service\InvalidServicePOSTDataException $exception) {
        exit_error($Language->getText('global','error'), $exception->getMessage());
    }

    $short_name        = $service_data->getShortName();
    $label             = $service_data->getLabel();
    $description       = $service_data->getDescription();
    $link              = $service_data->getLink();
    $rank              = $service_data->getRank();
    $scope             = $service_data->getScope();
    $is_active         = $service_data->isActive();
    $is_used           = $service_data->isUsed();
    $is_system_service = $service_data->isSystemService();

    if (! $is_active) {
        if ($is_used) {
            $GLOBALS['Response']->addFeedback('info',
                $Language->getText('project_admin_servicebar', 'set_stat_unused'));
            $is_used = false;
        }
    }

    if ($short_name) {
        // Check that the short_name is not already used
        $sql="SELECT * FROM service WHERE short_name='".db_es($short_name)."'";
        $result=db_query($sql);
        if (db_numrows($result)>0) {
            exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','short_name_exist'));
        }
    }

    if (($group_id!=100)&&($scope=="system")) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','cant_make_system_wide_s'));
    }
    $is_in_iframe = $request->get('is_in_iframe') ? 1 : 0;
    // Create
    $sql = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank, is_in_iframe) VALUES (".db_ei($group_id).", '".db_es($label)."', '".db_es($description)."', '".db_es($short_name)."', '".db_es($link)."', ".($is_active?"1":"0").", ".($is_used?"1":"0").", '".db_es($scope)."', ".db_ei($rank).", $is_in_iframe)";
    $result=db_query($sql);

    if (!$result) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','cant_create_s',db_error()));
    } else {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_servicebar','s_create_success'));
    }

    $pm->clear($group_id);
    $project = $pm->getProject($group_id);

    if (($is_active)&&($group_id==100)) {
        // Add service to ALL active projects!
        $sql1="SELECT group_id FROM groups WHERE group_id!=100";
        $result1=db_query($sql1);
        $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
        $link=str_replace('$sys_default_protocol',$sys_default_protocol,$link);
        $nbproj=1;
        $sys_default_protocol='http';
        if (ForgeConfig::get('sys_https_host')) {
            $sys_default_protocol='https';
        }
        while ($arr = db_fetch_array($result1)) {
            $my_group_id=$arr['group_id'];
            // Substitute values in links
            $my_link=$link;
            if (strstr($link,'$projectname')) {
                // Don't check project name if not needed.
                // When it is done here, the service bar will not appear updated on the current page
                $pm = ProjectManager::instance();
                $my_link=str_replace('$projectname',$pm->getProject($my_group_id)->getUnixName(),$my_link);
            }
            $my_link=str_replace('$group_id',$my_group_id,$my_link);

            $sql = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank, is_in_iframe) VALUES (".db_ei($my_group_id).", '".db_es($label)."', '".db_es($description)."', '".db_es($short_name)."', '".db_es($my_link)."', ".($is_active?"1":"0").", ".($is_used?"1":"0").", '".db_es($scope)."', ".db_ei($rank).", $is_in_iframe)";
            $result=db_query($sql);
            $nbproj++;
            if (!$result) {
                $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_servicebar','cant_create_s_for_p',$my_group_id));
            }
        }
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_servicebar','s_add_success',$nbproj));
    }
    $GLOBALS['Response']->redirect('/project/admin/servicebar.php?group_id='.$group_id);
}

if ($func=='do_update') {
    $builder = new \Tuleap\Project\Service\ServicePOSTDataBuilder();
    try {
        $service_data = $builder->buildFromRequest($request);
    } catch (\Tuleap\Project\Service\InvalidServicePOSTDataException $exception) {
        exit_error($Language->getText('global','error'), $exception->getMessage());
    }

    $short_name        = $service_data->getShortName();
    $label             = $service_data->getLabel();
    $description       = $service_data->getDescription();
    $link              = $service_data->getLink();
    $rank              = $service_data->getRank();
    $scope             = $service_data->getScope();
    $is_active         = $service_data->isActive();
    $is_used           = $service_data->isUsed();
    $is_system_service = $service_data->isSystemService();

    if (! $is_active) {
        if ($is_used) {
            $GLOBALS['Response']->addFeedback('info',
                $Language->getText('project_admin_servicebar', 'set_stat_unused'));
            $is_used = false;
        }
    }

    $redirect_url = '/project/admin/servicebar.php?' . http_build_query(array(
        'group_id' => $group_id
    ));

    $service_id = $request->getValidated('service_id', 'uint', 0);
    if (!$service_id) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','s_id_missed'));
    }

    if (! $service_manager->isServiceAllowedForProject($project, $service_id)) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','not_allowed'));
    }

    $set_server_id = '';
    $server_id = $request->getValidated('server_id', 'uint');
    if (user_is_super_user() && $server_id) {
        $set_server_id = ", location = 'satellite', server_id = ". (int)$server_id .' ';
    }
    $is_in_iframe = $request->get('is_in_iframe') ? 1 : 0;
    $admin_statement = '';
    if (user_is_super_user()) { //is_active and scope can only be change by a siteadmin
        $admin_statement = ", is_active=". ($is_active ? 1 : 0) .", scope='". db_es($scope) ."'";

    }

    $update_usage = '';
    if ($is_system_service) {
        $updatable = $service_manager->checkServiceCanBeUpdated($project, $short_name, $is_used);

        if (! $updatable) {
            $GLOBALS['Response']->redirect($redirect_url);
        }
    } else {
        $update_usage = ', is_used = '.db_ei($is_used);
    }

    $sql = "UPDATE service SET label='".db_es($label)."', description='".db_es($description)."', link='".db_es($link)."' ". $admin_statement .
        ", rank='".db_ei($rank)."' $set_server_id, is_in_iframe=$is_in_iframe $update_usage WHERE service_id=".db_ei($service_id);
    $result=db_query($sql);

    if (!$result) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','cant_update_s',db_error()));
    } else {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_servicebar','s_update_success'));
    }
    $pm->clear($group_id);

    if ($is_system_service) {
        $service_manager->toggleServiceUsage($project, $short_name, $is_used);
    }

    $GLOBALS['Response']->redirect($redirect_url);
}

$router = new AdminRouter(
    new IndexController(
        new ServicesPresenterBuilder(ServiceManager::instance()),
        new IncludeAssets(ForgeConfig::get('tuleap_dir') . '/src/www/assets', '/assets'),
        new HeaderNavigationDisplayer()
    ),
    new DeleteController(new ServiceDao())
);
$router->process($request);
