#! /usr/bin/php
<?php
/*
 * Copyright (C) 2010  Olaf Lenz
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

  /** This script will automatically create mediawiki instances for
   projects that do not yet have it.

   It is intended to be started in a cronjob.
   */

# TODO: How to use cronjob history?
# Required config variables:
#   src_path: the directory where the mediawiki sources are installed

require_once 'www/env.inc.php';
require_once 'pre.php';
require_once dirname(__FILE__).'/../fusionforge/cron_utils.php';

class MediaWiki_Instantiater {
    
    public function instantiate($project_name) {
        $schema = strtr("plugin_mediawiki_".$project_name, "-", "_");

        $this->createDirectory($project_name);
        $this->createDatabase($schema);
        $this->cleanUp($project_name);
    }

    private function createDirectory($project_name) {
        $project_name_dir = forge_get_config('projects_path', 'mediawiki') . "/$project_name";
        cron_debug("Checking $project_name ...");
        
        if (is_dir($project_name_dir)) {
            cron_debug("  Project dir $project_name_dir exists, so I assumen the project already exists.");
        } else {
            cron_debug("  Creating project dir $project_name_dir.");
            mkdir($project_name_dir, 0775, true);
        }
    }

    private function createDatabase($schema) {
        $src_path = forge_get_config('src_path', 'mediawiki');
        $table_file = "$src_path/maintenance/tables.sql";

        db_begin();

        try{
            cron_debug("Creating schema $schema.");
            $create_db = db_query_params("CREATE SCHEMA".$schema, array());
            if (! $create_db) {
                throw new Exception("Error: Schema Creation Failed: ".db_error());
            }

            cron_debug("Creating mediawiki database.");
            if (! file_exists($table_file)) {
                throw new Exception("Error: Couldn't find Mediawiki Database Creation File $table_file!");
            }

            $use_new_schema = db_query_params("USE $schema;", array());
            if (! $use_new_schema) {
                throw new Exception("Error: DB Query Failed: ".db_error());
            }

            $add_tables = db_query_from_file($table_file);
            if (! $add_tables) {
                throw new Exception("Error: Mediawiki Database Creation Failed: ".db_error());
            }

        } catch (Excepion $e) {
            db_rollback();
            cron_entry(23, $e->getMessage());
        }

        db_commit();
    }

    private function cleanUp() {
        $mwwrapper = forge_get_config('source_path')."/plugins/mediawiki/bin/mw-wrapper.php" ;
        $dumpfile = forge_get_config('config_path')."/mediawiki/initial-content.xml" ;

        if (file_exists ($dumpfile)) {
            cron_debug("Dumping using $mwwrapper");
            system ("$mwwrapper $project_name importDump.php $dumpfile") ;
            system ("$mwwrapper $project_name rebuildrecentchanges.php") ;
        }
    }
}

$src_path = forge_get_config('src_path', 'mediawiki');
$project_name_dir = forge_get_config('projects_path', 'mediawiki') . "/$project_name";
cron_debug("Checking $project_name...");



/*???????????????????????????*/
$res = db_query_params('
    DELETE FROM plugin_mediawiki_interwiki 
    WHERE iw_prefix=$1', array($project_name));



$url = util_make_url('/plugins/mediawiki/wiki/' . $project_name . '/index.php/$1');



$res = db_query_params('
    INSERT INTO plugin_mediawiki_interwiki 
    VALUES ($1, $2, 1, 0)',
                       array($project_name,
                             $url));


 /*???????????????????????????*/

// Create the project directory if necessary
if (is_dir($project_name_dir)) {
    cron_debug("  Project dir $project_name_dir exists, so I assumen the project already exists.");
} else {
    cron_debug("  Creating project dir $project_name_dir.");
    mkdir($project_name_dir, 0775, true);

    // Create the DB
    $schema = "plugin_mediawiki_$project_name";
    // Sanitize schema name
    $schema = strtr($schema, "-", "_");

    db_begin();

    cron_debug("  Creating schema $schema.");
    $res = db_query_params("CREATE SCHEMA $schema", array());
    if (!$res) {
        $err =  "Error: Schema Creation Failed: " .
                db_error();
        cron_debug($err);
        cron_entry(23,$err);
        db_rollback();
        exit;
    }

    cron_debug("  Creating mediawiki database.");
    $table_file = "$src_path/maintenance/tables.sql";
    if (!file_exists($table_file)) {
        $err =  "Error: Couldn't find Mediawiki Database Creation File $table_file!";
        cron_debug($err);
        cron_entry(23,$err);
        db_rollback();
        exit;
    }

    $res = db_query_params("use $schema;", array());
    if (!$res) {
        $err =  "Error: DB Query Failed: " .
                db_error();
        cron_debug($err);
        cron_entry(23,$err);
        db_rollback();
        exit;
    }

    $creation_query = file_get_contents($table_file);
    $res = db_query_from_file($table_file);
    if (!$res) {
        $err =  "Error: Mediawiki Database Creation Failed: " .
                db_error();
        cron_debug($err);
        cron_entry(23,$err);
        db_rollback();
        exit;
    }

    $mwwrapper = forge_get_config('source_path')."/plugins/mediawiki/bin/mw-wrapper.php" ;
    $dumpfile = forge_get_config('config_path')."/mediawiki/initial-content.xml" ;

    if (file_exists ($dumpfile)) {
        cron_debug("Dumping using $mwwrapper");
        system ("$mwwrapper $project_name importDump.php $dumpfile") ;
        system ("$mwwrapper $project_name rebuildrecentchanges.php") ;
    }
}
?>
