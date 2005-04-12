<?php // -*-php-*-
rcs_id('$Id$');

require_once('lib/WikiDB/backend.php');
//require_once('lib/FileFinder.php');
require_once('lib/ErrorManager.php');
require_once('DB.php'); // Always favor use our local pear copy

class WikiDB_backend_PearDB
extends WikiDB_backend
{
    var $_dbh;

    function WikiDB_backend_PearDB ($dbparams) {
        // Find and include PEAR's DB.php.
        //$pearFinder = new PearFileFinder;
        //$pearFinder->includeOnce('DB.php');

        // Install filter to handle bogus error notices from buggy DB.php's.
        global $ErrorManager;
        $ErrorManager->pushErrorHandler(new WikiMethodCb($this, '_pear_notice_filter'));
        
        // Open connection to database
        $this->_dsn = $dbparams['dsn'];
        $dboptions = array('persistent' => true,
                           'debug' => 2);
        if (preg_match('/^pgsql/',$this->_dsn))
            $dboptions['persistent'] = false;
        $this->_dbh = DB::connect($this->_dsn, $dboptions);
        $dbh = &$this->_dbh;
        if (DB::isError($dbh)) {
            trigger_error(sprintf("Can't connect to database: %s",
                                  $this->_pear_error_message($dbh)),
                          E_USER_ERROR);
        }
        $dbh->setErrorHandling(PEAR_ERROR_CALLBACK,
                               array(&$this, '_pear_error_callback'));
        $dbh->setFetchMode(DB_FETCHMODE_ASSOC);

        $prefix = isset($dbparams['prefix']) ? $dbparams['prefix'] : '';

        $this->_table_names
            = array('page_tbl'     => $prefix . 'page',
                    'version_tbl'  => $prefix . 'version',
                    'link_tbl'     => $prefix . 'link',
                    'recent_tbl'   => $prefix . 'recent',
                    'nonempty_tbl' => $prefix . 'nonempty');
        $page_tbl = $this->_table_names['page_tbl'];
        $version_tbl = $this->_table_names['version_tbl'];
        $this->page_tbl_fields = "$page_tbl.id as id, $page_tbl.pagename as pagename, $page_tbl.hits as hits, $page_tbl.pagedata as pagedata, $page_tbl.group_id as group_id";
        $this->version_tbl_fields = "$version_tbl.version as version, $version_tbl.mtime as mtime, ".
            "$version_tbl.minor_edit as minor_edit, $version_tbl.content as content, $version_tbl.versiondata as versiondata";

        $this->_expressions
            = array('maxmajor'     => "MAX(CASE WHEN minor_edit=0 THEN version END)",
                    'maxminor'     => "MAX(CASE WHEN minor_edit<>0 THEN version END)",
                    'maxversion'   => "MAX(version)");
        
        $this->_lock_count = 0;
    }
    
    /**
     * Close database connection.
     */
    function close () {
        if (!$this->_dbh)
            return;
        if ($this->_lock_count) {
            trigger_error( "WARNING: database still locked " . '(lock_count = $this->_lock_count)' . "\n<br />",
                          E_USER_WARNING);
        }
        $this->_dbh->setErrorHandling(PEAR_ERROR_PRINT);	// prevent recursive loops.
        $this->unlock('force');

        $this->_dbh->disconnect();
    }


    /*
     * Test fast wikipage.
     */
    function is_wiki_page($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        return $dbh->getOne(sprintf("SELECT $page_tbl.id as id"
                                    . " FROM $nonempty_tbl, $page_tbl"
                                    . " WHERE $nonempty_tbl.id=$page_tbl.id"
                                    . "   AND pagename='%s'"
                                    . " AND $page_tbl.group_id='%d'",
                                    $dbh->quoteString($pagename),
                                    GROUP_ID));
    }
        
    function get_all_pagenames() {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        return $dbh->getCol("SELECT pagename"
                            . " FROM $nonempty_tbl, $page_tbl"
                            . " WHERE $nonempty_tbl.id=$page_tbl.id"
                            . " AND $page_tbl.group_id=".GROUP_ID);
    }

    function numPages($filter=false, $exclude='') {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        return $dbh->getOne("SELECT count(*)"
                            . " FROM $nonempty_tbl, $page_tbl"
                            . " WHERE $nonempty_tbl.id=$page_tbl.id"
                            . " AND $page_tbl.group_id=".GROUP_ID);
    }
    
    /**
     * Read page information from database.
     */
    function get_pagedata($pagename) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];

        //trigger_error("GET_PAGEDATA $pagename", E_USER_NOTICE);

        $result = $dbh->getRow(sprintf("SELECT %s FROM $page_tbl WHERE pagename='%s' AND $page_tbl.group_id='%d'",
                                       $this->page_tbl_fields,
                                       $dbh->quoteString($pagename),
                                       GROUP_ID),
                               DB_FETCHMODE_ASSOC);
        if (!$result)
            return false;
        return $this->_extract_page_data($result);
    }

    function  _extract_page_data(&$query_result) {
        extract($query_result);
        $data = $this->_unserialize($pagedata);
        $data['hits'] = $hits;
        return $data;
    }

    function update_pagedata($pagename, $newdata) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];

        // Hits is the only thing we can update in a fast manner.
        if (count($newdata) == 1 && isset($newdata['hits'])) {
            // Note that this will fail silently if the page does not
            // have a record in the page table.  Since it's just the
            // hit count, who cares?
            $dbh->query(sprintf("UPDATE $page_tbl SET hits=%d WHERE pagename='%s' AND $page_tbl.group_id='%d'",
                                $newdata['hits'], $dbh->quoteString($pagename), GROUP_ID));
            return;
        }

        $this->lock();
        $data = $this->get_pagedata($pagename);
        if (!$data) {
            $data = array();
            $this->_get_pageid($pagename, true); // Creates page record
        }
        
        @$hits = (int)$data['hits'];
        unset($data['hits']);

        foreach ($newdata as $key => $val) {
            if ($key == 'hits')
                $hits = (int)$val;
            else if (empty($val))
                unset($data[$key]);
            else
                $data[$key] = $val;
        }

        $dbh->query(sprintf("UPDATE $page_tbl"
                            . " SET hits=%d, pagedata='%s'"
                            . " WHERE pagename='%s'"
                            . " AND group_id=%d",
                            $hits,
                            $dbh->quoteString($this->_serialize($data)),
                            $dbh->quoteString($pagename),
                            GROUP_ID));
        $this->unlock();
    }

    function _get_pageid($pagename, $create_if_missing = false) {

        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        
        $query = sprintf("SELECT id FROM $page_tbl WHERE pagename='%s' AND $page_tbl.group_id=%d",
                         $dbh->quoteString($pagename),
                         GROUP_ID);

        if (!$create_if_missing)
            return $dbh->getOne($query);

        $this->lock();
        $id = $dbh->getOne($query);
        if (empty($id)) {
            $max_id = $dbh->getOne("SELECT MAX(id) FROM $page_tbl");
            $id = $max_id + 1;
            $dbh->query(sprintf("INSERT INTO $page_tbl"
                                . " (id,pagename,hits,group_id)"
                                . " VALUES (%d,'%s',0,%d)",
                                $id, $dbh->quoteString($pagename),
                                GROUP_ID));
        }
        $this->unlock();
        return $id;
    }

    function get_latest_version($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        return
            (int)$dbh->getOne(sprintf("SELECT latestversion"
                                      . " FROM $page_tbl, $recent_tbl"
                                      . " WHERE $page_tbl.id=$recent_tbl.id"
                                      . "  AND pagename='%s'"
                                      . " AND $page_tbl.group_id=%d",
                                      $dbh->quoteString($pagename),
                                      GROUP_ID));
    }

    function get_previous_version($pagename, $version) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        
        return
            (int)$dbh->getOne(sprintf("SELECT version"
                                      . " FROM $version_tbl, $page_tbl"
                                      . " WHERE $version_tbl.id=$page_tbl.id"
                                      . "  AND pagename='%s'"
                                      . "  AND $page_tbl.group_id=%d"
                                      . "  AND version < %d"
                                      . " ORDER BY version DESC"
                                      . " LIMIT 1",
                                      $dbh->quoteString($pagename),
                                      GROUP_ID,
                                      $version));
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

        assert(is_string($pagename) and $pagename != "");
        assert($version > 0);
        
        //trigger_error("GET_REVISION $pagename $version $want_content", E_USER_NOTICE);
        // FIXME: optimization: sometimes don't get page data?

        if ($want_content) {
            $fields = $this->page_tbl_fields . "," . $this->version_tbl_fields;
        }
        else {
            $fields = $this->page_tbl_fields . ","
                       . "mtime, minor_edit, versiondata,"
                       . "content<>'' AS have_content";
        }

        $result = $dbh->getRow(sprintf("SELECT $fields"
                                       . " FROM $page_tbl, $version_tbl"
                                       . " WHERE $page_tbl.id=$version_tbl.id"
                                       . "  AND pagename='%s'"
                                       . "  AND version=%d"
                                       . "  AND $page_tbl.group_id=%d",
                                       $dbh->quoteString($pagename), $version, GROUP_ID),
                               DB_FETCHMODE_ASSOC);

        return $this->_extract_version_data($result);
    }

    function _extract_version_data(&$query_result) {
        if (!$query_result)
            return false;

        extract($query_result);
        $data = $this->_unserialize($versiondata);
        
        $data['mtime'] = $mtime;
        $data['is_minor_edit'] = !empty($minor_edit);
        
        if (isset($content))
            $data['%content'] = $content;
        elseif ($have_content)
            $data['%content'] = true;
        else
            $data['%content'] = '';

        // FIXME: this is ugly.
        if (isset($pagename)) {
            // Query also includes page data.
            // We might as well send that back too...
            $data['%pagedata'] = $this->_extract_page_data($query_result);
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
        
        $this->lock();
        $id = $this->_get_pageid($pagename, true);

        // FIXME: optimize: mysql can do this with one REPLACE INTO (I think).
        $dbh->query(sprintf("DELETE FROM $version_tbl"
                            . " WHERE id=%d AND version=%d",
                            $id, $version));

        $dbh->query(sprintf("INSERT INTO $version_tbl"
                            . " (id,version,mtime,minor_edit,content,versiondata)"
                            . " VALUES(%d,%d,%d,%d,'%s','%s')",
                            $id, $version, $mtime, $minor_edit,
                            $dbh->quoteString($content),
                            $dbh->quoteString($this->_serialize($data))));

        $this->_update_recent_table($id);
        $this->_update_nonempty_table($id);
        
        $this->unlock();
    }
    
    /**
     * Delete an old revision of a page.
     */
    function delete_versiondata($pagename, $version) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $this->lock();
        if ( ($id = $this->_get_pageid($pagename)) ) {
            $dbh->query("DELETE FROM $version_tbl"
                        . " WHERE id=$id AND version=$version");
            $this->_update_recent_table($id);
            // This shouldn't be needed (as long as the latestversion
            // never gets deleted.)  But, let's be safe.
            $this->_update_nonempty_table($id);
        }
        $this->unlock();
    }

    /**
     * Delete page completely from the database.
     * I'm not sure if this is what we want. Maybe just delete the revisions
     */
    function delete_page($pagename) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        
        $this->lock();
        if ( ($id = $this->_get_pageid($pagename, false)) ) {
            $dbh->query("DELETE FROM $version_tbl  WHERE id=$id");
            $dbh->query("DELETE FROM $recent_tbl   WHERE id=$id");
            $dbh->query("DELETE FROM $nonempty_tbl WHERE id=$id");
            $dbh->query("DELETE FROM $link_tbl     WHERE linkfrom=$id");
            $nlinks = $dbh->getOne("SELECT COUNT(*) FROM $link_tbl WHERE linkto=$id");
            if ($nlinks) {
                // We're still in the link table (dangling link) so we can't delete this
                // altogether.
                $dbh->query("UPDATE $page_tbl SET hits=0, pagedata='' WHERE id=$id AND group_id=".GROUP_ID);
            }
            else {
                $dbh->query("DELETE FROM $page_tbl WHERE id=$id AND group_id=".GROUP_ID);
            }
            $this->_update_recent_table();
            $this->_update_nonempty_table();
        }
        $this->unlock();
    }
            

    // The only thing we might be interested in updating which we can
    // do fast in the flags (minor_edit).   I think the default
    // update_versiondata will work fine...
    //function update_versiondata($pagename, $version, $data) {
    //}

    function set_links($pagename, $links) {
        // Update link table.
        // FIXME: optimize: mysql can do this all in one big INSERT.

        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $this->lock();
        $pageid = $this->_get_pageid($pagename, true);

        $dbh->query("DELETE FROM $link_tbl WHERE linkfrom=$pageid");

	if ($links) {
            foreach($links as $link) {
                if (isset($linkseen[$link]))
                    continue;
                $linkseen[$link] = true;
                $linkid = $this->_get_pageid($link, true);
                $dbh->query("INSERT INTO $link_tbl (linkfrom, linkto)"
                            . " VALUES ($pageid, $linkid)");
            }
	}
        $this->unlock();
    }
    
    /**
     * Find pages which link to or are linked from a page.
     */
    function get_links($pagename, $reversed = true) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        if ($reversed)
            list($have,$want) = array('linkee', 'linker');
        else
            list($have,$want) = array('linker', 'linkee');

        
        $qpagename = $dbh->quoteString($pagename);
        
        $result = $dbh->query("SELECT $want.id as id, $want.pagename as pagename, $want.hits as hits, $want.pagedata as pagedata"
                              . " FROM $link_tbl, $page_tbl AS linker, $page_tbl AS linkee"
                              . " WHERE linkfrom=linker.id AND linkto=linkee.id"
                              . "  AND $have.pagename='$qpagename'"
                              . "  AND linker.group_id=".GROUP_ID
                              . "  AND linkee.group_id=".GROUP_ID
                              //. " GROUP BY $want.id"
                              . " ORDER BY $want.pagename");
        
        return new WikiDB_backend_PearDB_iter($this, $result);
    }

    function get_all_pages($include_deleted=false,$sortby=false,$limit=false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        if ($limit)  $limit = "LIMIT $limit";
        else         $limit = '';
        if ($sortby) $orderby = 'ORDER BY ' . PageList::sortby($sortby,'db');
        else         $orderby = '';
        if (strstr($orderby,' mtime')) {
            if ($include_deleted) {
                $result = $dbh->query("SELECT "
                					  . $this->page_tbl_fields
                					  . " FROM $page_tbl, $recent_tbl, $version_tbl"
                                      . " WHERE $page_tbl.id=$recent_tbl.id"
                                      . " AND $page_tbl.id=$version_tbl.id AND latestversion=version"
                                      . " AND $page_tbl.group_id=".GROUP_ID
                                      . " $orderby $limit");
            }
            else {
                $result = $dbh->query("SELECT "
                                      . $this->page_tbl_fields
                                      . " FROM $nonempty_tbl, $page_tbl, $recent_tbl, $version_tbl"
                                      . " WHERE $nonempty_tbl.id=$page_tbl.id"
                                      . " AND $page_tbl.id=$recent_tbl.id"
                                      . " AND $page_tbl.id=$version_tbl.id AND latestversion=version"
                                      . " AND $page_tbl.group_id=" .GROUP_ID
                                      . " $orderby $limit");
            }
        } else {
            if ($include_deleted) {
                $result = $dbh->query("SELECT * FROM $page_tbl WHERE $page_tbl.group_id=".GROUP_ID." $orderby $limit");
            }
            else {
                $result = $dbh->query("SELECT "
                                      . $this->page_tbl_fields
                                      . " FROM $nonempty_tbl, $page_tbl"
                                      . " WHERE $nonempty_tbl.id=$page_tbl.id"
                                      . " AND $page_tbl.group_id=".GROUP_ID
                                      . " $orderby $limit");
            }
        }
        return new WikiDB_backend_PearDB_iter($this, $result);
    }
        
    /**
     * Title search.
     */
    function text_search($search = '', $fullsearch = false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        
        $table = "$nonempty_tbl, $page_tbl";
        $join_clause = "$nonempty_tbl.id=$page_tbl.id";
        $fields = $this->page_tbl_fields;
        $callback = new WikiMethodCb($this, '_sql_match_clause');
        
        if ($fullsearch) {
            $table .= ", $recent_tbl";
            $join_clause .= " AND $page_tbl.id=$recent_tbl.id";

            $table .= ", $version_tbl";
            $join_clause .= " AND $page_tbl.id=$version_tbl.id AND latestversion=version";

            $fields .= ", " . $this->version_tbl_fields;
            $callback = new WikiMethodCb($this, '_fullsearch_sql_match_clause');
        }
        
        $search_clause = $search->makeSqlClause($callback);
        
        $result = $dbh->query("SELECT $fields FROM $table"
                              . " WHERE $join_clause"
                              . "  AND $page_tbl.group_id=".GROUP_ID
                              . "  AND ($search_clause)"
                              . " ORDER BY pagename");
        
        return new WikiDB_backend_PearDB_iter($this, $result);
    }

    //Todo: check if the better Mysql MATCH operator is supported,
    // (ranked search) and also google like expressions.
    function _sql_match_clause($word) {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->quoteString($word);
        //$page_tbl = $this->_table_names['page_tbl'];
        //Note: Mysql 4.1.0 has a bug which fails with binary fields.
        //      e.g. if word is lowercased.
        // http://bugs.mysql.com/bug.php?id=1491
        return "LOWER(pagename) LIKE '%$word%'";
    }

    function _fullsearch_sql_match_clause($word) {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->quoteString($word);
        //$page_tbl = $this->_table_names['page_tbl'];
        //Mysql 4.1.1 has a bug which fails here if word is lowercased.
        return "LOWER(pagename) LIKE '%$word%' OR content LIKE '%$word%'";
    }

    /**
     * Find highest or lowest hit counts.
     */
    function most_popular($limit=false,$sortby = '') {
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
        if ($limit)  $limit = " LIMIT $limit";
        else         $limit = '';
        if ($sortby) $orderby = ' ORDER BY ' . PageList::sortby($sortby,'db');
        else         $orderby = " ORDER BY hits $order";
        //$limitclause = $limit ? " LIMIT $limit" : '';
        $result = $dbh->query("SELECT "
                              . $this->page_tbl_fields
                              . " FROM $nonempty_tbl, $page_tbl"
                              . " WHERE $nonempty_tbl.id=$page_tbl.id"
                              . " AND $page_tbl.group_id=".GROUP_ID
                              . $where
                              . $orderby
                              . $limit);

        return new WikiDB_backend_PearDB_iter($this, $result);
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
        $limitclause = $limit ? " LIMIT $limit" : '';
        $where_clause = $join_clause;
        if ($pick)
            $where_clause .= " AND " . join(" AND ", $pick);

        // FIXME: use SQL_BUFFER_RESULT for mysql?
        $result = $dbh->query("SELECT " 
                              . $this->page_tbl_fields . ", " . $this->version_tbl_fields
                              . " FROM $table"
                              . " WHERE $where_clause"
                              . " AND $page_tbl.group_id=".GROUP_ID
                              . " ORDER BY mtime $order"
                              . $limitclause);

        return new WikiDB_backend_PearDB_iter($this, $result);
    }

    /**
     * Rename page in the database.
     */
    function rename_page($pagename, $to) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $this->lock();
        if ( ($id = $this->_get_pageid($pagename, false)) ) {
            if ($new = $this->_get_pageid($to, false)) {
                //cludge alert!
                //this page does not exist (already verified before), but exists in the page table.
                //so we delete this page.

                // MV patch: backport bug fix from CVS. Really delete new page if exist                
                $dbh->query("DELETE FROM $page_tbl WHERE id=$new");
                $dbh->query("DELETE FROM $version_tbl WHERE id=$new");
                $dbh->query("DELETE FROM $recent_tbl WHERE id=$new");
                $dbh->query("DELETE FROM $nonempty_tbl WHERE id=$new");
                // We have to fix all referring tables to the old id
                $dbh->query("UPDATE $link_tbl SET linkfrom=$id WHERE linkfrom=$new");
                $dbh->query("UPDATE $link_tbl SET linkto=$id WHERE linkto=$new");
            }
            $dbh->query(sprintf("UPDATE $page_tbl SET pagename='%s' WHERE id=$id AND $page_tbl.group_id=%d",
                                $dbh->quoteString($to), GROUP_ID));
        }
        $this->unlock();

        return $id;
    }

    function _update_recent_table($pageid = false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        extract($this->_expressions);

        $pageid = (int)$pageid;

        $this->lock();

        $dbh->query("DELETE FROM $recent_tbl"
                    . ( $pageid ? " WHERE id=$pageid" : ""));
        
        $dbh->query( "INSERT INTO $recent_tbl"
                     . " (id, latestversion, latestmajor, latestminor)"
                     . " SELECT id, $maxversion, $maxmajor, $maxminor"
                     . " FROM $version_tbl"
                     . ( $pageid ? " WHERE id=$pageid" : "")
                     . " GROUP BY id" );
        $this->unlock();
    }

    function _update_nonempty_table($pageid = false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $pageid = (int)$pageid;

        $this->lock();

        $dbh->query("DELETE FROM $nonempty_tbl"
                    . ( $pageid ? " WHERE id=$pageid" : ""));

        $dbh->query("INSERT INTO $nonempty_tbl (id)"
                    . " SELECT $recent_tbl.id"
                    . " FROM $recent_tbl, $version_tbl"
                    . " WHERE $recent_tbl.id=$version_tbl.id"
                    . "       AND version=latestversion"
                    . "  AND content<>''"
                    . ( $pageid ? " AND $recent_tbl.id=$pageid" : ""));

        $this->unlock();
    }


    /**
     * Grab a write lock on the tables in the SQL database.
     *
     * Calls can be nested.  The tables won't be unlocked until
     * _unlock_database() is called as many times as _lock_database().
     *
     * @access protected
     */
    function lock($tables = false, $write_lock = true) {
        if ($this->_lock_count++ == 0)
            $this->_lock_tables($write_lock);
    }

    /**
     * Actually lock the required tables.
     */
    function _lock_tables($write_lock) {
        trigger_error("virtual", E_USER_ERROR);
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
        if ($this->_lock_count == 0)
            return;
        if (--$this->_lock_count <= 0 || $force) {
            $this->_unlock_tables();
            $this->_lock_count = 0;
        }
    }

    /**
     * Actually unlock the required tables.
     */
    function _unlock_tables($write_lock) {
        trigger_error("virtual", E_USER_ERROR);
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
    
    /**
     * Callback for PEAR (DB) errors.
     *
     * @access protected
     *
     * @param A PEAR_error object.
     */
    function _pear_error_callback($error) {
        if ($this->_is_false_error($error))
            return;
        
        $this->_dbh->setErrorHandling(PEAR_ERROR_PRINT);	// prevent recursive loops.
        $this->close();
        trigger_error($this->_pear_error_message($error), E_USER_ERROR);
    }

    /**
     * Detect false errors messages from PEAR DB.
     *
     * The version of PEAR DB which ships with PHP 4.0.6 has a bug in that
     * it doesn't recognize "LOCK" and "UNLOCK" as SQL commands which don't
     * return any data.  (So when a "LOCK" command doesn't return any data,
     * DB reports it as an error, when in fact, it's not.)
     *
     * @access private
     * @return bool True iff error is not really an error.
     */
    function _is_false_error($error) {
        if ($error->getCode() != DB_ERROR)
            return false;

        $query = $this->_dbh->last_query;

        if (! preg_match('/^\s*"?(INSERT|UPDATE|DELETE|REPLACE|CREATE'
                         . '|DROP|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s/', $query)) {
            // Last query was not of the sort which doesn't return any data.
            //" <--kludge for brain-dead syntax coloring
            return false;
        }
        
        if (! in_array('ismanip', get_class_methods('DB'))) {
            // Pear shipped with PHP 4.0.4pl1 (and before, presumably)
            // does not have the DB::isManip method.
            return true;
        }
        
        if (DB::isManip($query)) {
            // If Pear thinks it's an isManip then it wouldn't have thrown
            // the error we're testing for....
            return false;
        }

        return true;
    }

    function _pear_error_message($error) {
        $class = get_class($this);
        $message = "$class: fatal database error\n"
             . "\t" . $error->getMessage() . "\n"
             . "\t(" . $error->getDebugInfo() . ")\n";

        // Prevent password from being exposed during a connection error
        $safe_dsn = preg_replace('| ( :// .*? ) : .* (?=@) |xs',
                                 '\\1:XXXXXXXX', $this->_dsn);
        return str_replace($this->_dsn, $safe_dsn, $message);
    }

    /**
     * Filter PHP errors notices from PEAR DB code.
     *
     * The PEAR DB code which ships with PHP 4.0.6 produces spurious
     * errors and notices.  This is an error callback (for use with
     * ErrorManager which will filter out those spurious messages.)
     * @see _is_false_error, ErrorManager
     * @access private
     */
    function _pear_notice_filter($err) {
        return ( $err->isNotice()
                 && preg_match('|DB[/\\\\]common.php$|', $err->errfile)
                 && $err->errline == 126
                 && preg_match('/Undefined offset: +0\b/', $err->errstr) );
    }

    /* some variables and functions for DB backend abstraction (action=upgrade) */
    function database () {
        return $this->_dbh->dsn['database'];
    }
    function backendType() {
        return $this->_dbh->phptype;
    }
    function connection() {
        return $this->_dbh->connection;
    }

    function listOfTables() {
        return $this->_dbh->getListOf('tables');
    }
    function listOfFields($database,$table) {
        if ($this->backendType() == 'mysql') {
            $fields = array();
            assert(!empty($database));
            assert(!empty($table));
  	    $result = mysql_list_fields($database, $table, $this->_dbh->connection) or 
  	        trigger_error(__FILE__.':'.__LINE__.' '.mysql_error(),E_USER_WARNING);
  	    if (!$result) return array();
  	    $columns = mysql_num_fields($result);
            for ($i = 0; $i < $columns; $i++) {
                $fields[] = mysql_field_name($result, $i);
            }
            mysql_free_result($result);
            return $fields;
        } else {
            // TODO: try ADODB version?
            trigger_error("Unsupported dbtype and backend. Either switch to ADODB or check it manually.");
        }
    }

};

