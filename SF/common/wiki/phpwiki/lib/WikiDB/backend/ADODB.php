<?php // -*-php-*-
rcs_id('$Id: ADODB.php,v 1.78 2005/09/14 06:04:43 rurban Exp $');

/*
 Copyright 2002,2004,2005 $ThePhpWikiProgrammingTeam

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
 * Based on PearDB.php. 
 * @author: Lawrence Akka, Reini Urban
 *
 * Now (since phpwiki-1.3.10) with adodb-4.22, by Reini Urban:
 * 1) Extended to use all available database backends, not only mysql.
 * 2) It uses the ultra-fast binary adodb extension if loaded.
 * 3) We use FETCH_NUM instead of FETCH_ASSOC (faster and more generic)
 * 4) To support generic iterators which return ASSOC fields, and to support queries with 
 *    variable columns, some trickery was needed to use recordset specific fetchMode.
 *    The first Execute uses the global fetchMode (ASSOC), then it's resetted back to NUM 
 *    and the recordset fetchmode is set to ASSOC.
 * 5) Transaction support, and locking as fallback.
 * 6) 2004-12-10 added extra page.cached_html
 *
 * phpwiki-1.3.11, by Philippe Vanhaesendonck
 * - pass column list to iterators so we can FETCH_NUM in all cases
 * phpwiki-1.3.12: get rid of ISNULL
 * phpwiki-1.3.13: tsearch2 and stored procedures
 *
 * ADODB basic differences to PearDB: It pre-fetches the first row into fields, 
 * is dirtier in style, layout and more low-level ("worse is better").
 * It has less needed basic features (modifyQuery, locks, ...), but some more 
 * unneeded features included: paging, monitoring and sessions, and much more drivers.
 * No locking (which PearDB supports in some backends), and sequences are very 
 * bad compared to PearDB.

 * Old Comments, by Lawrence Akka:
 * 1)  ADODB's GetRow() is slightly different from that in PEAR.  It does not 
 *     accept a fetchmode parameter
 *     That doesn't matter too much here, since we only ever use FETCHMODE_ASSOC
 * 2)  No need for ''s around strings in sprintf arguments - qstr puts them 
 *     there automatically
 * 3)  ADODB has a version of GetOne, but it is difficult to use it when 
 *     FETCH_ASSOC is in effect.
 *     Instead, use $rs = Execute($query); $value = $rs->fields["$colname"]
 * 4)  No error handling yet - could use ADOConnection->raiseErrorFn
 * 5)  It used to be faster then PEAR/DB at the beginning of 2002. 
 *     Now at August 2002 PEAR/DB with our own page cache added, 
 *     performance is comparable.
 */

require_once('lib/WikiDB/backend.php');
// Error handling - calls trigger_error.  NB - does not close the connection.  Does it need to?
include_once('lib/WikiDB/adodb/adodb-errorhandler.inc.php');
// include the main adodb file
require_once('lib/WikiDB/adodb/adodb.inc.php');

