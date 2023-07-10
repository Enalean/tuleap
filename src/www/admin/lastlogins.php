<?php
/**
 * SourceForge: Breaking Down the Barriers to Open Source Development
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\MostRecentLoginsPresenter;
use Tuleap\Admin\MostRecentLoginPresenter;

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$include_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($include_assets, 'site-admin-most-recent-logins.js'));

$res_logins = db_query("SELECT session.user_id AS user_id,"
    . "session.ip_addr AS ip_addr,"
    . "session.time AS time,"
    . "user.user_name AS user_name FROM session,user "
    . "WHERE session.user_id=user.user_id AND "
    . "session.user_id>0 AND session.time>0 ORDER BY session.time DESC LIMIT 5000");

$most_recent_login_presenters = [];

while ($row_logins = db_fetch_array($res_logins)) {
    $most_recent_login_presenters[] = new MostRecentLoginPresenter(
        $row_logins['user_name'],
        $row_logins['ip_addr'],
        $row_logins['time']
    );
}

$most_recent_logins_presenter = new MostRecentLoginsPresenter($most_recent_login_presenters);

$admin_page = new AdminPageRenderer();
$admin_page->renderAPresenter(
    $Language->getText('admin_lastlogins', 'title'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/most_recent_logins/',
    'most-recent-logins',
    $most_recent_logins_presenter
);
