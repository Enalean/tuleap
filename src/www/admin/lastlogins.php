<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// Copyright 2016 Enalean SAS
// http://sourceforge.net
//
//

require_once('pre.php');

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\MostRecentLoginsPresenter;
use Tuleap\Admin\MostRecentLoginPresenter;

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/admin/most-recent-logins.js');

session_require(array('group'=>'1','admin_flags'=>'A'));

$res_logins = db_query("SELECT session.user_id AS user_id,"
    . "session.ip_addr AS ip_addr,"
    . "session.time AS time,"
    . "user.user_name AS user_name FROM session,user "
    . "WHERE session.user_id=user.user_id AND "
    . "session.user_id>0 AND session.time>0 ORDER BY session.time DESC LIMIT 5000"
);

$most_recent_login_presenters = array();

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
