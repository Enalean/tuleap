<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

require_once __DIR__ . '/../../../www/include/pre.php';
if (!$group_id || !$project) {
    exit_error("Invalid Project", "Invalid Project");
} else {
    define('VIRTUAL_PATH', $_SERVER['SCRIPT_NAME'] . '/' . $project->getUnixName());
    define('PATH_INFO_PREFIX', '/' . $project->getUnixName() . '/');

    define('WIKI_NAME', $project->getUnixName());
    //define('ALLOW_HTTP_AUTH_LOGIN', 1);
    //define('ADMIN_USER', '');
    //define('ADMIN_PASSWD', '');
        define('AUTH_SESS_USER', 'user_id');
        define('AUTH_SESS_LEVEL', 2);
        $USER_AUTH_ORDER = "Session : PersonalPage";
        $USER_AUTH_POLICY = "stacked";

    // Override the default configuration for CONSTANTS before index.php
    //$LANG='de'; $LC_ALL='de_DE';
    define('THEME', 'gforge');
    //define('WIKI_NAME', "WikiDemo:$LANG:" . THEME);

    // Load the default configuration.
    include "index.php";

    error_log("PATH_INFO_PREFIX " . PATH_INFO_PREFIX);

    // Override the default configuration for VARIABLES after index.php:
    // E.g. Use another DB:
    $DBParams['dbtype'] = 'SQL';
    $DBParams['dsn']    = 'pgsql://' . $sys_dbuser . ':' .
                              $sys_dbpasswd . '@' . $sys_dbhost . '/' . $sys_dbname
    . '_wiki';
    $DBParams['prefix'] = $project->getUnixName() . "_";

    // If the user is logged in, let the Wiki know
    if (session_loggedin()) {
            // let php do it's session stuff too!
            //ini_set('session.save_handler', 'files');
            session_start();
            $_SESSION['user_id'] = user_getname();
    } else {
            // clear out the globals, just in case...
    }
    // Start the wiki
    include "lib/main.php";
}
