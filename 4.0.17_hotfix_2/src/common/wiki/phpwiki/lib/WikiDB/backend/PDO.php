<?php // -*-php-*-
rcs_id('$Id: PDO.php,v 1.6 2005/11/14 22:24:33 rurban Exp $');

/*
 Copyright 2005 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/ 

/**
 * @author: Reini Urban
 */

require_once('lib/WikiDB/backend.php');

class WikiDB_backend_PDO
extends WikiDB_backend
{

    function WikiDB_backend_PDO ($dbparams) {
        $this->_dbparams = $dbparams;
        if (strstr($dbparams['dsn'], "://")) { // pear DB syntax
            $parsed = parseDSN($dbparams['dsn']);
            $this->_parsedDSN = $parsed;
            /* Need to convert the DSN
             *    dbtype(dbsyntax)://username:password@protocol+hostspec/database?option=value&option2=value2
             * to dbtype:option1=value1;option2=value2
             * PDO passes the DSN verbatim to the driver. But we need to extract the username + password
             * and cannot rely that the parseDSN keys have the same name as needed by the driver.
             * e.g: odbc:DSN=SAMPLE;UID=db2inst1;PWD=ibmdb2, mysql:host=127.0.0.1;dbname=testdb
             */
            $driver = $parsed['phptype'];
            unset($parsed['phptype']);
            unset($parsed['dbsyntax']);
            $dbparams['dsn'] = $driver . ":";
            $this->_dbh->database = $parsed['database'];
            // mysql needs to map database=>dbname, hostspec=>host. TODO for the others.
            $dsnmap = array('mysql' => array('database'=>'dbname', 'hostspec' => 'host')
                            );
            foreach (array('protocol','hostspec','port','socket','database') as $option) {
                if (!empty($parsed[$option])) {
                    $optionname = (isset($dsnmap[$driver][$option]) and !isset($parsed[$optionname]))
                        ? $dsnmap[$driver][$option]
                        : $option;
                    $dbparams['dsn'] .= ($optionname . "=" . $parsed[$option] . ";");
                    unset($parsed[$optionname]);
                }
                unset($parsed[$option]);
            }
            unset($parsed['username']);
            unset($parsed['password']);
            // pass the rest verbatim to the driver.
            foreach ($parsed as $option => $value) {
                $dbparams['dsn'] .= ($option . "=" . $value . ";");
            }
        } else {
            list($driver, $dsn) = explode(":", $dbparams['dsn'], 2);
            foreach (explode(";", trim($dsn)) as $pair) {
                if ($pair) {
                    list($option, $value) = explode("=", $pair, 2);
                    $this->_parsedDSN[$option] = $value;
                }
            }
            $this->_dbh->database = isset($this->_parsedDSN['database']) 
                ? $this->_parsedDSN['database'] 
                : $this->_parsedDSN['dbname'];
        }
        if (empty($this->_parsedDSN['password'])) $this->_parsedDSN['password'] = '';

        try {
            // try to load it dynamically (unix only)
            if (!loadPhpExtension("pdo_$driver")) {
                echo $GLOBALS['php_errormsg'], "<br>\n";
                trigger_error(sprintf("dl() problem: Required extension '%s' could not be loaded!", 
                                      "pdo_$driver"),
                              E_USER_WARNING);
            }

            // persistent is defined as DSN option, or with a config value.
            //   phptype://username:password@hostspec/database?persistent=false
            $this->_dbh = new PDO($dbparams['dsn'], 
                                  $this->_parsedDSN['username'], 
                                  $this->_parsedDSN['password'],
                                  array(PDO_ATTR_AUTOCOMMIT => true,
                                        PDO_ATTR_TIMEOUT    => DATABASE_TIMEOUT,
                                        PDO_ATTR_PERSISTENT => !empty($parsed['persistent'])
                                                               or DATABASE_PERSISTENT
                                        ));
        }
        catch (PDOException $e) {
            echo "<br>\nDB Connection failed: " . $e->getMessage();
            if (DEBUG & _DEBUG_VERBOSE or DEBUG & _DEBUG_SQL) {
                echo "<br>\nDSN: '", $dbparams['dsn'], "'";
                echo "<br>\n_parsedDSN: '", print_r($this->_parsedDSN), "'";
                echo "<br>\nparsed: '", print_r($parsed), "'";
            }
            exit();
        }
        if (DEBUG & _DEBUG_SQL) { // not yet implemented
            $this->_dbh->debug = true;
        }
        $this->_dsn = $dbparams['dsn'];
        $this->_dbh->databaseType = $driver;
        $this->_dbh->setAttribute(PDO_ATTR_CASE, PDO_CASE_NATURAL);
        // Use the faster FETCH_NUM, with some special ASSOC based exceptions.

        $this->_hasTransactions = true;
        try {
            $this->_dbh->beginTransaction();
        }
        catch (PDOException $e) {
            $this->_hasTransactions = false;
        }
        $sth = $this->_dbh->prepare("SELECT version()");
        $sth->execute();
        $this->_serverinfo['version'] = $sth->fetchSingle();
        $this->commit(); // required to match the try catch block above!

        $prefix = isset($dbparams['prefix']) ? $dbparams['prefix'] : '';
        $this->_table_names
            = array('page_tbl'     => $prefix . 'page',
                    'version_tbl'  => $prefix . 'version',
                    'link_tbl'     => $prefix . 'link',
                    'recent_tbl'   => $prefix . 'recent',
                    'nonempty_tbl' => $prefix . 'nonempty');
        $page_tbl = $this->_table_names['page_tbl'];
        $version_tbl = $this->_table_names['version_tbl'];
        $this->page_tbl_fields = "$page_tbl.id AS id, $page_tbl.pagename AS pagename, "
            . "$page_tbl.hits hits";
        $this->page_tbl_field_list = array('id', 'pagename', 'hits');
        $this->version_tbl_fields = "$version_tbl.version AS version, "
            . "$version_tbl.mtime AS mtime, "
            . "$version_tbl.minor_edit AS minor_edit, $version_tbl.content AS content, "
            . "$version_tbl.versiondata AS versiondata";
        $this->version_tbl_field_list = array('version', 'mtime', 'minor_edit', 'content',
            'versiondata');

        $this->_expressions
            = array('maxmajor'     => "MAX(CASE WHEN minor_edit=0 THEN version END)",
                    'maxminor'     => "MAX(CASE WHEN minor_edit<>0 THEN version END)",
                    'maxversion'   => "MAX(version)",
                    'notempty'     => "<>''",
                    'iscontent'    => "$version_tbl.content<>''");
        $this->_lock_count = 0;
    }

    function beginTransaction() {
        if ($this->_hasTransactions)
            $this->_dbh->beginTransaction();
    }
    function commit() {
        if ($this->_hasTransactions)
            $this->_dbh->commit();
    }
    function rollback() {
        if ($this->_hasTransactions)
            $this->_dbh->rollback();
    }
    /* no result */
    function query($sql) {
        $sth = $this->_dbh->prepare($sql);
        return $sth->execute();
    }
    /* with one result row */
    function getRow($sql) {
        $sth = $this->_dbh->prepare($sql);
        if ($sth->execute())
            return $sth->fetch(PDO_FETCH_BOTH);
        else 
            return false;
    }

    /**
     * Close database connection.
     */
    function close () {
        if (!$this->_dbh)
            return;
        if ($this->_lock_count) {
            trigger_error("WARNING: database still locked " . 
                          '(lock_count = $this->_lock_count)' . "\n<br />",
                          E_USER_WARNING);
        }
        $this->unlock(false, 'force');

        unset($this->_dbh);
    }

    /*
     * Fast test for wikipage.
     */
    function is_wiki_page($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $sth = $dbh->prepare("SELECT $page_tbl.id AS id"
                             . " FROM $nonempty_tbl, $page_tbl"
                             . " WHERE $nonempty_tbl.id=$page_tbl.id"
                             . "   AND pagename=?");
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        if ($sth->execute())
            return $sth->fetchSingle();
        else
            return false;
    }
        
    function get_all_pagenames() {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $sth = $dbh->exec("SELECT pagename"
                          . " FROM $nonempty_tbl, $page_tbl"
                          . " WHERE $nonempty_tbl.id=$page_tbl.id"
                          . " LIMIT 1");
        return $sth->fetchAll(PDO_FETCH_NUM);
    }

    function numPages($filter=false, $exclude='') {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $sth = $dbh->exec("SELECT count(*)"
                          . " FROM $nonempty_tbl, $page_tbl"
                          . " WHERE $nonempty_tbl.id=$page_tbl.id");
        return $sth->fetchSingle();
    }

    function increaseHitCount($pagename) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        $sth = $dbh->prepare("UPDATE $page_tbl SET hits=hits+1"
                             . " WHERE pagename=?"
                             . " LIMIT 1");
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        $sth->execute();
    }

    /**
     * Read page information from database.
     */
    function get_pagedata($pagename) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        $sth = $dbh->prepare("SELECT id,pagename,hits,pagedata FROM $page_tbl"
                             ." WHERE pagename=? LIMIT 1");
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        $sth->execute();
        $row = $sth->fetch(PDO_FETCH_NUM);
        return $row ? $this->_extract_page_data($row[3], $row[2]) : false;
    }

    function  _extract_page_data($data, $hits) {
        if (empty($data))
            return array('hits' => $hits);
        else 
            return array_merge(array('hits' => $hits), $this->_unserialize($data));
    }

    function update_pagedata($pagename, $newdata) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];

        // Hits is the only thing we can update in a fast manner.
        if (count($newdata) == 1 && isset($newdata['hits'])) {
            // Note that this will fail silently if the page does not
            // have a record in the page table.  Since it's just the
            // hit count, who cares?
            $sth = $dbh->prepare("UPDATE $page_tbl SET hits=? WHERE pagename=? LIMIT 1");
            $sth->bindParam(1, $newdata['hits'], PDO_PARAM_INT);
            $sth->bindParam(2, $pagename, PDO_PARAM_STR, 100);
            $sth->execute();
            return;
        }
        $this->beginTransaction();
        $data = $this->get_pagedata($pagename);
        if (!$data) {
            $data = array();
            $this->_get_pageid($pagename, true); // Creates page record
        }
        
        $hits = (empty($data['hits'])) ? 0 : (int)$data['hits'];
        unset($data['hits']);

        foreach ($newdata as $key => $val) {
            if ($key == 'hits')
                $hits = (int)$val;
            else if (empty($val))
                unset($data[$key]);
            else
                $data[$key] = $val;
        }
        $sth = $dbh->prepare("UPDATE $page_tbl"
                             . " SET hits=?, pagedata=?"
                             . " WHERE pagename=?"
                             . " LIMIT 1");
        $sth->bindParam(1, $hits, PDO_PARAM_INT);
        $sth->bindParam(2, $this->_serialize($data), PDO_PARAM_LOB);
        $sth->bindParam(3, $pagename, PDO_PARAM_STR, 100);
        if ($sth->execute()) {
            $this->commit();
            return true;
        } else {
            $this->rollBack();
            return false;
        }
    }

    function get_cached_html($pagename) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        $sth = $dbh->prepare("SELECT cached_html FROM $page_tbl WHERE pagename=? LIMIT 1");
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        $sth->execute();
        return $sth->fetchSingle(PDO_FETCH_NUM);
    }

    function set_cached_html($pagename, $data) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        if (empty($data)) $data = '';
        $sth = $dbh->prepare("UPDATE $page_tbl"
                             . " SET cached_html=?"
                             . " WHERE pagename=?"
                             . " LIMIT 1");
        $sth->bindParam(1, $data, PDO_PARAM_STR);
        $sth->bindParam(2, $pagename, PDO_PARAM_STR, 100);
        $sth->execute();
    }

    function _get_pageid($pagename, $create_if_missing = false) {
        // check id_cache
        global $request;
        $cache =& $request->_dbi->_cache->_id_cache;
        if (isset($cache[$pagename])) {
            if ($cache[$pagename] or !$create_if_missing) {
                return $cache[$pagename];
            }
        }
        
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        $sth = $dbh->prepare("SELECT id FROM $page_tbl WHERE pagename=? LIMIT 1");
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        $id = $sth->fetchSingle();
        if (! $create_if_missing ) {
            return $id;
        }
        if (! $id ) {
            //mysql, mysqli or mysqlt
            if (substr($dbh->databaseType,0,5) == 'mysql') {
                // have auto-incrementing, atomic version
                $sth = $dbh->prepare("INSERT INTO $page_tbl"
                                     . " (id,pagename)"
                                     . " VALUES (NULL,?)");
                $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
                $sth->execute();
                $id = $dbh->lastInsertId();
            } else {
                $this->beginTransaction();
                $sth = $dbh->prepare("SELECT MAX(id) FROM $page_tbl");
                $sth->execute();
                $id = $sth->fetchSingle();
                $sth = $dbh->prepare("INSERT INTO $page_tbl"
                                     . " (id,pagename,hits)"
                                     . " VALUES (?,?,0)");
                $id++;
                $sth->bindParam(1, $id, PDO_PARAM_INT);
                $sth->bindParam(2, $pagename, PDO_PARAM_STR, 100);
                if ($sth->execute())
                    $this->commit();
                else 
                    $this->rollBack();
            }
        }
        assert($id);
        return $id;
    }

    function get_latest_version($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $sth = $dbh->prepare("SELECT latestversion"
                             . " FROM $page_tbl, $recent_tbl"
                             . " WHERE $page_tbl.id=$recent_tbl.id"
                             . "  AND pagename=?"
                             . " LIMIT 1");
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        $sth->execute();
        return $sth->fetchSingle();
    }

    function get_previous_version($pagename, $version) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $sth = $dbh->prepare("SELECT version"
                             . " FROM $version_tbl, $page_tbl"
                             . " WHERE $version_tbl.id=$page_tbl.id"
                             . "  AND pagename=?"
                             . "  AND version < ?"
                             . " ORDER BY version DESC"
                             . " LIMIT 1");
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        $sth->bindParam(2, $version, PDO_PARAM_INT);
        $sth->execute();
        return $sth->fetchSingle();
    }
    
    /**
     * Get version data.
     *
     * @param $version int Which version to get.
     *
     * @return hash The version data, or false if specified version does not
     *              exist.
     */
    function get_versiondata($pagename, $version, $want_content = false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        extract($this->_expressions);
                
        assert(is_string($pagename) and $pagename != '');
        assert($version > 0);
        
        // FIXME: optimization: sometimes don't get page data?
        if ($want_content) {
            $fields = $this->page_tbl_fields . ", $page_tbl.pagedata AS pagedata"
                . ', ' . $this->version_tbl_fields;
        } else {
            $fields = $this->page_tbl_fields . ", '' AS pagedata"
                . ", $version_tbl.version AS version, $version_tbl.mtime AS mtime, "
                . "$version_tbl.minor_edit AS minor_edit, $iscontent AS have_content, "
                . "$version_tbl.versiondata as versiondata";
        }
        $sth = $dbh->prepare("SELECT $fields"
                             . " FROM $page_tbl, $version_tbl"
                             . " WHERE $page_tbl.id=$version_tbl.id"
                             . "  AND pagename=?"
                             . "  AND version=?"
                             . " LIMIT 1");
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        $sth->bindParam(2, $version, PDO_PARAM_INT);
        $sth->execute();
        $row = $sth->fetch(PDO_FETCH_NUM);
        return $row ? $this->_extract_version_data_num($row, $want_content) : false;
    }

    function _extract_version_data_num($row, $want_content) {
        if (!$row)
            return false;

        //$id       &= $row[0];
        //$pagename &= $row[1];
        $data = empty($row[8]) ? array() : $this->_unserialize($row[8]);
        $data['mtime']         = $row[5];
        $data['is_minor_edit'] = !empty($row[6]);
        if ($want_content) {
            $data['%content'] = $row[7];
        } else {
            $data['%content'] = !empty($row[7]);
        }
        if (!empty($row[3])) {
            $data['%pagedata'] = $this->_extract_page_data($row[3], $row[2]);
        }
        return $data;
    }

    function _extract_version_data_assoc($row) {
        if (!$row)
            return false;

        extract($row);
        $data = empty($versiondata) ? array() : $this->_unserialize($versiondata);
        $data['mtime'] = $mtime;
        $data['is_minor_edit'] = !empty($minor_edit);
        if (isset($content))
            $data['%content'] = $content;
        elseif ($have_content)
            $data['%content'] = true;
        else
            $data['%content'] = '';
        if (!empty($pagedata)) {
            $data['%pagedata'] = $this->_extract_page_data($pagedata, $hits);
        }
        return $data;
    }

    /**
     * Create a new revision of a page.
     */
    function set_versiondata($pagename, $version, $data) {
        $dbh = &$this->_dbh;
        $version_tbl = $this->_table_names['version_tbl'];
        
        $minor_edit = (int) !empty($data['is_minor_edit']);
        unset($data['is_minor_edit']);
        
        $mtime = (int)$data['mtime'];
        unset($data['mtime']);
        assert(!empty($mtime));

        @$content = (string) $data['%content'];
        unset($data['%content']);
        unset($data['%pagedata']);
        
        $this->lock(array('page','recent','version','nonempty'));
        $this->beginTransaction();
        $id = $this->_get_pageid($pagename, true);
        $backend_type = $this->backendType();
        // optimize: mysql can do this with one REPLACE INTO.
        if (substr($backend_type,0,5) == 'mysql') {
            $sth = $dbh->prepare("REPLACE INTO $version_tbl"
                                 . " (id,version,mtime,minor_edit,content,versiondata)"
                                 . " VALUES(?,?,?,?,?,?)");
            $sth->bindParam(1, $id, PDO_PARAM_INT);
            $sth->bindParam(2, $version, PDO_PARAM_INT);
            $sth->bindParam(3, $mtime, PDO_PARAM_INT);
            $sth->bindParam(4, $minor_edit, PDO_PARAM_INT);
            $sth->bindParam(5, $content, PDO_PARAM_STR, 100);
            $sth->bindParam(6, $this->_serialize($data), PDO_PARAM_STR, 100);
            $rs = $sth->execute();
        } else {
            $sth = $dbh->prepare("DELETE FROM $version_tbl"
                                 . " WHERE id=? AND version=?");
            $sth->bindParam(1, $id, PDO_PARAM_INT);
            $sth->bindParam(2, $version, PDO_PARAM_INT);
            $sth->execute();
            $sth = $dbh->prepare("INSERT INTO $version_tbl"
                                . " (id,version,mtime,minor_edit,content,versiondata)"
                                 . " VALUES(?,?,?,?,?,?)");
            $sth->bindParam(1, $id, PDO_PARAM_INT);
            $sth->bindParam(2, $version, PDO_PARAM_INT);
            $sth->bindParam(3, $mtime, PDO_PARAM_INT);
            $sth->bindParam(4, $minor_edit, PDO_PARAM_INT);
            $sth->bindParam(5, $content, PDO_PARAM_STR, 100);
            $sth->bindParam(6, $this->_serialize($data), PDO_PARAM_STR, 100);
            $rs = $sth->execute();
        }
        $this->_update_recent_table($id);
        $this->_update_nonempty_table($id);
        if ($rs) $this->commit( );
        else $this->rollBack( );
        $this->unlock(array('page','recent','version','nonempty'));
    }
    
    /**
     * Delete an old revision of a page.
     */
    function delete_versiondata($pagename, $version) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $this->lock(array('version'));
        if ( ($id = $this->_get_pageid($pagename)) ) {
            $dbh->query("DELETE FROM $version_tbl"
                        . " WHERE id=$id AND version=$version");
            $this->_update_recent_table($id);
            // This shouldn't be needed (as long as the latestversion
            // never gets deleted.)  But, let's be safe.
            $this->_update_nonempty_table($id);
        }
        $this->unlock(array('version'));
    }

    /**
     * Delete page from the database with backup possibility.
     * i.e save_page('') and DELETE nonempty id
     * 
     * deletePage increments latestversion in recent to a non-existent version, 
     * and removes the nonempty row,
     * so that get_latest_version returns id+1 and get_previous_version returns prev id 
     * and page->exists returns false.
     */
    function delete_page($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $this->beginTransaction();
        //$dbh->CommitLock($recent_tbl);
        if (($id = $this->_get_pageid($pagename, false)) === false) {
            $this->rollback( );
            return false;
        }
        $mtime = time();
        $user =& $GLOBALS['request']->_user;
        $meta = array('author' => $user->getId(),
                      'author_id' => $user->getAuthenticatedId(),
                      'mtime' => $mtime);
        $this->lock(array('version','recent','nonempty','page','link'));
        $version = $this->get_latest_version($pagename);
        if ($dbh->query("UPDATE $recent_tbl SET latestversion=latestversion+1,"
                        . "latestmajor=latestversion+1,latestminor=NULL WHERE id=$id")) 
        {
            $insert = $dbh->prepare("INSERT INTO $version_tbl"
                                    . " (id,version,mtime,minor_edit,content,versiondata)"
                                    . " VALUES(?,?,?,?,?,?)");
            $insert->bindParam(1, $id, PDO_PARAM_INT);
            $insert->bindParam(2, $version + 1, PDO_PARAM_INT);
            $insert->bindParam(3, $mtime, PDO_PARAM_INT);
            $insert->bindParam(4, 0, PDO_PARAM_INT);
            $insert->bindParam(5, '', PDO_PARAM_STR, 100);
            $insert->bindParam(6, $this->_serialize($meta), PDO_PARAM_STR, 100);
            if ($insert->execute()
                and $dbh->query("DELETE FROM $nonempty_tbl WHERE id=$id")
                and $this->set_links($pagename, false)) {
                    // need to keep perms and LOCKED, otherwise you can reset the perm 
                    // by action=remove and re-create it with default perms
                    // keep hits but delete meta-data 
                    //and $dbh->Execute("UPDATE $page_tbl SET pagedata='' WHERE id=$id") 
                $this->unlock(array('version','recent','nonempty','page','link'));
                $this->commit();
                return true;
            }
        } else {
            $this->unlock(array('version','recent','nonempty','page','link'));	
            $this->rollBack();
            return false;
        }
    }

    function purge_page($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        
        $this->lock(array('version','recent','nonempty','page','link'));
        if ( ($id = $this->_get_pageid($pagename, false)) ) {
            $dbh->query("DELETE FROM $version_tbl  WHERE id=$id");
            $dbh->query("DELETE FROM $recent_tbl   WHERE id=$id");
            $dbh->query("DELETE FROM $nonempty_tbl WHERE id=$id");
            $this->set_links($pagename, false);
            $sth = $dbh->prepare("SELECT COUNT(*) FROM $link_tbl WHERE linkto=$id");
            $sth->execute();
            if ($sth->fetchSingle()) {
                // We're still in the link table (dangling link) so we can't delete this
                // altogether.
                $dbh->query("UPDATE $page_tbl SET hits=0, pagedata='' WHERE id=$id");
                $result = 0;
            }
            else {
                $dbh->query("DELETE FROM $page_tbl WHERE id=$id");
                $result = 1;
            }
        } else {
            $result = -1; // already purged or not existing
        }
        $this->unlock(array('version','recent','nonempty','page','link'));
        return $result;
    }


    // The only thing we might be interested in updating which we can
    // do fast in the flags (minor_edit).   I think the default
    // update_versiondata will work fine...
    //function update_versiondata($pagename, $version, $data) {
    //}

    function set_links($pagename, $links) {
        // Update link table.
        // FIXME: optimize: mysql can do this all in one big INSERT/REPLACE.

        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $this->lock(array('link'));
        $pageid = $this->_get_pageid($pagename, true);

        if ($links) {
            $dbh->query("DELETE FROM $link_tbl WHERE linkfrom=$pageid");
            foreach ($links as $link) {
                if (isset($linkseen[$link]))
                    continue;
                $linkseen[$link] = true;
                $linkid = $this->_get_pageid($link, true);
                assert($linkid);
                $dbh->query("INSERT INTO $link_tbl (linkfrom, linkto)"
                            . " VALUES ($pageid, $linkid)");
            }
        } elseif (DEBUG) {
            // purge page table: delete all non-referenced pages
            // for all previously linked pages...
            $sth = $dbh->prepare("SELECT $link_tbl.linkto as id FROM $link_tbl".
                                 " WHERE linkfrom=$pageid");
            $sth->execute();
            foreach ($sth->fetchAll(PDO_FETCH_NUM) as $id) {
            	// ...check if the page is empty and has no version
                $sth1 = $dbh->prepare("SELECT $page_tbl.id FROM $page_tbl"
                                      . " LEFT JOIN $nonempty_tbl USING (id) "
                                      . " LEFT JOIN $version_tbl USING (id)"
                                      . " WHERE ISNULL($nonempty_tbl.id) AND"
                                      . " ISNULL($version_tbl.id) AND $page_tbl.id=$id");
                $sth1->execute();
                if ($sth1->fetchSingle()) {
                    $dbh->query("DELETE FROM $page_tbl WHERE id=$id");   // this purges the link
                    $dbh->query("DELETE FROM $recent_tbl WHERE id=$id"); // may fail
                }
            }
            $dbh->query("DELETE FROM $link_tbl WHERE linkfrom=$pageid");
        }
        $this->unlock(array('link'));
        return true;
    }
    
    /**
     * Find pages which link to or are linked from a page.
     *
     * Optimization: save request->_dbi->_iwpcache[] to avoid further iswikipage checks
     * (linkExistingWikiWord or linkUnknownWikiWord)
     * This is called on every page header GleanDescription, so we can store all the existing links.
     */
    function get_links($pagename, $reversed=true, $include_empty=false,
                       $sortby=false, $limit=false, $exclude='') {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        if ($reversed)
            list($have,$want) = array('linkee', 'linker');
        else
            list($have,$want) = array('linker', 'linkee');
        $orderby = $this->sortby($sortby, 'db', array('pagename'));
        if ($orderby) $orderby = ' ORDER BY $want.' . $orderby;
        if ($exclude) // array of pagenames
            $exclude = " AND $want.pagename NOT IN ".$this->_sql_set($exclude);
        else 
            $exclude='';
        $limit = $this->_limit_sql($limit);

        $sth = $dbh->prepare("SELECT $want.id AS id, $want.pagename AS pagename,"
                             . " $want.hits AS hits"
                             . " FROM $link_tbl, $page_tbl linker, $page_tbl linkee"
                             . (!$include_empty ? ", $nonempty_tbl" : '')
                             . " WHERE linkfrom=linker.id AND linkto=linkee.id"
                             . " AND $have.pagename=?"
                             . (!$include_empty ? " AND $nonempty_tbl.id=$want.id" : "")
                             //. " GROUP BY $want.id"
                             . $exclude
                             . $orderby
                             . $limit);
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        $sth->execute();
        $result = $sth->fetch(PDO_FETCH_BOTH);
        return new WikiDB_backend_PDO_iter($this, $result, $this->page_tbl_field_list);
    }

    /**
     * Find if a page links to another page
     */
    function exists_link($pagename, $link, $reversed=false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        if ($reversed)
            list($have, $want) = array('linkee', 'linker');
        else
            list($have, $want) = array('linker', 'linkee');
        $sth = $dbh->prepare("SELECT IF($want.pagename,1,0)"
                             . " FROM $link_tbl, $page_tbl linker, $page_tbl linkee, $nonempty_tbl"
                             . " WHERE linkfrom=linker.id AND linkto=linkee.id"
                             . " AND $have.pagename=?"
                             . " AND $want.pagename=?"
                             . "LIMIT 1");
        $sth->bindParam(1, $pagename, PDO_PARAM_STR, 100);
        $sth->bindParam(2, $link, PDO_PARAM_STR, 100);
        $sth->execute();
        return $sth->fetchSingle();
    }

    function get_all_pages($include_empty=false, $sortby=false, $limit=false, $exclude='') {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $orderby = $this->sortby($sortby, 'db');
        if ($orderby) $orderby = ' ORDER BY ' . $orderby;
        if ($exclude) // array of pagenames
            $exclude = " AND $page_tbl.pagename NOT IN ".$this->_sql_set($exclude);
        else 
            $exclude='';
        $limit = $this->_limit_sql($limit);

        if (strstr($orderby, 'mtime ')) { // was ' mtime'
            if ($include_empty) {
                $sql = "SELECT "
                    . $this->page_tbl_fields
                    ." FROM $page_tbl, $recent_tbl, $version_tbl"
                    . " WHERE $page_tbl.id=$recent_tbl.id"
                    . " AND $page_tbl.id=$version_tbl.id AND latestversion=version"
                    . $exclude
                    . $orderby;
            }
            else {
                $sql = "SELECT "
                    . $this->page_tbl_fields
                    . " FROM $nonempty_tbl, $page_tbl, $recent_tbl, $version_tbl"
                    . " WHERE $nonempty_tbl.id=$page_tbl.id"
                    . " AND $page_tbl.id=$recent_tbl.id"
                    . " AND $page_tbl.id=$version_tbl.id AND latestversion=version"
                    . $exclude
                    . $orderby;
            }
        } else {
            if ($include_empty) {
                $sql = "SELECT "
                    . $this->page_tbl_fields
                    . " FROM $page_tbl"
                    . ($exclude ? " WHERE $exclude" : '')
                    . $orderby;
            } else {
                $sql = "SELECT "
                    . $this->page_tbl_fields
                    . " FROM $nonempty_tbl, $page_tbl"
                    . " WHERE $nonempty_tbl.id=$page_tbl.id"
                    . $exclude
                    . $orderby;
            }
        }
        $sth = $dbh->prepare($sql . $limit); 
        $sth->execute();
        $result = $sth->fetch(PDO_FETCH_BOTH);
        return new WikiDB_backend_PDO_iter($this, $result, $this->page_tbl_field_list);
    }
        
    /**
     * Title search.
     */
    function text_search($search, $fullsearch=false, $sortby=false, $limit=false, $exclude=false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $orderby = $this->sortby($sortby, 'db');
        if ($orderby) $orderby = ' ORDER BY ' . $orderby;
        $limit = $this->_limit_sql($limit);

        $table = "$nonempty_tbl, $page_tbl";
        $join_clause = "$nonempty_tbl.id=$page_tbl.id";
        $fields = $this->page_tbl_fields;
        $field_list = $this->page_tbl_field_list;
        $searchobj = new WikiDB_backend_PDO_search($search, $dbh);
        
        if ($fullsearch) {
            $table .= ", $recent_tbl";
            $join_clause .= " AND $page_tbl.id=$recent_tbl.id";

            $table .= ", $version_tbl";
            $join_clause .= " AND $page_tbl.id=$version_tbl.id AND latestversion=version";

            $fields .= ",$page_tbl.pagedata as pagedata," . $this->version_tbl_fields;
            $field_list = array_merge($field_list, array('pagedata'), $this->version_tbl_field_list);
            $callback = new WikiMethodCb($searchobj, "_fulltext_match_clause");
        } else {
            $callback = new WikiMethodCb($searchobj, "_pagename_match_clause");
        }
        
        $search_clause = $search->makeSqlClauseObj($callback);
        $sth = $dbh->prepare("SELECT $fields FROM $table"
                             . " WHERE $join_clause"
                             . " AND ($search_clause)"
                             . $orderby
                             . $limit);
        $sth->execute();
        $result = $sth->fetch(PDO_FETCH_NUM);
        $iter = new WikiDB_backend_PDO_iter($this, $result, $field_list);
        $iter->stoplisted = $searchobj->stoplisted;
        return $iter;
    }

    /*
     * TODO: efficiently handle wildcards exclusion: exclude=Php* => 'Php%', 
     *       not sets. See above, but the above methods find too much. 
     * This is only for already resolved wildcards:
     * " WHERE $page_tbl.pagename NOT IN ".$this->_sql_set(array('page1','page2'));
     */
    function _sql_set(&$pagenames) {
        $s = '(';
        foreach ($pagenames as $p) {
            $s .= ($this->_dbh->qstr($p).",");
        }
        return substr($s,0,-1).")";
    }

    /**
     * Find highest or lowest hit counts.
     */
    function most_popular($limit=20, $sortby='-hits') {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $order = "DESC";
        if ($limit < 0){ 
            $order = "ASC"; 
            $limit = -$limit;
            $where = "";
        } else {
            $where = " AND hits > 0";
        }
        if ($sortby != '-hits') {
            if ($order = $this->sortby($sortby, 'db'))  $orderby = " ORDER BY " . $order;
            else $orderby = "";
        } else
            $orderby = " ORDER BY hits $order";
        $sql = "SELECT " 
            . $this->page_tbl_fields
            . " FROM $nonempty_tbl, $page_tbl"
            . " WHERE $nonempty_tbl.id=$page_tbl.id"
            . $where
            . $orderby;
        if ($limit) {
            $sth = $dbh->prepare($sql . $this->_limit_sql($limit));
        } else {
            $sth = $dbh->prepare($sql);
        }
        $sth->execute();
        $result = $sth->fetch(PDO_FETCH_NUM);
        return new WikiDB_backend_PDO_iter($this, $result, $this->page_tbl_field_list);
    }

    /**
     * Find recent changes.
     */
    function most_recent($params) {
        $limit = 0;
        $since = 0;
        $include_minor_revisions = false;
        $exclude_major_revisions = false;
        $include_all_revisions = false;
        extract($params);

        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $pick = array();
        if ($since)
            $pick[] = "mtime >= $since";
        
        if ($include_all_revisions) {
            // Include all revisions of each page.
            $table = "$page_tbl, $version_tbl";
            $join_clause = "$page_tbl.id=$version_tbl.id";

            if ($exclude_major_revisions) {
                // Include only minor revisions
                $pick[] = "minor_edit <> 0";
            }
            elseif (!$include_minor_revisions) {
                // Include only major revisions
                $pick[] = "minor_edit = 0";
            }
        }
        else {
            $table = "$page_tbl, $recent_tbl";
            $join_clause = "$page_tbl.id=$recent_tbl.id";
            $table .= ", $version_tbl";
            $join_clause .= " AND $version_tbl.id=$page_tbl.id";
                
            if ($exclude_major_revisions) {
                // Include only most recent minor revision
                $pick[] = 'version=latestminor';
            }
            elseif (!$include_minor_revisions) {
                // Include only most recent major revision
                $pick[] = 'version=latestmajor';
            }
            else {
                // Include only the latest revision (whether major or minor).
                $pick[] ='version=latestversion';
            }
        }
        $order = "DESC";
        if($limit < 0){
            $order = "ASC";
            $limit = -$limit;
        }
        $where_clause = $join_clause;
        if ($pick)
            $where_clause .= " AND " . join(" AND ", $pick);
        $sql = "SELECT "
            . $this->page_tbl_fields . ", " . $this->version_tbl_fields
            . " FROM $table"
            . " WHERE $where_clause"
            . " ORDER BY mtime $order";
        if ($limit) {
            $sth = $dbh->prepare($sql . $this->_limit_sql($limit));
        } else {
            $sth = $dbh->prepare($sql);
        }
        $sth->execute();
        $result = $sth->fetch(PDO_FETCH_NUM);
        return new WikiDB_backend_PDO_iter($this, $result, 
            array_merge($this->page_tbl_field_list, $this->version_tbl_field_list));
    }

    /**
     * Find referenced empty pages.
     */
    function wanted_pages($exclude_from='', $exclude='', $sortby=false, $limit=false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        if ($orderby = $this->sortby($sortby, 'db', array('pagename','wantedfrom')))
            $orderby = 'ORDER BY ' . $orderby;
            
        if ($exclude_from) // array of pagenames
            $exclude_from = " AND linked.pagename NOT IN ".$this->_sql_set($exclude_from);
        if ($exclude) // array of pagenames
            $exclude = " AND $page_tbl.pagename NOT IN ".$this->_sql_set($exclude);

        /* 
         all empty pages, independent of linkstatus:
           select pagename as empty from page left join nonempty using(id) where isnull(nonempty.id);
         only all empty pages, which have a linkto:
           select page.pagename, linked.pagename as wantedfrom from link, page as linked 
             left join page on(link.linkto=page.id) left join nonempty on(link.linkto=nonempty.id) 
             where isnull(nonempty.id) and linked.id=link.linkfrom;  
        */
        $sql = "SELECT $page_tbl.pagename,linked.pagename as wantedfrom"
            . " FROM $link_tbl,$page_tbl as linked "
            . " LEFT JOIN $page_tbl ON($link_tbl.linkto=$page_tbl.id)"
            . " LEFT JOIN $nonempty_tbl ON($link_tbl.linkto=$nonempty_tbl.id)" 
            . " WHERE ISNULL($nonempty_tbl.id) AND linked.id=$link_tbl.linkfrom"
            . $exclude_from
            . $exclude
            . $orderby;
        if ($limit) {
            $sth = $dbh->prepare($sql . $this->_limit_sql($limit));
        } else {
            $sth = $dbh->prepare($sql);
        }
        $sth->execute();
        $result = $sth->fetch(PDO_FETCH_NUM);
        return new WikiDB_backend_PDO_iter($this, $result, array('pagename','wantedfrom'));
    }

    /**
     * Rename page in the database.
     */
    function rename_page($pagename, $to) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        
        $this->lock(array('page','version','recent','nonempty','link'));
        if ( ($id = $this->_get_pageid($pagename, false)) ) {
            if ($new = $this->_get_pageid($to, false)) {
                // Cludge Alert!
                // This page does not exist (already verified before), but exists in the page table.
                // So we delete this page.
                $dbh->query("DELETE FROM $page_tbl WHERE id=$new");
                $dbh->query("DELETE FROM $version_tbl WHERE id=$new");
                $dbh->query("DELETE FROM $recent_tbl WHERE id=$new");
                $dbh->query("DELETE FROM $nonempty_tbl WHERE id=$new");
                // We have to fix all referring tables to the old id
                $dbh->query("UPDATE $link_tbl SET linkfrom=$id WHERE linkfrom=$new");
                $dbh->query("UPDATE $link_tbl SET linkto=$id WHERE linkto=$new");
            }
            $sth = $dbh->prepare("UPDATE $page_tbl SET pagename=? WHERE id=?");
            $sth->bindParam(1, $to, PDO_PARAM_STR, 100);
            $sth->bindParam(2, $id, PDO_PARAM_INT);
            $sth->execute();
        }
        $this->unlock(array('page'));
        return $id;
    }

    function _update_recent_table($pageid = false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        extract($this->_expressions);

        $pageid = (int)$pageid;

        // optimize: mysql can do this with one REPLACE INTO.
        $backend_type = $this->backendType();
        if (substr($backend_type,0,5) == 'mysql') {
            $sth = $dbh->prepare("REPLACE INTO $recent_tbl"
                                 . " (id, latestversion, latestmajor, latestminor)"
                                 . " SELECT id, $maxversion, $maxmajor, $maxminor"
                                 . " FROM $version_tbl"
                                 . ( $pageid ? " WHERE id=$pageid" : "")
                                 . " GROUP BY id" );
            $sth->execute();
        } else {
            $this->lock(array('recent'));
            $sth = $dbh->prepare("DELETE FROM $recent_tbl"
                                 . ( $pageid ? " WHERE id=$pageid" : ""));
            $sth->execute();
            $sth = $dbh->prepare( "INSERT INTO $recent_tbl"
                                  . " (id, latestversion, latestmajor, latestminor)"
                                  . " SELECT id, $maxversion, $maxmajor, $maxminor"
                                  . " FROM $version_tbl"
                                  . ( $pageid ? " WHERE id=$pageid" : "")
                                  . " GROUP BY id" );
            $sth->execute();
            $this->unlock(array('recent'));
        }
    }

    function _update_nonempty_table($pageid = false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        extract($this->_expressions);

        $pageid = (int)$pageid;

        extract($this->_expressions);
        $this->lock(array('nonempty'));
        $dbh->query("DELETE FROM $nonempty_tbl"
                    . ( $pageid ? " WHERE id=$pageid" : ""));
        $dbh->query("INSERT INTO $nonempty_tbl (id)"
                    . " SELECT $recent_tbl.id"
                    . " FROM $recent_tbl, $version_tbl"
                    . " WHERE $recent_tbl.id=$version_tbl.id"
                    . "       AND version=latestversion"
                    // We have some specifics here (Oracle)
                    //. "  AND content<>''"
                    . "  AND content $notempty"
                    . ( $pageid ? " AND $recent_tbl.id=$pageid" : ""));
        $this->unlock(array('nonempty'));
    }

    /**
     * Grab a write lock on the tables in the SQL database.
     *
     * Calls can be nested.  The tables won't be unlocked until
     * _unlock_database() is called as many times as _lock_database().
     *
     * @access protected
     */
    function lock($tables, $write_lock = true) {
        if ($this->_lock_count++ == 0) {
            $this->_current_lock = $tables;
            if (!$this->_hasTransactions)
                $this->_lock_tables($tables, $write_lock);
        }
    }

    /**
     * Overridden by non-transaction safe backends.
     */
    function _lock_tables($tables, $write_lock) {
        $lock_type = $write_lock ? "WRITE" : "READ";
        foreach ($this->_table_names as $key => $table) {
            $locks[] = "$table $lock_type";
        }
        $this->_dbh->query("LOCK TABLES " . join(",", $locks));
    }
    
    /**
     * Release a write lock on the tables in the SQL database.
     *
     * @access protected
     *
     * @param $force boolean Unlock even if not every call to lock() has been matched
     * by a call to unlock().
     *
     * @see _lock_database
     */
    function unlock($tables = false, $force = false) {
        if ($this->_lock_count == 0) {
            $this->_current_lock = false;
            return;
        }
        if (--$this->_lock_count <= 0 || $force) {
            if (!$this->_hasTransactions)
                $this->_unlock_tables($tables);
            $this->_current_lock = false;
            $this->_lock_count = 0;
        }
    }

    /**
     * overridden by non-transaction safe backends
     */
    function _unlock_tables($tables) {
        $this->_dbh->query("UNLOCK TABLES");
    }

    /**
     * Serialize data
     */
    function _serialize($data) {
        if (empty($data))
            return '';
        assert(is_array($data));
        return serialize($data);
    }

    /**
     * Unserialize data
     */
    function _unserialize($data) {
        return empty($data) ? array() : unserialize($data);
    }

    /* some variables and functions for DB backend abstraction (action=upgrade) */
    function database () {
        return $this->_dbh->database;
    }
    function backendType() {
        return $this->_dbh->databaseType;
    }
    function connection() {
        trigger_error("PDO: connectionID unsupported", E_USER_ERROR);
        return false;
    }
    function listOfTables() {
        trigger_error("PDO: virtual listOfTables", E_USER_ERROR);
        return array();
    }
    function listOfFields($database, $table) {
        trigger_error("PDO: virtual listOfFields", E_USER_ERROR);
        return array();
    }

    /*
     * LIMIT with OFFSET is not SQL specified. 
     *   mysql: LIMIT $offset, $count
     *   pgsql,sqlite: LIMIT $count OFFSET $offset
     *   InterBase,FireBird: ROWS $offset TO $last
     *   mssql: TOP $rows => TOP $last
     *   oci8: ROWNUM
     *   IBM DB2: FetchFirst
     * See http://search.cpan.org/dist/SQL-Abstract-Limit/lib/SQL/Abstract/Limit.pm

     SELECT field_list FROM $table X WHERE where_clause AND
     (
         SELECT COUNT(*) FROM $table WHERE $pk > X.$pk
     )
     BETWEEN $offset AND $last
     ORDER BY $pk $asc_desc
     */
    function _limit_sql($limit = false) {
        if ($limit) {
            list($offset, $count) = $this->limit($limit);
            if ($offset) {
                $limit = " LIMIT $count"; 
                trigger_error("unsupported OFFSET in SQL ignored", E_USER_WARNING);
            } else
                $limit = " LIMIT $count"; 
        } else
            $limit = '';
        return $limit;
    }
};

