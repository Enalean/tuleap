<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * How to compare 2 dumps:
 * diff -u -I "[ ]*<date>.*</date>" -I "[ ]*<create_date>.*</create_date>" -I "[ ]*<update_date>.*</update_date>" -I "[ ]*<owner>.*</owner>" -I "[ ]*<author>.*</author>" file1.xml file2.xml
 */
set_include_path(get_include_path() .PATH_SEPARATOR. dirname(__FILE__).'/../../../../src' .PATH_SEPARATOR. dirname(__FILE__).'/../../../../src/www/include');
require 'pre.php';
require 'XMLExport.class.php';

function error($str) {
    echo "*** Error: $str".PHP_EOL;
}

function usage() {
    echo "Usage: export.php groupId targetname".PHP_EOL;
}

if(!isset($argv[2])) {
    error("No target directory specified");
    usage();
    return false;
}

if(is_file($argv[2])) {
    error("Target directoy already exists");
    usage();
    return false;
}

$start = microtime(true);

$XMLExport = new XMLExport();
$XMLExport->setGroupId($argv[1]);
$XMLExport->setArchiveName($argv[2]);

//echo $XMLExport->dump()->saveXML();
$XMLExport->dumpPackage();

$end = microtime(true);

echo "Elapsed time: ".($end-$start).PHP_EOL;

?>
