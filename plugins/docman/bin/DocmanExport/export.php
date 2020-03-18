<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * How to compare 2 dumps:
 * diff -u -I "[ ]*<date>.*</date>" -I "[ ]*<create_date>.*</create_date>" -I "[ ]*<update_date>.*</update_date>" -I "[ ]*<owner>.*</owner>" -I "[ ]*<author>.*</author>" file1.xml file2.xml
 */
require_once __DIR__ . '/../../../../src/www/include/pre.php';
require __DIR__ . '/../../include/docmanPlugin.php';
require __DIR__ . '/XMLExport.class.php';
require __DIR__ . '/Docman_ExportException.class.php';


$consoleLogger = new Log_ConsoleLogger();

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user   = $posix_user['name'];
if ($sys_user !== 'root' && $sys_user !== ForgeConfig::get('sys_http_user')) {
    $consoleLogger->error('Unsufficient privileges for user ' . $sys_user);
    return false;
}

function usage()
{
    $consoleLogger = new Log_ConsoleLogger();
    $consoleLogger->error("Usage: export.php groupId targetname");
}

if (!isset($argv[2])) {
    $consoleLogger->error("No target directory specified");
    usage();
    return false;
}

if (is_file($argv[2])) {
    $consoleLogger->error("Target directoy already exists");
    return false;
}

$start = microtime(true);

try {
    $logger    = BackendLogger::getDefaultLogger('DocmanExport.log');
    $XMLExport = new XMLExport($logger);
    $XMLExport->setGroupId($argv[1]);
    $XMLExport->setPackagePath($argv[2]);
    $XMLExport->setArchiveName(basename($argv[2]));
    $XMLExport->dumpPackage();
} catch (Exception $exception) {
    $consoleLogger->error("Export failed : " . $exception->getMessage());
    return false;
}

$end = microtime(true);
$consoleLogger->info("Elapsed time: " . ($end - $start));