class WikiDB_backend_PDO_generic_iter
extends WikiDB_backend_iterator
{
    function WikiDB_backend_PDO_generic_iter($backend, $query_result, $field_list = NULL) {
        $this->_backend = &$backend;
        $this->_result = $query_result;
        //$this->_fields = $field_list;
    }
    
    function count() {
        if (!is_object($this->_result)) {
            return false;
        }
        $count = $this->_result->rowCount();
        return $count;
    }

    function next() {
        $result = &$this->_result;
        if (!is_object($result)) {
            return false;
        }
        return $result->fetch(PDO_FETCH_BOTH);
    }

    function free () {
        if ($this->_result) {
            unset($this->_result);
        }
    }
}

class WikiDB_backend_PDO_iter
extends WikiDB_backend_PDO_generic_iter
{
    function next() {
        $result = &$this->_result;
        if (!is_object($result)) {
            return false;
        }
        $this->_backend = &$backend;
        $rec = $result->fetch(PDO_FETCH_ASSOC);

        if (isset($rec['pagedata']))
            $rec['pagedata'] = $backend->_extract_page_data($rec['pagedata'], $rec['hits']);
        if (!empty($rec['version'])) {
            $rec['versiondata'] = $backend->_extract_version_data_assoc($rec);
        }
        return $rec;
    }
}

