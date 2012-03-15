<?php
/**
 *
 * Copyright (C) Villanova University 2009.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
ini_set('memory_limit', '50M');
ini_set('max_execution_time', '3600');

require_once 'util.inc.php';        // set up util environment
require_once 'sys/Solr.php';

// Read Config file
$configArray = parse_ini_file('../web/conf/config.ini', true);

// Setup Solr Connection -- Allow core to be specified as first command line param.
$url = $configArray['Index']['url'];
$solr = new Solr($url, isset($argv[1]) ? $argv[1] : '');
if ($configArray['System']['debug']) {
    $solr->debug = true;
}

// Commit and Optimize the Solr Index
$solr->commit();
$solr->optimize();

?>