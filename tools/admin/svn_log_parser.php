<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 *
 *
 * This tool parse svn_log files to find the top access repositories
 * in the given log file. There is default threshold of 5%.
 *
 * Usage: php svn_log_parser.php svn_log
 *   svn_log is a log file produced by Apache mod_svn module you find
 *   in /var/log/httpd
 */

if ($argc != 2 || !is_file($argv[1])) {
    usage();
    exit;
}

// Match
// 192.168.1.1 - user_name [21/Nov/2011:23:59:27 +0100] /svnroot/stuff/!svn/bc/20976/trunk/Makefile 207 "get-file /trunk/Makefile r20976 props"

// Count number of access
$repos        = array();
$total_access = 0;
foreach (file($argv[1]) as $line) {
    $matches = array();
    if (preg_match('%^(.*) - (.*) (\[.*\]) /svnroot/([^\/ ]*).*%', $line, $matches)) {
        $total_access++;
        if (!isset($repos[$matches[4]])) {
            $repos[$matches[4]] = 1;
        } else {
            $repos[$matches[4]]++;
        }
    }
}
asort($repos, SORT_NUMERIC);

echo "Total amount of access: $total_access\n";
foreach ($repos as $repo_name => $nb_access) {
    $percent = round(($nb_access / $total_access) * 100, 1);
    if ($percent >= 5) {
        echo "$repo_name = $percent% ($nb_access access)\n";
    }
}

function usage() {
    echo <<< EOT
Usage: php svn_log_parser.php svn_log
   svn_log is a log file produced by Apache mod_svn module you find
   in /var/log/httpd

EOT;
}