class WikiDB_backend_PDO_search extends WikiDB_backend_search_sql {}

// Following function taken from Pear::DB (prev. from adodb-pear.inc.php).
// Eventually, change index.php to provide the relevant information
// directly?
    /**
     * Parse a data source name.
     *
     * Additional keys can be added by appending a URI query string to the
     * end of the DSN.
     *
     * The format of the supplied DSN is in its fullest form:
     * <code>
     *  phptype(dbsyntax)://username:password@protocol+hostspec/database?option=8&another=true
     * </code>
     *
     * Most variations are allowed:
     * <code>
     *  phptype://username:password@protocol+hostspec:110//usr/db_file.db?mode=0644
     *  phptype://username:password@hostspec/database_name
     *  phptype://username:password@hostspec
     *  phptype://username@hostspec
     *  phptype://hostspec/database
     *  phptype://hostspec
     *  phptype(dbsyntax)
     *  phptype
     * </code>
     *
     * @param string $dsn Data Source Name to be parsed
     *
     * @return array an associative array with the following keys:
     *  + phptype:  Database backend used in PHP (mysql, odbc etc.)
     *  + dbsyntax: Database used with regards to SQL syntax etc. (ignored with PDO)
     *  + protocol: Communication protocol to use (tcp, unix, pipe etc.)
     *  + hostspec: Host specification (hostname[:port])
     *  + database: Database to use on the DBMS server
     *  + username: User name for login
     *  + password: Password for login
     *
     * @author Tomas V.V.Cox <cox@idecnet.com>
     */
    function parseDSN($dsn)
    {
        $parsed = array(
            'phptype'  => false,
            'dbsyntax' => false,
            'username' => false,
            'password' => false,
            'protocol' => false,
            'hostspec' => false,
            'port'     => false,
            'socket'   => false,
            'database' => false,
        );

        if (is_array($dsn)) {
            $dsn = array_merge($parsed, $dsn);
            if (!$dsn['dbsyntax']) {
                $dsn['dbsyntax'] = $dsn['phptype'];
            }
            return $dsn;
        }

        // Find phptype and dbsyntax
        if (($pos = strpos($dsn, '://')) !== false) {
            $str = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 3);
        } else {
            $str = $dsn;
            $dsn = null;
        }

        // Get phptype and dbsyntax
        // $str => phptype(dbsyntax)
        if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
            $parsed['phptype']  = $arr[1];
            $parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
        } else {
            $parsed['phptype']  = $str;
            $parsed['dbsyntax'] = $str;
        }

        if (!count($dsn)) {
            return $parsed;
        }

        // Get (if found): username and password
        // $dsn => username:password@protocol+hostspec/database
        if (($at = strrpos($dsn,'@')) !== false) {
            $str = substr($dsn, 0, $at);
            $dsn = substr($dsn, $at + 1);
            if (($pos = strpos($str, ':')) !== false) {
                $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                $parsed['password'] = rawurldecode(substr($str, $pos + 1));
            } else {
                $parsed['username'] = rawurldecode($str);
            }
        }

        // Find protocol and hostspec

        // $dsn => proto(proto_opts)/database
        if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
            $proto       = $match[1];
            $proto_opts  = $match[2] ? $match[2] : false;
            $dsn         = $match[3];

        // $dsn => protocol+hostspec/database (old format)
        } else {
            if (strpos($dsn, '+') !== false) {
                list($proto, $dsn) = explode('+', $dsn, 2);
            }
            if (strpos($dsn, '/') !== false) {
                list($proto_opts, $dsn) = explode('/', $dsn, 2);
            } else {
                $proto_opts = $dsn;
                $dsn = null;
            }
        }

        // process the different protocol options
        $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
        $proto_opts = rawurldecode($proto_opts);
        if ($parsed['protocol'] == 'tcp') {
            if (strpos($proto_opts, ':') !== false) {
                list($parsed['hostspec'], $parsed['port']) = explode(':', $proto_opts);
            } else {
                $parsed['hostspec'] = $proto_opts;
            }
        } elseif ($parsed['protocol'] == 'unix') {
            $parsed['socket'] = $proto_opts;
        }

        // Get dabase if any
        // $dsn => database
        if ($dsn) {
            // /database
            if (($pos = strpos($dsn, '?')) === false) {
                $parsed['database'] = $dsn;
            // /database?param1=value1&param2=value2
            } else {
                $parsed['database'] = substr($dsn, 0, $pos);
                $dsn = substr($dsn, $pos + 1);
                if (strpos($dsn, '&') !== false) {
                    $opts = explode('&', $dsn);
                } else { // database?param1=value1
                    $opts = array($dsn);
                }
                foreach ($opts as $opt) {
                    list($key, $value) = explode('=', $opt);
                    if (!isset($parsed[$key])) {
                        // don't allow params overwrite
                        $parsed[$key] = rawurldecode($value);
                    }
                }
            }
        }

        return $parsed;
    }

// $Log: PDO.php,v $
// Revision 1.6  2005/11/14 22:24:33  rurban
// fix fulltext search,
// Eliminate stoplist words,
//
// Revision 1.5  2005/09/14 06:04:43  rurban
// optimize searching for ALL (ie %), use the stoplist on PDO
//
// Revision 1.4  2005/09/11 13:25:12  rurban
// enhance LIMIT support
//
// Revision 1.3  2005/09/10 21:30:16  rurban
// enhance titleSearch
//
// Revision 1.2  2005/02/11 14:45:45  rurban
// support ENABLE_LIVESEARCH, enable PDO sessions
//
// Revision 1.1  2005/02/10 19:01:22  rurban
// add PDO support
//

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>