class WikiDB_backend_ADODB
extends WikiDB_backend
{

    function WikiDB_backend_ADODB ($dbparams) {
        $parsed = parseDSN($dbparams['dsn']);
        $this->_dbparams = $dbparams;
        $this->_parsedDSN =& $parsed;
        $this->_dbh = &ADONewConnection($parsed['phptype']);
        if (DEBUG & _DEBUG_SQL) {
            $this->_dbh->debug = true;
            $GLOBALS['ADODB_OUTP'] = '_sql_debuglog';
        }
        $this->_dsn = $parsed;
        // persistent is defined as DSN option, or with a config value.
        //   phptype://username:password@hostspec/database?persistent=false
        if (!empty($parsed['persistent']) or DATABASE_PERSISTENT)
            $conn = $this->_dbh->PConnect($parsed['hostspec'],$parsed['username'], 
                                          $parsed['password'], $parsed['database']);
        else
            $conn = $this->_dbh->Connect($parsed['hostspec'],$parsed['username'], 
                                         $parsed['password'], $parsed['database']);

        // Since 1.3.10 we use the faster ADODB_FETCH_NUM,
        // with some ASSOC based recordsets.
        $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_NUM;
        $this->_dbh->SetFetchMode(ADODB_FETCH_NUM);
        $GLOBALS['ADODB_COUNTRECS'] = false;

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
            . "$page_tbl.hits AS hits";
        $this->links_field_list = array('id', 'pagename');
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
            $this->unlock(false,'force');
        }

        $this->_dbh->close();
        $this->_dbh = false;
    }

    /*
     * Fast test for wikipage.
     */
    function is_wiki_page($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $row = $dbh->GetRow(sprintf("SELECT $page_tbl.id AS id"
                                    . " FROM $nonempty_tbl, $page_tbl"
                                    . " WHERE $nonempty_tbl.id=$page_tbl.id"
                                    . "   AND pagename=%s",
                                    $dbh->qstr($pagename)));
        return $row ? $row[0] : false;
    }
        
    function get_all_pagenames() {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $result = $dbh->Execute("SELECT pagename"
                                . " FROM $nonempty_tbl, $page_tbl"
                                . " WHERE $nonempty_tbl.id=$page_tbl.id");
        return $result->GetArray();
    }

    /*
     * filter (nonempty pages) currently ignored
     */
    function numPages($filter=false, $exclude='') {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $result = $dbh->getRow("SELECT count(*)"
                            . " FROM $nonempty_tbl, $page_tbl"
                            . " WHERE $nonempty_tbl.id=$page_tbl.id");
        return $result[0];
    }

    function increaseHitCount($pagename) {
        $dbh = &$this->_dbh;
        // Hits is the only thing we can update in a fast manner.
        // Note that this will fail silently if the page does not
        // have a record in the page table.  Since it's just the
        // hit count, who cares?
        $dbh->Execute(sprintf("UPDATE %s SET hits=hits+1 WHERE pagename=%s",
                              $this->_table_names['page_tbl'],
                              $dbh->qstr($pagename)));
        return;
    }

    /**
     * Read page information from database.
     */
    function get_pagedata($pagename) {
        $dbh = &$this->_dbh;
        $row = $dbh->GetRow(sprintf("SELECT id,pagename,hits,pagedata FROM %s WHERE pagename=%s",
                                    $this->_table_names['page_tbl'],
                                    $dbh->qstr($pagename)));
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
            $dbh->Execute(sprintf("UPDATE $page_tbl SET hits=%d WHERE pagename=%s",
                                  $newdata['hits'], $dbh->qstr($pagename)));
            return;
        }
        $where = sprintf("pagename=%s", $dbh->qstr($pagename));
        $dbh->BeginTrans( );
        $dbh->RowLock($page_tbl,$where);
        
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
        if ($dbh->Execute("UPDATE $page_tbl"
                          . " SET hits=?, pagedata=?"
                          . " WHERE pagename=?",
                          array($hits, $this->_serialize($data), $pagename))) {
            $dbh->CommitTrans( );
            return true;
        } else {
            $dbh->RollbackTrans( );
            return false;
        }
    }

    function get_cached_html($pagename) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        $row = $dbh->GetRow(sprintf("SELECT cached_html FROM $page_tbl WHERE pagename=%s",
                                    $dbh->qstr($pagename)));
        return $row ? $row[0] : false;
    }

    function set_cached_html($pagename, $data) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        if (empty($data)) $data = '';
        $rs = $dbh->Execute("UPDATE $page_tbl"
                            . " SET cached_html=?"
                            . " WHERE pagename=?",
                            array($data, $pagename));
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
        $query = sprintf("SELECT id FROM $page_tbl WHERE pagename=%s",
                         $dbh->qstr($pagename));
        if (! $create_if_missing ) {
            $row = $dbh->GetRow($query);
            return $row ? $row[0] : false;
        }
        $row = $dbh->GetRow($query);
        if (! $row ) {
	    //$id = $dbh->GenID($page_tbl . 'seq');
	    // Better generic version than with adodob::genID
	    //TODO: Does the DBM has subselects? Then we can do it with select max(id)+1
	    $this->lock(array('page'));
	    $dbh->BeginTrans( );
	    $dbh->CommitLock($page_tbl);
	    $row = $dbh->GetRow("SELECT MAX(id) FROM $page_tbl");
	    $id = $row[0] + 1;
	    $rs = $dbh->Execute(sprintf("INSERT INTO $page_tbl"
					. " (id,pagename,hits)"
					. " VALUES (%d,%s,0)",
					$id, $dbh->qstr($pagename)));
	    if ($rs) $dbh->CommitTrans( );
	    else $dbh->RollbackTrans( );
	    $this->unlock(array('page'));
        } else {
            $id = $row[0];
        }
        assert($id);
        return $id;
    }

    function get_latest_version($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $row = $dbh->GetRow(sprintf("SELECT latestversion"
                                    . " FROM $page_tbl, $recent_tbl"
                                    . " WHERE $page_tbl.id=$recent_tbl.id"
                                    . "  AND pagename=%s",
                                    $dbh->qstr($pagename)));
        return $row ? (int)$row[0] : false;
    }

    function get_previous_version($pagename, $version) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        // Use SELECTLIMIT for maximum portability
        $rs = $dbh->SelectLimit(sprintf("SELECT version"
                                        . " FROM $version_tbl, $page_tbl"
                                        . " WHERE $version_tbl.id=$page_tbl.id"
                                        . "  AND pagename=%s"
                                        . "  AND version < %d"
                                        . " ORDER BY version DESC",
                                        $dbh->qstr($pagename),
                                        $version),
                                1);
        return $rs->fields ? (int)$rs->fields[0] : false;
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
        $row = $dbh->GetRow(sprintf("SELECT $fields"
                                    . " FROM $page_tbl, $version_tbl"
                                    . " WHERE $page_tbl.id=$version_tbl.id"
                                    . "  AND pagename=%s"
                                    . "  AND version=%d",
                                    $dbh->qstr($pagename), $version));
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
            // hmm, $pagedata = is already extracted by WikiDB_backend_ADODB_iter
            //$data['%pagedata'] = $this->_extract_page_data($pagedata, $hits);
            $data['%pagedata'] = $pagedata;
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
        $dbh->BeginTrans( );
        $dbh->CommitLock($version_tbl);
        $id = $this->_get_pageid($pagename, true);
        $backend_type = $this->backendType();
	$dbh->Execute(sprintf("DELETE FROM $version_tbl"
			      . " WHERE id=%d AND version=%d",
			      $id, $version));
	$rs = $dbh->Execute("INSERT INTO $version_tbl"
			    . " (id,version,mtime,minor_edit,content,versiondata)"
			    . " VALUES(?,?,?,?,?,?)",
			    array($id, $version, $mtime, $minor_edit,
                                  $content, $this->_serialize($data)));
        $this->_update_recent_table($id);
        $this->_update_nonempty_table($id);
        if ($rs) $dbh->CommitTrans( );
        else $dbh->RollbackTrans( );
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
            $dbh->Execute("DELETE FROM $version_tbl"
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

        $dbh->BeginTrans();
        $dbh->CommitLock($recent_tbl);
        if (($id = $this->_get_pageid($pagename, false)) === false) {
            $dbh->RollbackTrans( );
            return false;
        }
        $mtime = time();
        $user =& $GLOBALS['request']->_user;
        $meta = array('author' => $user->getId(),
                      'author_id' => $user->getAuthenticatedId(),
                      'mtime' => $mtime);
        $this->lock(array('version','recent','nonempty','page','link'));
        $version = $this->get_latest_version($pagename);
        if ($dbh->Execute("UPDATE $recent_tbl SET latestversion=latestversion+1,"
                          . "latestmajor=latestversion+1,latestminor=NULL WHERE id=$id")
            and $dbh->Execute("INSERT INTO $version_tbl"
                                . " (id,version,mtime,minor_edit,content,versiondata)"
                                . " VALUES(?,?,?,?,?,?)",
                                  array($id, $version+1, $mtime, 0,
                                        '', $this->_serialize($meta)))
            and $dbh->Execute("DELETE FROM $nonempty_tbl WHERE id=$id")
            and $this->set_links($pagename, false)
            // need to keep perms and LOCKED, otherwise you can reset the perm 
            // by action=remove and re-create it with default perms
            // keep hits but delete meta-data 
            //and $dbh->Execute("UPDATE $page_tbl SET pagedata='' WHERE id=$id") 
           )
        {
            $this->unlock(array('version','recent','nonempty','page','link'));	
            $dbh->CommitTrans( );
            return true;
        } else {
            $this->unlock(array('version','recent','nonempty','page','link'));	
            $dbh->RollbackTrans( );
            return false;
        }
    }

    function purge_page($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        
        $this->lock(array('version','recent','nonempty','page','link'));
        if ( ($id = $this->_get_pageid($pagename, false)) ) {
            $dbh->Execute("DELETE FROM $version_tbl  WHERE id=$id");
            $dbh->Execute("DELETE FROM $recent_tbl   WHERE id=$id");
            $dbh->Execute("DELETE FROM $nonempty_tbl WHERE id=$id");
            $this->set_links($pagename, false);
            $row = $dbh->GetRow("SELECT COUNT(*) FROM $link_tbl WHERE linkto=$id");
            if ($row and $row[0]) {
                // We're still in the link table (dangling link) so we can't delete this
                // altogether.
                $dbh->Execute("UPDATE $page_tbl SET hits=0, pagedata='' WHERE id=$id");
                $result = 0;
            }
            else {
                $dbh->Execute("DELETE FROM $page_tbl WHERE id=$id");
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

        if (1 or $links) {
            $dbh->Execute("DELETE FROM $link_tbl WHERE linkfrom=$pageid");
            foreach ($links as $link) {
                if (isset($linkseen[$link]))
                    continue;
                $linkseen[$link] = true;
                $linkid = $this->_get_pageid($link, true);
                assert($linkid);
                $dbh->Execute("INSERT INTO $link_tbl (linkfrom, linkto)"
                            . " VALUES ($pageid, $linkid)");
            }
        } elseif (0 and DEBUG) {
            // purge page table: delete all non-referenced pages
            // for all previously linked pages...
            foreach ($dbh->getRow("SELECT $link_tbl.linkto as id FROM $link_tbl".
                                  " WHERE linkfrom=$pageid") as $id) {
            	// ...check if the page is empty and has no version
                //FIXME: ISNULL is mysql specific
                if ($dbh->getRow("SELECT $page_tbl.id FROM $page_tbl"
                                 . " LEFT JOIN $nonempty_tbl USING (id) "
                                 . " LEFT JOIN $version_tbl USING (id)"
                                 . " WHERE $nonempty_tbl.id is NULL"
                                 . " AND $version_tbl.id is NULL"
                                 . " AND $page_tbl.id=$id")) {
                    $dbh->Execute("DELETE FROM $page_tbl WHERE id=$id");   // this purges the link
                    $dbh->Execute("DELETE FROM $recent_tbl WHERE id=$id"); // may fail
                }
            }
            $dbh->Execute("DELETE FROM $link_tbl WHERE linkfrom=$pageid");
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

        $qpagename = $dbh->qstr($pagename);
        // removed ref to FETCH_MODE in next line
        $sql = "SELECT $want.id AS id, $want.pagename AS pagename"
            . " FROM $link_tbl, $page_tbl linker, $page_tbl linkee"
            . (!$include_empty ? ", $nonempty_tbl" : '')
            . " WHERE linkfrom=linker.id AND linkto=linkee.id"
            . " AND $have.pagename=$qpagename"
            . (!$include_empty ? " AND $nonempty_tbl.id=$want.id" : "")
            //. " GROUP BY $want.id"
            . $exclude
            . $orderby;
        if ($limit) {
            // extract from,count from limit
            list($offset,$count) = $this->limit($limit);
            $result = $dbh->SelectLimit($sql, $count, $offset);
        } else {
            $result = $dbh->Execute($sql);
        }
        return new WikiDB_backend_ADODB_iter($this, $result, $this->links_field_list);
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
        $qpagename = $dbh->qstr($pagename);
        $qlink = $dbh->qstr($link);
        $row = $dbh->GetRow("SELECT IF($want.pagename,1,0)"
                            . " FROM $link_tbl, $page_tbl linker, $page_tbl linkee, $nonempty_tbl"
                            . " WHERE linkfrom=linker.id AND linkto=linkee.id"
                            . " AND $have.pagename=$qpagename"
                            . " AND $want.pagename=$qlink");
        return $row[0];
    }

    /*
     * 
     */
    function get_all_pages($include_empty=false, $sortby=false, $limit=false, $exclude='') {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $orderby = $this->sortby($sortby, 'db');
        if ($orderby) $orderby = ' ORDER BY ' . $orderby;
        if ($exclude) // array of pagenames
            $exclude = " AND $page_tbl.pagename NOT IN ".$this->_sql_set($exclude);
        else 
            $exclude='';

        //$dbh->SetFetchMode(ADODB_FETCH_ASSOC);
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
        if ($limit) {
            // extract from,count from limit
            list($offset,$count) = $this->limit($limit);
            $result = $dbh->SelectLimit($sql, $count, $offset);
        } else {
            $result = $dbh->Execute($sql);
        }
        //$dbh->SetFetchMode(ADODB_FETCH_NUM);
        return new WikiDB_backend_ADODB_iter($this, $result, $this->page_tbl_field_list);
    }
        
    /**
     * Title and fulltext search.
     */
    function text_search($search, $fullsearch=false, 
                         $sortby=false, $limit=false, $exclude=false) 
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $orderby = $this->sortby($sortby, 'db');
        if ($orderby) $orderby = ' ORDER BY ' . $orderby;
        
        $table = "$nonempty_tbl, $page_tbl";
        $join_clause = "$nonempty_tbl.id=$page_tbl.id";
        $fields = $this->page_tbl_fields;
        $field_list = $this->page_tbl_field_list;
        $searchobj = new WikiDB_backend_ADODB_search($search, $dbh);
        
        if ($fullsearch) {
            $table .= ", $recent_tbl";
            $join_clause .= " AND $page_tbl.id=$recent_tbl.id";

            $table .= ", $version_tbl";
            $join_clause .= " AND $page_tbl.id=$version_tbl.id AND latestversion=version";

            $fields .= ",$page_tbl.pagedata as pagedata," . $this->version_tbl_fields;
            $field_list = array_merge($field_list, array('pagedata'), 
                                      $this->version_tbl_field_list);
            $callback = new WikiMethodCb($searchobj, "_fulltext_match_clause");
        } else {
            $callback = new WikiMethodCb($searchobj, "_pagename_match_clause");
        }
        
        $search_clause = $search->makeSqlClauseObj($callback);
        $sql = "SELECT $fields FROM $table"
            . " WHERE $join_clause"
            . " AND ($search_clause)"
            . $orderby;
        if ($limit) {
            // extract from,count from limit
            list($offset,$count) = $this->limit($limit);
            $result = $dbh->SelectLimit($sql, $count, $offset);
        } else {
            $result = $dbh->Execute($sql);
        }
        $iter = new WikiDB_backend_ADODB_iter($this, $result, $field_list);
        if ($fullsearch)
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
    function most_popular ($limit=20, $sortby='-hits') {
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
            // extract from,count from limit
            list($offset,$count) = $this->limit($limit);
            $result = $dbh->SelectLimit($sql, $count, $offset);
        } else {
            $result = $dbh->Execute($sql);
        }
        return new WikiDB_backend_ADODB_iter($this, $result, $this->page_tbl_field_list);
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
        // FIXME: use SQL_BUFFER_RESULT for mysql?
        if ($limit) {
            // extract from,count from limit
            list($offset,$count) = $this->limit($limit);
            $result = $dbh->SelectLimit($sql, $count, $offset);
        } else {
            $result = $dbh->Execute($sql);
        }
        //$result->fields['version'] = $result->fields[6];
        return new WikiDB_backend_ADODB_iter($this, $result, 
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
            $exclude_from = " AND pp.pagename NOT IN ".$this->_sql_set($exclude_from);
        if ($exclude) // array of pagenames
            $exclude = " AND p.pagename NOT IN ".$this->_sql_set($exclude);

        /* 
         all empty pages, independent of linkstatus:
           select pagename as empty from page left join nonempty using(id) where is null(nonempty.id);
         only all empty pages, which have a linkto:
            select page.pagename, linked.pagename as wantedfrom from link, page linked 
              left join page on link.linkto=page.id left join nonempty on link.linkto=nonempty.id
              where nonempty.id is null and linked.id=link.linkfrom;  
        */
        $sql = "SELECT p.pagename, pp.pagename as wantedfrom"
            . " FROM $page_tbl p, $link_tbl linked "
            . " LEFT JOIN $page_tbl pp ON linked.linkto = pp.id"
            . " LEFT JOIN $nonempty_tbl ne ON linked.linkto = ne.id" 
            . " WHERE ne.id is NULL"
            .       " AND p.id = linked.linkfrom"
            . $exclude_from
            . $exclude
            . $orderby;
        if ($limit) {
            // extract from,count from limit
            list($offset,$count) = $this->limit($limit);
            $result = $dbh->SelectLimit($sql, $count, $offset);
        } else {
            $result = $dbh->Execute($sql);
        }
        return new WikiDB_backend_ADODB_iter($this, $result, array('pagename','wantedfrom'));
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
            $dbh->query(sprintf("UPDATE $page_tbl SET pagename=%s WHERE id=$id",
                                $dbh->qstr($to)));
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
            $dbh->Execute("REPLACE INTO $recent_tbl"
                          . " (id, latestversion, latestmajor, latestminor)"
                          . " SELECT id, $maxversion, $maxmajor, $maxminor"
                          . " FROM $version_tbl"
                          . ( $pageid ? " WHERE id=$pageid" : "")
                          . " GROUP BY id" );
        } else {
            $this->lock(array('recent'));
            $dbh->Execute("DELETE FROM $recent_tbl"
                      . ( $pageid ? " WHERE id=$pageid" : ""));
            $dbh->Execute( "INSERT INTO $recent_tbl"
                           . " (id, latestversion, latestmajor, latestminor)"
                           . " SELECT id, $maxversion, $maxmajor, $maxminor"
                           . " FROM $version_tbl"
                           . ( $pageid ? " WHERE id=$pageid" : "")
                           . " GROUP BY id" );
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
        $dbh->Execute("DELETE FROM $nonempty_tbl"
                      . ( $pageid ? " WHERE id=$pageid" : ""));
        $dbh->Execute("INSERT INTO $nonempty_tbl (id)"
                      . " SELECT $recent_tbl.id"
                      . " FROM $recent_tbl, $version_tbl"
                      . " WHERE $recent_tbl.id=$version_tbl.id"
                      .       " AND version=latestversion"
                      // We have some specifics here (Oracle)
                      //. "  AND content<>''"
                      . "  AND content $notempty" // On Oracle not just "<>''"
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
	$this->_dbh->StartTrans();
        if ($this->_lock_count++ == 0) {
            $this->_current_lock = $tables;
            $this->_lock_tables($tables, $write_lock);
        }
    }

    /**
     * Overridden by non-transaction safe backends.
     */
    function _lock_tables($tables, $write_lock) {
        return $this->_current_lock;
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
            $this->_dbh->CompleteTrans(! $force);
            $this->_current_lock = false;
            return;
        }
        if (--$this->_lock_count <= 0 || $force) {
            $this->_unlock_tables($tables, $force);
            $this->_current_lock = false;
            $this->_lock_count = 0;
        }
            $this->_dbh->CompleteTrans(! $force);
    }

    /**
     * overridden by non-transaction safe backends
     */
    function _unlock_tables($tables, $write_lock=false) {
        return;
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
        return $this->_dbh->_connectionID;
    }
    function getRow($query) {
        return $this->_dbh->getRow($query);
    }

    function listOfTables() {
        return $this->_dbh->MetaTables();
    }
    
    // other database needs another connection and other privileges.
    function listOfFields($database, $table) {
        $field_list = array();
        $old_db = $this->database();
        if ($database != $old_db) {
            $conn = $this->_dbh->Connect($this->_parsedDSN['hostspec'], 
            				 DBADMIN_USER ? DBADMIN_USER : $this->_parsedDSN['username'], 
                                         DBADMIN_PASSWD ? DBADMIN_PASSWD : $this->_parsedDSN['password'], 
                                         $database);
        }
        foreach ($this->_dbh->MetaColumns($table, false) as $field) {
            $field_list[] = $field->name;
        }
        if ($database != $old_db) {
            $this->_dbh->close();
            $conn = $this->_dbh->Connect($this->_parsedDSN['hostspec'], 
            				 $this->_parsedDSN['username'], 
                                         $this->_parsedDSN['password'], 
                                         $old_db);
        }
        return $field_list;
    }

};

class WikiDB_backend_ADODB_generic_iter
extends WikiDB_backend_iterator
{
    function WikiDB_backend_ADODB_generic_iter($backend, $query_result, $field_list = NULL) {
        $this->_backend = &$backend;
        $this->_result = $query_result;

        if (is_null($field_list)) {
            // No field list passed, retrieve from DB
            // WikiLens is using the iterator behind the scene
            $field_list = array();
            $fields = $query_result->FieldCount();
            for ($i = 0; $i < $fields ; $i++) {
                $field_info = $query_result->FetchField($i);
                array_push($field_list, $field_info->name);
            }
        }

        $this->_fields = $field_list;
    }
    
    function count() {
        if (!$this->_result) {
            return false;
        }
        $count = $this->_result->numRows();
        //$this->_result->Close();
        return $count;
    }

    function next() {
        $result = &$this->_result;
        $backend = &$this->_backend;
        if (!$result || $result->EOF) {
            $this->free();
            return false;
        }

        // Convert array to hash
        $i = 0;
        $rec_num = $result->fields;
        foreach ($this->_fields as $field) {
            $rec_assoc[$field] = $rec_num[$i++];
        }
        // check if the cache can be populated here?

        $result->MoveNext();
        return $rec_assoc;
    }

    function free () {
        if ($this->_result) {
            /* call mysql_free_result($this->_queryID) */
            $this->_result->Close();
            $this->_result = false;
        }
    }
}

class WikiDB_backend_ADODB_iter
extends WikiDB_backend_ADODB_generic_iter
{
    function next() {
        $result = &$this->_result;
        $backend = &$this->_backend;
        if (!$result || $result->EOF) {
            $this->free();
            return false;
        }

        // Convert array to hash
        $i = 0;
        $rec_num = $result->fields;
        foreach ($this->_fields as $field) {
            $rec_assoc[$field] = $rec_num[$i++];
        }

        $result->MoveNext();
        if (isset($rec_assoc['pagedata']))
            $rec_assoc['pagedata'] = $backend->_extract_page_data($rec_assoc['pagedata'], $rec_assoc['hits']);
        if (!empty($rec_assoc['version'])) {
            $rec_assoc['versiondata'] = $backend->_extract_version_data_assoc($rec_assoc);
        }
        return $rec_assoc;
    }
}

class WikiDB_backend_ADODB_search extends WikiDB_backend_search_sql 
{
    // no surrounding quotes because we know it's a string
    // function _quote($word) { return $this->_dbh->escapeSimple($word); }
}

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
     *  + dbsyntax: Database used with regards to SQL syntax etc.
     *  + protocol: Communication protocol to use (tcp, unix etc.)
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

// $Log: ADODB.php,v $
// Revision 1.82  2005/10/31 16:48:22  rurban
// move mysql-specifics into its special class
//
// Eliminate stoplist words,
// don't extract %pagedate twice in ADODB,
// fix fulltext search,
//
// Revision 1.81  2005/10/10 19:42:14  rurban
// fix wanted_pages SQL syntax
//
// Revision 1.79  2005/09/28 19:08:41  rurban
// dont use LIMIT on modifying queries
//
// Revision 1.78  2005/09/14 06:04:43  rurban
// optimize searching for ALL (ie %), use the stoplist on PDO
//
// Revision 1.77  2005/09/11 14:55:05  rurban
// implement fulltext stoplist
//
// Revision 1.76  2005/09/11 13:25:12  rurban
// enhance LIMIT support
//
// Revision 1.75  2005/09/10 21:30:16  rurban
// enhance titleSearch
//
// Revision 1.74  2005/02/10 19:04:22  rurban
// move getRow up one level to our backend class
//
// Revision 1.73  2005/02/04 13:43:30  rurban
// fix purge cache error
//
// Revision 1.72  2005/01/29 19:51:03  rurban
// Bugs item #1077769 fixed by frugal.
// Deleted the wrong page. Fix all other tables also.
//
// Revision 1.71  2005/01/25 08:01:00  rurban
// fix listOfFields with different database
//
// Revision 1.70  2005/01/18 20:55:43  rurban
// reformatting and two bug fixes: adding missing parens
//
// Revision 1.69  2004/12/26 17:14:03  rurban
// fix ADODB MostPopular, avoid limit -1, pass hits on empty data
//
// Revision 1.68  2004/12/22 18:33:25  rurban
// fix page _id_cache logic for _get_pageid create_if_missing
//
// Revision 1.67  2004/12/22 15:47:41  rurban
// fix wrong _update_nonempty_table on empty content (i.e. the new deletePage)
//
// Revision 1.66  2004/12/13 14:39:16  rurban
// avoid warning
//
// Revision 1.65  2004/12/10 22:15:00  rurban
// fix $page->get('_cached_html)
// refactor upgrade db helper _convert_cached_html() to be able to call them from WikiAdminUtils also.
// support 2nd genericSqlQuery param (bind huge arg)
//
// Revision 1.64  2004/12/10 02:45:27  rurban
// SQL optimization:
//   put _cached_html from pagedata into a new seperate blob, not huge serialized string.
//   it is only rarelely needed: for current page only, if-not-modified
//   but was extracted for every simple page iteration.
//
// Revision 1.63  2004/12/08 12:55:51  rurban
// support new non-destructive delete_page via generic backend method
//
// Revision 1.62  2004/12/06 19:50:04  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.61  2004/11/30 17:45:53  rurban
// exists_links backend implementation
//
// Revision 1.60  2004/11/28 20:42:18  rurban
// Optimize PearDB _extract_version_data and _extract_page_data.
//
// Revision 1.59  2004/11/27 14:39:05  rurban
// simpified regex search architecture:
//   no db specific node methods anymore,
//   new sql() method for each node
//   parallel to regexp() (which returns pcre)
//   regex types bitmasked (op's not yet)
// new regex=sql
// clarified WikiDB::quote() backend methods:
//   ->quote() adds surrounsing quotes
//   ->qstr() (new method) assumes strings and adds no quotes! (in contrast to ADODB)
//   pear and adodb have now unified quote methods for all generic queries.
//
// Revision 1.58  2004/11/26 18:39:02  rurban
// new regex search parser and SQL backends (90% complete, glob and pcre backends missing)
//
// Revision 1.57  2004/11/25 17:20:51  rurban
// and again a couple of more native db args: backlinks
//
// Revision 1.56  2004/11/23 13:35:48  rurban
// add case_exact search
//
// Revision 1.55  2004/11/21 11:59:26  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.54  2004/11/20 17:49:39  rurban
// add fast exclude support to SQL get_all_pages
//
// Revision 1.53  2004/11/20 17:35:58  rurban
// improved WantedPages SQL backends
// PageList::sortby new 3rd arg valid_fields (override db fields)
// WantedPages sql pager inexact for performance reasons:
//   assume 3 wantedfrom per page, to be correct, no getTotal()
// support exclude argument for get_all_pages, new _sql_set()
//
// Revision 1.52  2004/11/17 20:07:17  rurban
// just whitespace
//
// Revision 1.51  2004/11/15 15:57:37  rurban
// silent cache warning
//
// Revision 1.50  2004/11/10 19:32:23  rurban
// * optimize increaseHitCount, esp. for mysql.
// * prepend dirs to the include_path (phpwiki_dir for faster searches)
// * Pear_DB version logic (awful but needed)
// * fix broken ADODB quote
// * _extract_page_data simplification
//
// Revision 1.49  2004/11/10 15:29:21  rurban
// * requires newer Pear_DB (as the internal one): quote() uses now escapeSimple for strings
// * ACCESS_LOG_SQL: fix cause request not yet initialized
// * WikiDB: moved SQL specific methods upwards
// * new Pear_DB quoting: same as ADODB and as newer Pear_DB.
//   fixes all around: WikiGroup, WikiUserNew SQL methods, SQL logging
//
// Revision 1.48  2004/11/09 17:11:16  rurban
// * revert to the wikidb ref passing. there's no memory abuse there.
// * use new wikidb->_cache->_id_cache[] instead of wikidb->_iwpcache, to effectively
//   store page ids with getPageLinks (GleanDescription) of all existing pages, which
//   are also needed at the rendering for linkExistingWikiWord().
//   pass options to pageiterator.
//   use this cache also for _get_pageid()
//   This saves about 8 SELECT count per page (num all pagelinks).
// * fix passing of all page fields to the pageiterator.
// * fix overlarge session data which got broken with the latest ACCESS_LOG_SQL changes
//
// Revision 1.47  2004/11/06 17:11:42  rurban
// The optimized version doesn't query for pagedata anymore.
//
// Revision 1.46  2004/11/01 10:43:58  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.45  2004/10/14 17:19:17  rurban
// allow most_popular sortby arguments
//
// Revision 1.44  2004/09/06 08:33:09  rurban
// force explicit mysql auto-incrementing, atomic version
//
// Revision 1.43  2004/07/10 08:50:24  rurban
// applied patch by Philippe Vanhaesendonck:
//   pass column list to iterators so we can FETCH_NUM in all cases.
//   bind UPDATE pramas for huge pagedata.
//   portable oracle backend
//
// Revision 1.42  2004/07/09 10:06:50  rurban
// Use backend specific sortby and sortable_columns method, to be able to
// select between native (Db backend) and custom (PageList) sorting.
// Fixed PageList::AddPageList (missed the first)
// Added the author/creator.. name to AllPagesBy...
//   display no pages if none matched.
// Improved dba and file sortby().
// Use &$request reference
//
// Revision 1.41  2004/07/08 21:32:35  rurban
// Prevent from more warnings, minor db and sort optimizations
//
// Revision 1.40  2004/07/08 16:56:16  rurban
// use the backendType abstraction
//
// Revision 1.39  2004/07/05 13:56:22  rurban
// sqlite autoincrement fix
//
// Revision 1.38  2004/07/05 12:57:54  rurban
// add mysql timeout
//
// Revision 1.37  2004/07/04 10:24:43  rurban
// forgot the expressions
//
// Revision 1.36  2004/07/03 16:51:06  rurban
// optional DBADMIN_USER:DBADMIN_PASSWD for action=upgrade (if no ALTER permission)
// added atomic mysql REPLACE for PearDB as in ADODB
// fixed _lock_tables typo links => link
// fixes unserialize ADODB bug in line 180
//
// Revision 1.35  2004/06/28 14:45:12  rurban
// fix adodb_sqlite to have the same dsn syntax as pear, use pconnect if requested
//
// Revision 1.34  2004/06/28 14:17:38  rurban
// updated DSN parser from Pear. esp. for sqlite
//
// Revision 1.33  2004/06/27 10:26:02  rurban
// oci8 patch by Philippe Vanhaesendonck + some ADODB notes+fixes
//
// Revision 1.32  2004/06/25 14:15:08  rurban
// reduce memory footprint by caching only requested pagedate content (improving most page iterators)
//
// Revision 1.31  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.30  2004/06/07 19:31:31  rurban
// fixed ADOOB upgrade: listOfFields()
//
// Revision 1.29  2004/05/12 10:49:55  rurban
// require_once fix for those libs which are loaded before FileFinder and
//   its automatic include_path fix, and where require_once doesn't grok
//   dirname(__FILE__) != './lib'
// upgrade fix with PearDB
// navbar.tmpl: remove spaces for IE &nbsp; button alignment
//
// Revision 1.28  2004/05/06 19:26:16  rurban
// improve stability, trying to find the InlineParser endless loop on sf.net
//
// remove end-of-zip comments to fix sf.net bug #777278 and probably #859628
//
// Revision 1.27  2004/05/06 17:30:38  rurban
// CategoryGroup: oops, dos2unix eol
// improved phpwiki_version:
//   pre -= .0001 (1.3.10pre: 1030.099)
//   -p1 += .001 (1.3.9-p1: 1030.091)
// improved InstallTable for mysql and generic SQL versions and all newer tables so far.
// abstracted more ADODB/PearDB methods for action=upgrade stuff:
//   backend->backendType(), backend->database(),
//   backend->listOfFields(),
//   backend->listOfTables(),
//
// Revision 1.26  2004/04/26 20:44:35  rurban
// locking table specific for better databases
//
// Revision 1.25  2004/04/20 00:06:04  rurban
// themable paging support
//
// Revision 1.24  2004/04/18 01:34:20  rurban
// protect most_popular from sortby=mtime
//
// Revision 1.23  2004/04/16 14:19:39  rurban
// updated ADODB notes
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