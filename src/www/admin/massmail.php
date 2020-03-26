<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../include/pre.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

// get numbers of users for each mailing
$res_count     = db_query("SELECT COUNT(DISTINCT user.email) FROM user WHERE ( status='A' OR status='R' ) AND mail_va=1");
$count_comm    = db_result($res_count, 0, null);
$res_count     = db_query("SELECT COUNT(DISTINCT user.email) FROM user WHERE ( status='A' OR status='R' ) AND mail_siteupdates=1");
$count_sf      = db_result($res_count, 0, null);
$res_count     = db_query("SELECT COUNT(DISTINCT user.email) FROM user WHERE ( status='A' OR status='R' )");
$count_all     = db_result($res_count, 0, null);
$res_count     = db_query("SELECT COUNT(DISTINCT user.email) FROM user,user_group WHERE "
    . "user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.admin_flags='A'");
$count_admin   = db_result($res_count, 0, null);
$res_count     = db_query("SELECT COUNT(DISTINCT user.email) FROM user,user_group WHERE "
    . "user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' )");
$count_devel   = db_result($res_count, 0, null);
$res_count     = db_query("SELECT COUNT(DISTINCT user.email) FROM user,user_group WHERE "
    . "user.user_id=user_group.user_id AND( user.status='A' OR user.status='R' ) AND user_group.group_id=1");
$count_sfadmin = db_result($res_count, 0, null);

$recipients = array(
    array(
        'key'      => 'comm',
        'label'    => $Language->getText('admin_massmail', 'to_additional', $count_comm),
        'warning'  => $Language->getText('admin_massmail', 'warning', $count_comm),
        'nb_users' => $count_comm
    ),
    array(
        'key'      => 'sf',
        'label'    => $Language->getText('admin_massmail', 'to_update', $count_sf),
        'warning'  => $Language->getText('admin_massmail', 'warning', $count_sf),
        'nb_users' => $count_sf
    ),
    array(
        'key'      => 'devel',
        'label'    => $Language->getText('admin_massmail', 'to_devel', $count_devel),
        'warning'  => $Language->getText('admin_massmail', 'warning', $count_devel),
        'nb_users' => $count_devel
    ),
    array(
        'key'      => 'admin',
        'label'    => $Language->getText('admin_massmail', 'to_proj_admin', $count_admin),
        'warning'  => $Language->getText('admin_massmail', 'warning', $count_admin),
        'nb_users' => $count_admin
    ),
    array(
        'key'      => 'sfadmin',
        'label'    => $Language->getText('admin_massmail', 'to_site_admin', $count_sfadmin),
        'warning'  => $Language->getText('admin_massmail', 'warning', $count_sfadmin),
        'nb_users' => $count_sfadmin
    ),
    array(
        'key'      => 'all',
        'label'    => $Language->getText('admin_massmail', 'to_all', $count_all),
        'warning'  => $Language->getText('admin_massmail', 'warning', $count_all),
        'nb_users' => $count_all
    )
);

$include_assets = new IncludeAssets(__DIR__ . '/../assets/core', '/assets/core');

$GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL("ckeditor.js"));
$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/tuleap-ckeditor-toolbar.js');
$GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('site-admin-mass-emailing.js'));

$csrf  = new CSRFSynchronizerToken('/admin/massmail.php');
$title = $Language->getText('admin_massmail', 'title');

$presenter = new \Tuleap\Admin\MassmailPresenter(
    $title,
    $recipients,
    $csrf
);

$renderer = new \Tuleap\Admin\AdminPageRenderer();
$renderer->renderAPresenter(
    $title,
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/global-utils/',
    'massmail',
    $presenter
);
