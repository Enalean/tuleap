<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/DocmanImport/ImportFromDocmanV1.class.php';

if ($argc != 4) {
    die("*** Usage: " . basename($argv[0]) . " wsdl_url admin_loginame projectid\n");
}

$wsdl_url   = $argv[1];
$login      = $argv[2];
$project_id = $argv[3];

$password = getPasswordFromStdin($login);

$migration = new Docman_ImportFromDocmanV1($wsdl_url, $login, $password);
$migration->migrate(ProjectManager::instance()->getProject($project_id));

function getPasswordFromStdin($login)
{
    echo "Password for $login: ";

    if (PHP_OS != 'WINNT') {
        shell_exec('stty -echo');
        $password = fgets(STDIN);
        shell_exec('stty echo');
    } else {
        $password = fgets(STDIN);
    }
    $password = substr($password, 0, strlen($password) - 1);
    echo PHP_EOL;
    return $password;
}