/**
 * This class is a generic iterator.
 *
 * WikiDB_backend_PearDB_iter only iterates over things that have
 * 'pagename', 'pagedata', etc. etc.
 *
 * Probably WikiDB_backend_PearDB_iter and this class should be merged
 * (most of the code is cut-and-paste :-( ), but I am trying to make
 * changes that could be merged easily.
 *
 * @author: Dan Frankowski
 */
class WikiDB_backend_PearDB_generic_iter
extends WikiDB_backend_iterator
{
    function WikiDB_backend_PearDB_generic_iter(&$backend, &$query_result) {
        if (DB::isError($query_result)) {
            // This shouldn't happen, I thought.
            $backend->_pear_error_callback($query_result);
        }
        
        $this->_backend = &$backend;
        $this->_result = $query_result;
    }

    function count() {
        if (!$this->_result)
            return false;
        return $this->_result->numRows();
    }
    
    function next() {
        $backend = &$this->_backend;
        if (!$this->_result)
            return false;

        $record = $this->_result->fetchRow(DB_FETCHMODE_ASSOC);
        if (!$record) {
            $this->free();
            return false;
        }
        
        return $record;
    }

    function free () {
        if ($this->_result) {
            $this->_result->free();
            $this->_result = false;
        }
    }
}

class WikiDB_backend_PearDB_iter
extends WikiDB_backend_PearDB_generic_iter
{

    function next() {
        $backend = &$this->_backend;
        if (!$this->_result)
            return false;

        $record = $this->_result->fetchRow(DB_FETCHMODE_ASSOC);
        if (!$record) {
            $this->free();
            return false;
        }
        
        $pagedata = $backend->_extract_page_data($record);
        $rec = array('pagename' => $record['pagename'],
                     'pagedata' => $pagedata);

        if (!empty($record['version'])) {
            $rec['versiondata'] = $backend->_extract_version_data($record);
            $rec['version'] = $record['version'];
        }
        
        return $rec;
    }
}
// $Log$
// Revision 1.1  2005/04/12 13:33:30  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.51  2004/05/12 10:49:55  rurban
// require_once fix for those libs which are loaded before FileFinder and
//   its automatic include_path fix, and where require_once doesn't grok
//   dirname(__FILE__) != './lib'
// upgrade fix with PearDB
// navbar.tmpl: remove spaces for IE &nbsp; button alignment
//
// Revision 1.50  2004/05/06 17:30:39  rurban
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
// Revision 1.49  2004/05/03 21:35:30  rurban
// don't use persistent connections with postgres
//
// Revision 1.48  2004/04/26 20:44:35  rurban
// locking table specific for better databases
//
// Revision 1.47  2004/04/20 00:06:04  rurban
// themable paging support
//
// Revision 1.46  2004/04/19 21:51:41  rurban
// php5 compatibility: it works!
//
// Revision 1.45  2004/04/16 14:19:39  rurban
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
