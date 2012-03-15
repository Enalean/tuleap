<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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
require_once 'CatalogConnection.php';

// Read Config file
$configArray = parse_ini_file('../web/conf/config.ini', true);

// Setup Solr Connection
$url = $configArray['Index']['url'];
$solr = new Solr($url);
if ($configArray['System']['debug']) {
    $solr->debug = true;
}

// Make ILS Connection
try {
    $catalog = new CatalogConnection($configArray['Catalog']['driver']);
} catch (PDOException $e) {
    // What should we do with this error?
    if ($configArray['System']['debug']) {
        echo '<pre>';
        echo 'DEBUG: ' . $e->getMessage();
        echo '</pre>';
    }
}

// Get Suppressed Records and Delete from index
if ($catalog->status) {
    $result = $catalog->getSuppressedRecords();
    if (!PEAR::isError($result)) {
        $status = $solr->deleteRecords($result);
        if ($status) {
            // Commit and Optimize
            $solr->commit();
            $solr->optimize();
        }
    }
}
?>