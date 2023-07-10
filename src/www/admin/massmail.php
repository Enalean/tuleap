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

$recipients = [
    [
        'key'      => 'comm',
        'label'    => sprintf(_('Send only to users subscribed to "Additional Community Mailings" (%1$s users)'), $count_comm),
        'warning'  => sprintf(_('%1$s users will receive this email.'), $count_comm),
        'nb_users' => $count_comm,
    ],
    [
        'key'      => 'sf',
        'label'    => sprintf(_('Users that agreed to receive "Site Updates" (%1$s users)'), $count_sf),
        'warning'  => sprintf(_('%1$s users will receive this email.'), $count_sf),
        'nb_users' => $count_sf,
    ],
    [
        'key'      => 'devel',
        'label'    => sprintf(_('Project developers (%1$s users)'), $count_devel),
        'warning'  => sprintf(_('%1$s users will receive this email.'), $count_devel),
        'nb_users' => $count_devel,
    ],
    [
        'key'      => 'admin',
        'label'    => sprintf(_('Project administrators (%1$s users)'), $count_admin),
        'warning'  => sprintf(_('%1$s users will receive this email.'), $count_admin),
        'nb_users' => $count_admin,
    ],
    [
        'key'      => 'sfadmin',
        'label'    => sprintf(_('Site administrators (%1$s users)'), $count_sfadmin),
        'warning'  => sprintf(_('%1$s users will receive this email.'), $count_sfadmin),
        'nb_users' => $count_sfadmin,
    ],
    [
        'key'      => 'all',
        'label'    => sprintf(_('All users, regardless of their preferences (%1$s users)'), $count_all),
        'warning'  => sprintf(_('%1$s users will receive this email.'), $count_all),
        'nb_users' => $count_all,
    ],
];

$include_assets = new \Tuleap\Layout\IncludeCoreAssets();

$GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL("ckeditor.js"));
$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/tuleap-ckeditor-toolbar.js');
$site_admin_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($site_admin_assets, 'site-admin-mass-emailing.js'));

$csrf  = new CSRFSynchronizerToken('/admin/massmail.php');
$title = _('Mass emailing');

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
