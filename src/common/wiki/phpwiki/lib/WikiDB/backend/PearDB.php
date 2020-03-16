<?php
// -*-php-*-
rcs_id('$Id: PearDB.php,v 1.92 2005/09/14 06:04:43 rurban Exp $');

require_once('lib/WikiDB/backend.php');
//require_once('lib/FileFinder.php');
//require_once('lib/ErrorManager.php');

class WikiDB_backend_PearDB extends WikiDB_backend
{
    public const DATABASE_TABLE_PREFIX = 'wiki_';

    public $_dbh;

    public function __construct()
    {
        // Find and include PEAR's DB.php. maybe we should force our private version again...
        // if DB would have exported its version number, it would be easier.
        @require_once('DB/common.php'); // Either our local pear copy or the system one
        $name = 'escapeSimple';
        // TODO: apparently some Pear::Db version adds LIMIT 1,0 to getOne(),
        // which is invalid for "select version()"
        if (!in_array($name, get_class_methods("DB_common"))) {
            $finder = new FileFinder;
            $dir = dirname(__FILE__) . "/../../pear";
            $finder->_prepend_to_include_path($dir);
            include_once("$dir/DB/common.php"); // use our version instead.
            if (!in_array($name, get_class_methods("DB_common"))) {
                $pearFinder = new PearFileFinder("lib/pear");
                $pearFinder->includeOnce('DB.php');
            } else {
                include_once("$dir/DB.php");
            }
        } else {
            include_once("DB.php");
        }

        // Install filter to handle bogus error notices from buggy DB.php's.
        // TODO: check the Pear_DB version, but how?
        if (0) {
            global $ErrorManager;
            $ErrorManager->pushErrorHandler(new WikiMethodCb($this, '_pear_notice_filter'));
            $this->_pearerrhandler = true;
        }

        $this->_lock_count = 0;
        $this->_dbh = DB::connect();
        $dbh = &$this->_dbh;
        if (DB::isError($dbh)) {
            trigger_error(
                sprintf(
                    "Can't connect to database: %s",
                    $this->_pear_error_message($dbh)
                ),
                E_USER_ERROR
            );
        }
        $dbh->setErrorHandling(
            PEAR_ERROR_CALLBACK,
            array($this, '_pear_error_callback')
        );
        $dbh->setFetchMode(DB_FETCHMODE_ASSOC);

        $this->_table_names
            = array('page_tbl'     => self::DATABASE_TABLE_PREFIX . 'page',
                    'version_tbl'  => self::DATABASE_TABLE_PREFIX . 'version',
                    'link_tbl'     => self::DATABASE_TABLE_PREFIX . 'link',
                    'recent_tbl'   => self::DATABASE_TABLE_PREFIX . 'recent',
                    'nonempty_tbl' => self::DATABASE_TABLE_PREFIX . 'nonempty');
        $page_tbl = $this->_table_names['page_tbl'];
        $version_tbl = $this->_table_names['version_tbl'];
        $this->page_tbl_fields = "$page_tbl.id AS id, $page_tbl.pagename AS pagename, $page_tbl.hits AS hits, $page_tbl.group_id AS group_id";
        $this->version_tbl_fields = "$version_tbl.version AS version, $version_tbl.mtime AS mtime, " .
            "$version_tbl.minor_edit AS minor_edit, $version_tbl.content AS content, $version_tbl.versiondata AS versiondata";

        $this->_expressions
            = array('maxmajor'     => "MAX(CASE WHEN minor_edit=0 THEN version END)",
                    'maxminor'     => "MAX(CASE WHEN minor_edit<>0 THEN version END)",
                    'maxversion'   => "MAX(version)",
                    'notempty'     => "<>''",
                    'iscontent'    => "content<>''");
    }

    /**
     * Close database connection.
     */
    public function close()
    {
        if (!$this->_dbh) {
            return;
        }
        if ($this->_lock_count) {
            trigger_error(
                "WARNING: database still locked " . '(lock_count = $this->_lock_count)' . "\n<br />",
                E_USER_WARNING
            );
        }
        $this->_dbh->setErrorHandling(PEAR_ERROR_PRINT);    // prevent recursive loops.
        $this->unlock('force');

        $this->_dbh->disconnect();

        if (!empty($this->_pearerrhandler)) {
            $GLOBALS['ErrorManager']->popErrorHandler();
        }
    }


    /*
     * Test fast wikipage.
     */
    public function is_wiki_page($pagename)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        return $dbh->getOne(sprintf(
            "SELECT $page_tbl.id as id"
                                    . " FROM $nonempty_tbl, $page_tbl"
                                    . " WHERE $nonempty_tbl.id=$page_tbl.id"
                                    . "   AND pagename='%s'"
                                    . "   AND $page_tbl.group_id=%d",
            $dbh->escapeSimple($pagename),
            GROUP_ID
        ));
    }

    public function get_all_pagenames()
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        return $dbh->getCol("SELECT pagename"
                            . " FROM $nonempty_tbl, $page_tbl"
                            . " WHERE $nonempty_tbl.id=$page_tbl.id"
                            . "   AND $page_tbl.group_id=" . GROUP_ID);
    }

    public function numPages($filter = false, $exclude = '')
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        return $dbh->getOne("SELECT count(*)"
                            . " FROM $nonempty_tbl, $page_tbl"
                            . " WHERE $nonempty_tbl.id=$page_tbl.id"
                            . "   AND $page_tbl.group_id=" . GROUP_ID);
    }

    public function increaseHitCount($pagename)
    {
        $dbh = &$this->_dbh;
        // Hits is the only thing we can update in a fast manner.
        // Note that this will fail silently if the page does not
        // have a record in the page table.  Since it's just the
        // hit count, who cares?
        $dbh->query(sprintf(
            "UPDATE %s SET hits=hits+1 WHERE pagename='%s' AND group_id=%d",
            $this->_table_names['page_tbl'],
            $dbh->escapeSimple($pagename),
            GROUP_ID
        ));
        return;
    }

    /**
     * Read page information from database.
     */
    public function get_pagedata($pagename)
    {
        $dbh = &$this->_dbh;
        //trigger_error("GET_PAGEDATA $pagename", E_USER_NOTICE);
        $result = $dbh->getRow(
            sprintf(
                "SELECT hits,pagedata FROM %s WHERE pagename='%s' AND group_id=%d",
                $this->_table_names['page_tbl'],
                $dbh->escapeSimple($pagename),
                GROUP_ID
            ),
            DB_FETCHMODE_ASSOC
        );
        return $result ? $this->_extract_page_data($result) : false;
    }

    public function _extract_page_data($data)
    {
        if (empty($data)) {
            return array();
        } elseif (empty($data['pagedata'])) {
            return $data;
        } else {
            $data = array_merge($data, $this->_unserialize($data['pagedata']));
            unset($data['pagedata']);
            return $data;
        }
    }

    public function update_pagedata($pagename, $newdata)
    {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];

        // Hits is the only thing we can update in a fast manner.
        if (count($newdata) == 1 && isset($newdata['hits'])) {
            // Note that this will fail silently if the page does not
            // have a record in the page table.  Since it's just the
            // hit count, who cares?
            $dbh->query(sprintf(
                "UPDATE $page_tbl SET hits=%d WHERE pagename='%s' AND group_id=%d",
                $newdata['hits'],
                $dbh->escapeSimple($pagename),
                GROUP_ID
            ));
            return;
        }

        $this->lock(array($page_tbl), true);
        $data = $this->get_pagedata($pagename);
        if (!$data) {
            $data = array();
            $this->_get_pageid($pagename, true); // Creates page record
        }

        @$hits = (int) $data['hits'];
        unset($data['hits']);

        foreach ($newdata as $key => $val) {
            if ($key == 'hits') {
                $hits = (int) $val;
            } elseif (empty($val)) {
                unset($data[$key]);
            } else {
                $data[$key] = $val;
            }
        }

        /* Portability issue -- not all DBMS supports huge strings
         * so we need to 'bind' instead of building a simple SQL statment.
         * Note that we do not need to escapeSimple when we bind
        $dbh->query(sprintf("UPDATE $page_tbl"
                            . " SET hits=%d, pagedata='%s'"
                            . " WHERE pagename='%s'",
                            $hits,
                            $dbh->escapeSimple($this->_serialize($data)),
                            $dbh->escapeSimple($pagename)));
        */
        $sth = $dbh->query(
            "UPDATE $page_tbl"
                           . " SET hits=?, pagedata=?"
                           . " WHERE pagename=?"
                           . "   AND group_id=?",
            array($hits, $this->_serialize($data), $pagename, GROUP_ID)
        );
        $this->unlock(array($page_tbl));
    }

    public function get_cached_html($pagename)
    {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        return $dbh->GetOne(sprintf(
            "SELECT cached_html FROM $page_tbl WHERE pagename='%s' AND group_id=%d",
            $dbh->escapeSimple($pagename),
            GROUP_ID
        ));
    }

    public function set_cached_html($pagename, $data)
    {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        $sth = $dbh->query(
            "UPDATE $page_tbl"
                           . " SET cached_html=?"
                           . " WHERE pagename=?"
                           . "   AND group_id=?",
            array($data, $pagename, GROUP_ID)
        );
    }

    public function _get_pageid($pagename, $create_if_missing = false)
    {
        // check id_cache
        global $request;
        $cache = $request->_dbi->_cache->_id_cache;
        if (isset($cache[$pagename])) {
            if ($cache[$pagename] or !$create_if_missing) {
                return $cache[$pagename];
            }
        }

        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];

        $query = sprintf(
            "SELECT id FROM $page_tbl WHERE pagename='%s' AND group_id=%d",
            $dbh->escapeSimple($pagename),
            GROUP_ID
        );

        if (!$create_if_missing) {
            return $dbh->getOne($query);
        }

        $id = $dbh->getOne($query);
        if (empty($id)) {
            $this->lock(array($page_tbl), true); // write lock
            $max_id = $dbh->getOne("SELECT MAX(id) FROM $page_tbl");
            $id = $max_id + 1;
            $dbh->query(sprintf(
                "INSERT INTO $page_tbl"
                                . " (id,pagename,hits,group_id)"
                                . " VALUES (%d,'%s',0,%d)",
                $id,
                $dbh->escapeSimple($pagename),
                GROUP_ID
            ));
            $this->unlock(array($page_tbl));
        }
        return $id;
    }

    public function get_latest_version($pagename)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        return
            (int) $dbh->getOne(sprintf(
                "SELECT latestversion"
                                      . " FROM $page_tbl, $recent_tbl"
                                      . " WHERE $page_tbl.id=$recent_tbl.id"
                                      . "  AND pagename='%s'"
                                      . "  AND $page_tbl.group_id=%d",
                $dbh->escapeSimple($pagename),
                GROUP_ID
            ));
    }

    public function get_previous_version($pagename, $version)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        return
            (int) $dbh->getOne(sprintf(
                "SELECT version"
                                      . " FROM $version_tbl, $page_tbl"
                                      . " WHERE $version_tbl.id=$page_tbl.id"
                                      . "  AND pagename='%s'"
                                      . "  AND version < %d"
                                      . "  AND $page_tbl.group_id=%d"
                                      . " ORDER BY version DESC",
                /* Non portable and useless anyway with getOne
                                      . " LIMIT 1",
                                      */
                                      $dbh->escapeSimple($pagename),
                $version,
                GROUP_ID
            ));
    }

    /**
     * Get version data.
     *
     * @param $version int Which version to get.
     *
     * @return hash The version data, or false if specified version does not
     *              exist.
     */
    public function get_versiondata($pagename, $version, $want_content = false)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        extract($this->_expressions);

        assert(is_string($pagename) and $pagename != "");
        assert($version > 0);

        //trigger_error("GET_REVISION $pagename $version $want_content", E_USER_NOTICE);
        // FIXME: optimization: sometimes don't get page data?
        if ($want_content) {
            $fields = $this->page_tbl_fields
                . ",$page_tbl.pagedata as pagedata,"
                . $this->version_tbl_fields;
        } else {
            $fields = $this->page_tbl_fields . ","
                . "mtime, minor_edit, versiondata,"
                . "$iscontent AS have_content";
        }

        $result = $dbh->getRow(
            sprintf(
                "SELECT $fields"
                                       . " FROM $page_tbl, $version_tbl"
                                       . " WHERE $page_tbl.id=$version_tbl.id"
                                       . "  AND pagename='%s'"
                                       . "  AND version=%d"
                                       . "  AND group_id=%d",
                $dbh->escapeSimple($pagename),
                $version,
                GROUP_ID
            ),
            DB_FETCHMODE_ASSOC
        );

        return $this->_extract_version_data($result);
    }

    public function _extract_version_data($query_result)
    {
        if (!$query_result) {
            return false;
        }

        $data = $this->_unserialize($query_result['versiondata']);

        $data['mtime'] = $query_result['mtime'];
        $data['is_minor_edit'] = !empty($query_result['minor_edit']);

        if (isset($query_result['content'])) {
            $data['%content'] = $query_result['content'];
        } elseif ($query_result['have_content']) {
            $data['%content'] = true;
        } else {
            $data['%content'] = '';
        }

        // FIXME: this is ugly.
        if (isset($query_result['pagedata'])) {
            // Query also includes page data.
            // We might as well send that back too...
            unset($query_result['versiondata']);
            $data['%pagedata'] = $this->_extract_page_data($query_result);
        }

        return $data;
    }


    /**
     * Create a new revision of a page.
     */
    public function set_versiondata($pagename, $version, $data)
    {
        $dbh = &$this->_dbh;
        $version_tbl = $this->_table_names['version_tbl'];

        $minor_edit = (int) !empty($data['is_minor_edit']);
        unset($data['is_minor_edit']);

        $mtime = (int) $data['mtime'];
        unset($data['mtime']);
        assert(!empty($mtime));

        @$content = (string) $data['%content'];
        unset($data['%content']);

        unset($data['%pagedata']);

        $this->lock();
        $id = $this->_get_pageid($pagename, true);

        // FIXME: optimize: mysql can do this with one REPLACE INTO (I think).
        $dbh->query(sprintf(
            "DELETE FROM $version_tbl"
                            . " WHERE id=%d AND version=%d",
            $id,
            $version
        ));

        /* mysql optimized version.
        $dbh->query(sprintf("INSERT INTO $version_tbl"
                            . " (id,version,mtime,minor_edit,content,versiondata)"
                            . " VALUES(%d,%d,%d,%d,'%s','%s')",
                            $id, $version, $mtime, $minor_edit,
                            $dbh->quoteSmart($content),
                            $dbh->quoteSmart($this->_serialize($data))));
        */
        // generic slow PearDB bind eh quoting.
        $dbh->query(
            "INSERT INTO $version_tbl"
                    . " (id,version,mtime,minor_edit,content,versiondata)"
                    . " VALUES(?, ?, ?, ?, ?, ?)",
            array($id, $version, $mtime, $minor_edit, $content,
            $this->_serialize($data))
        );

        $this->_update_recent_table($id);
        $this->_update_nonempty_table($id);

        $this->unlock();
    }

    /**
     * Delete an old revision of a page.
     */
    public function delete_versiondata($pagename, $version)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $this->lock();
        if (($id = $this->_get_pageid($pagename))) {
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
    public function purge_page($pagename)
    {
        $dbh = $this->_dbh;
        extract($this->_table_names);

        $this->lock();
        if (($id = $this->_get_pageid($pagename, false))) {
            $dbh->query("DELETE FROM $version_tbl  WHERE id=$id");
            $dbh->query("DELETE FROM $recent_tbl   WHERE id=$id");
            $dbh->query("DELETE FROM $nonempty_tbl WHERE id=$id");
            $dbh->query("DELETE FROM $link_tbl     WHERE linkfrom=$id");
            $nlinks = $dbh->getOne("SELECT COUNT(*) FROM $link_tbl WHERE linkto=$id");
            if ($nlinks) {
                // We're still in the link table (dangling link) so we can't delete this
                // altogether.
                $dbh->query("UPDATE $page_tbl SET hits=0, pagedata='' WHERE id=$id");
                $result = 0;
            } else {
                $dbh->query("DELETE FROM $page_tbl WHERE id=$id");
                $result = 1;
            }
            $this->_update_recent_table();
            $this->_update_nonempty_table();
        } else {
            $result = -1; // already purged or not existing
        }
        $this->unlock();
        return $result;
    }

    // The only thing we might be interested in updating which we can
    // do fast in the flags (minor_edit).   I think the default
    // update_versiondata will work fine...
    //function update_versiondata($pagename, $version, $data) {
    //}

    public function set_links($pagename, $links)
    {
        // Update link table.
        // FIXME: optimize: mysql can do this all in one big INSERT.

        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $this->lock();
        $pageid = $this->_get_pageid($pagename, true);

        $dbh->query("DELETE FROM $link_tbl WHERE linkfrom=$pageid");

        if ($links) {
            foreach ($links as $link) {
                // avoid duplicates
                if (isset($linkseen[$link])) {
                    continue;
                }
                $linkseen[$link] = true;
                $linkid = $this->_get_pageid($link, true);
                assert($linkid);
                $dbh->query("INSERT INTO $link_tbl (linkfrom, linkto)"
                            . " VALUES ($pageid, $linkid)");
            }
        }
        $this->unlock();
    }

    /**
     * Find pages which link to or are linked from a page.
     */
    public function get_links(
        $pagename,
        $reversed = true,
        $include_empty = false,
        $sortby = false,
        $limit = false,
        $exclude = ''
    ) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        if ($reversed) {
            list($have,$want) = array('linkee', 'linker');
        } else {
            list($have,$want) = array('linker', 'linkee');
        }
        $orderby = $this->sortby($sortby, 'db', array('pagename'));
        if ($orderby) {
            $orderby = ' ORDER BY $want.' . $orderby;
        }
        if ($exclude) { // array of pagenames
            $exclude = " AND $want.pagename NOT IN " . $this->_sql_set($exclude);
        } else {
            $exclude = '';
        }

        $qpagename = $dbh->escapeSimple($pagename);
        $sql = "SELECT $want.id AS id, $want.pagename AS pagename "
            . " FROM $link_tbl, $page_tbl linker, $page_tbl linkee"
            . (!$include_empty ? ", $nonempty_tbl" : '')
            . " WHERE linkfrom=linker.id AND linkto=linkee.id"
            . " AND $have.pagename='$qpagename'"
            . " AND linker.group_id=" . GROUP_ID
            . " AND linkee.group_id=" . GROUP_ID
            . (!$include_empty ? " AND $nonempty_tbl.id=$want.id" : "")
            //. " GROUP BY $want.id"
            . $exclude
            . $orderby;
        if ($limit) {
            // extract from,count from limit
            list($from,$count) = $this->limit($limit);
            $result = $dbh->limitQuery($sql, $from, $count);
        } else {
            $result = $dbh->query($sql);
        }

        return new WikiDB_backend_PearDB_iter($this, $result);
    }

    /**
     * Find if a page links to another page
     */
    public function exists_link($pagename, $link, $reversed = false)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        if ($reversed) {
            list($have, $want) = array('linkee', 'linker');
        } else {
            list($have, $want) = array('linker', 'linkee');
        }
        $qpagename = $dbh->escapeSimple($pagename);
        $qlink = $dbh->escapeSimple($link);
        $row = $dbh->GetRow("SELECT IF($want.pagename,1,0) as result"
             . " FROM $link_tbl, $page_tbl linker, $page_tbl linkee, $nonempty_tbl"
             . " WHERE linkfrom=linker.id AND linkto=linkee.id"
             . " AND $have.pagename='$qpagename'"
             . " AND $want.pagename='$qlink'"
             . " AND $want.group_id=" . GROUP_ID
             . " AND $have.group_id=" . GROUP_ID
        . " LIMIT 1");
        return $row['result'];
    }

    public function get_all_pages($include_empty = false, $sortby = false, $limit = false, $exclude = '')
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $orderby = $this->sortby($sortby, 'db');
        if ($orderby) {
            $orderby = ' ORDER BY ' . $orderby;
        }
        if ($exclude) { // array of pagenames
            $exclude = " AND $page_tbl.pagename NOT IN " . $this->_sql_set($exclude);
        } else {
            $exclude = '';
        }

        if (strstr($orderby, 'mtime ')) { // multiple columns possible
            if ($include_empty) {
                $sql = "SELECT "
                    . $this->page_tbl_fields
                    . " FROM $page_tbl, $recent_tbl, $version_tbl"
                    . " WHERE $page_tbl.id=$recent_tbl.id"
                    . " AND $page_tbl.id=$version_tbl.id AND latestversion=version AND $page_tbl.group_id=" . GROUP_ID
                    . $exclude
                    . $orderby;
            } else {
                $sql = "SELECT "
                    . $this->page_tbl_fields
                    . " FROM $nonempty_tbl, $page_tbl, $recent_tbl, $version_tbl"
                    . " WHERE $nonempty_tbl.id=$page_tbl.id"
                    . " AND $page_tbl.id=$recent_tbl.id"
                    . " AND $page_tbl.id=$version_tbl.id AND latestversion=version AND $page_tbl.group_id=" . GROUP_ID
                    . $exclude
                    . $orderby;
            }
        } else {
            if ($include_empty) {
                $sql = "SELECT "
                    . $this->page_tbl_fields
                    . " FROM $page_tbl"
                    . ($exclude ? " WHERE $exclude AND $page_tbl.group_id=" . GROUP_ID : " WHERE $page_tbl.group_id=" . GROUP_ID)
                    . $orderby;
            } else {
                $sql = "SELECT "
                    . $this->page_tbl_fields
                    . " FROM $nonempty_tbl, $page_tbl"
                    . " WHERE $nonempty_tbl.id=$page_tbl.id"
                    . "  AND $page_tbl.group_id=" . GROUP_ID
                    . $exclude
                    . $orderby;
            }
        }
        if ($limit) {
            // extract from,count from limit
            list($from,$count) = $this->limit($limit);
            $result = $dbh->limitQuery($sql, $from, $count);
        } else {
            $result = $dbh->query($sql);
        }
        return new WikiDB_backend_PearDB_iter($this, $result);
    }

    /**
     * Title search.
     */
    public function text_search($search, $fulltext = false, $sortby = false, $limit = false, $exclude = false)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        $orderby = $this->sortby($sortby, 'db');
        if ($orderby) {
            $orderby = ' ORDER BY ' . $orderby;
        }
        //else " ORDER BY rank($field, to_tsquery('$searchon')) DESC";

        $searchclass = static::class . "_search";
        // no need to define it everywhere and then fallback. memory!
        if (!class_exists($searchclass)) {
            $searchclass = "WikiDB_backend_PearDB_search";
        }
        $searchobj = new $searchclass($search, $dbh);

        $table = "$nonempty_tbl, $page_tbl";
        $join_clause = "$nonempty_tbl.id=$page_tbl.id";
        $join_clause .= " AND $page_tbl.group_id=" . GROUP_ID;
        $fields = $this->page_tbl_fields;

        if ($fulltext) {
            $table .= ", $recent_tbl";
            $join_clause .= " AND $page_tbl.id=$recent_tbl.id";

            $table .= ", $version_tbl";
            $join_clause .= " AND $page_tbl.id=$version_tbl.id AND latestversion=version";

            $fields .= ", $page_tbl.pagedata as pagedata, " . $this->version_tbl_fields;
            $callback = new WikiMethodCb($searchobj, "_fulltext_match_clause");
        } else {
            $callback = new WikiMethodCb($searchobj, "_pagename_match_clause");
        }
        $search_clause = $search->makeSqlClauseObj($callback);

        $sql = "SELECT $fields FROM $table"
            . " WHERE $join_clause"
            . "  AND ($search_clause)"
            . $orderby;
        if ($limit) {
            list($from, $count) = $this->limit($limit);
            $result = $dbh->limitQuery($sql, $from, $count);
        } else {
            $result = $dbh->query($sql);
        }

        $iter = new WikiDB_backend_PearDB_iter($this, $result);
        $iter->stoplisted = @$searchobj->stoplisted;
        return $iter;
    }

    //Todo: check if the better Mysql MATCH operator is supported,
    // (ranked search) and also google like expressions.
    public function _sql_match_clause($word)
    {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->escapeSimple($word);
        //$page_tbl = $this->_table_names['page_tbl'];
        //Note: Mysql 4.1.0 has a bug which fails with binary fields.
        //      e.g. if word is lowercased.
        // http://bugs.mysql.com/bug.php?id=1491
        return "LOWER(pagename) LIKE '%$word%'";
    }
    public function _sql_casematch_clause($word)
    {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->escapeSimple($word);
        return "pagename LIKE '%$word%'";
    }
    public function _fullsearch_sql_match_clause($word)
    {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->escapeSimple($word);
        //$page_tbl = $this->_table_names['page_tbl'];
        //Mysql 4.1.1 has a bug which fails here if word is lowercased.
        return "LOWER(pagename) LIKE '%$word%' OR content LIKE '%$word%'";
    }
    public function _fullsearch_sql_casematch_clause($word)
    {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->escapeSimple($word);
        return "pagename LIKE '%$word%' OR content LIKE '%$word%'";
    }

    /**
     * Find highest or lowest hit counts.
     */
    public function most_popular($limit = 20, $sortby = '-hits')
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        if ($limit < 0) {
            $order = "hits ASC";
            $limit = -$limit;
            $where = "";
        } else {
            $order = "hits DESC";
            $where = " AND hits > 0";
        }
        $orderby = '';
        if ($sortby != '-hits') {
            if ($order = $this->sortby($sortby, 'db')) {
                $orderby = " ORDER BY " . $order;
            }
        } else {
            $orderby = " ORDER BY $order";
        }
        //$limitclause = $limit ? " LIMIT $limit" : '';
        $sql = "SELECT "
            . $this->page_tbl_fields
            . " FROM $nonempty_tbl, $page_tbl"
            . " WHERE $nonempty_tbl.id=$page_tbl.id"
            . "  AND $page_tbl.group_id=" . GROUP_ID
            . $where
            . $orderby;
        if ($limit) {
            list($from, $count) = $this->limit($limit);
            $result = $dbh->limitQuery($sql, $from, $count);
        } else {
            $result = $dbh->query($sql);
        }

        return new WikiDB_backend_PearDB_iter($this, $result);
    }

    /**
     * Find recent changes.
     */
    public function most_recent($params)
    {
        $limit = 0;
        $since = 0;
        $include_minor_revisions = false;
        $exclude_major_revisions = false;
        $include_all_revisions = false;
        extract($params);

        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $pick = array();
        if ($since) {
            $pick[] = "mtime >= $since";
        }

        if ($include_all_revisions) {
            // Include all revisions of each page.
            $table = "$page_tbl, $version_tbl";
            $join_clause = "$page_tbl.id=$version_tbl.id";
            $join_clause .= " AND $page_tbl.group_id=" . GROUP_ID;
            if ($exclude_major_revisions) {
        // Include only minor revisions
                $pick[] = "minor_edit <> 0";
            } elseif (!$include_minor_revisions) {
        // Include only major revisions
                $pick[] = "minor_edit = 0";
            }
        } else {
            $table = "$page_tbl, $recent_tbl";
            $join_clause = "$page_tbl.id=$recent_tbl.id";
            $join_clause .= " AND $page_tbl.group_id=" . GROUP_ID;
            $table .= ", $version_tbl";
            $join_clause .= " AND $version_tbl.id=$page_tbl.id";

            if ($exclude_major_revisions) {
                // Include only most recent minor revision
                $pick[] = 'version=latestminor';
            } elseif (!$include_minor_revisions) {
                // Include only most recent major revision
                $pick[] = 'version=latestmajor';
            } else {
                // Include only the latest revision (whether major or minor).
                $pick[] = 'version=latestversion';
            }
        }
        $order = "DESC";
        if ($limit < 0) {
            $order = "ASC";
            $limit = -$limit;
        }
        // $limitclause = $limit ? " LIMIT $limit" : '';
        $where_clause = $join_clause;
        if ($pick) {
            $where_clause .= " AND " . join(" AND ", $pick);
        }

        // FIXME: use SQL_BUFFER_RESULT for mysql?
        $sql = "SELECT "
               . $this->page_tbl_fields . ", " . $this->version_tbl_fields
               . " FROM $table"
               . " WHERE $where_clause"
               . " ORDER BY mtime $order";
        if ($limit) {
             list($from, $count) = $this->limit($limit);
             $result = $dbh->limitQuery($sql, $from, $count);
        } else {
            $result = $dbh->query($sql);
        }
        return new WikiDB_backend_PearDB_iter($this, $result);
    }

    /**
     * Find referenced empty pages.
     */
    public function wanted_pages($exclude_from = '', $exclude = '', $sortby = false, $limit = false)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        if ($orderby = $this->sortby($sortby, 'db', array('pagename','wantedfrom'))) {
            $orderby = 'ORDER BY ' . $orderby;
        }

        if ($exclude_from) { // array of pagenames
            $exclude_from = " AND pp.pagename NOT IN " . $this->_sql_set($exclude_from);
        }
        if ($exclude) { // array of pagenames
            $exclude = " AND p.pagename NOT IN " . $this->_sql_set($exclude);
        }
        $sql = "SELECT p.pagename, pp.pagename as wantedfrom"
            . " FROM $page_tbl p, $link_tbl linked "
            . " LEFT JOIN $page_tbl pp ON linked.linkto = pp.id"
            . " LEFT JOIN $nonempty_tbl ne ON linked.linkto = ne.id"
            . " WHERE ne.id is NULL"
        .       " AND p.id = linked.linkfrom"
            .       " AND p.group_id = " . GROUP_ID
            . $exclude_from
            . $exclude
            . $orderby;
        if ($limit) {
            list($from, $count) = $this->limit($limit);
            $result = $dbh->limitQuery($sql, $from, $count * 3);
        } else {
            $result = $dbh->query($sql);
        }
        return new WikiDB_backend_PearDB_generic_iter($this, $result);
    }

    public function _sql_set(&$pagenames)
    {
        $s = '(';
        foreach ($pagenames as $p) {
            $s .= ("'" . $this->_dbh->escapeSimple($p) . "',");
        }
        return substr($s, 0, -1) . ")";
    }

    /**
     * Rename page in the database.
     */
    public function rename_page($pagename, $to)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $this->lock();
        if (($id = $this->_get_pageid($pagename, false))) {
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
            $dbh->query(sprintf(
                "UPDATE $page_tbl SET pagename='%s' WHERE id=$id",
                $dbh->escapeSimple($to)
            ));
        }
        $this->unlock();
        return $id;
    }

    public function _update_recent_table($pageid = false)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        extract($this->_expressions);

        $pageid = (int) $pageid;

        $this->lock();
        $dbh->query("DELETE FROM $recent_tbl"
                    . ( $pageid ? " WHERE id=$pageid" : ""));
        $dbh->query("INSERT INTO $recent_tbl"
                     . " (id, latestversion, latestmajor, latestminor)"
                     . " SELECT id, $maxversion, $maxmajor, $maxminor"
                     . " FROM $version_tbl"
                     . ( $pageid ? " WHERE id=$pageid" : "")
                     . " GROUP BY id");
        $this->unlock();
    }

    public function _update_nonempty_table($pageid = false)
    {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $pageid = (int) $pageid;

        extract($this->_expressions);
        $this->lock();
        $dbh->query("DELETE FROM $nonempty_tbl"
                    . ( $pageid ? " WHERE id=$pageid" : " USING $nonempty_tbl JOIN $page_tbl USING (id) WHERE $page_tbl.group_id=" . GROUP_ID ));
        $dbh->query("INSERT INTO $nonempty_tbl (id)"
                    . " SELECT $recent_tbl.id"
                    . " FROM $recent_tbl, $version_tbl"
                    . ( $pageid ? "" : " JOIN $page_tbl USING (id) ")
                    . " WHERE $recent_tbl.id=$version_tbl.id"
                    . "       AND version=latestversion"
                    // We have some specifics here (Oracle)
                    //. "  AND content<>''"
                    . "  AND content $notempty"
                    . ( $pageid ? " AND $recent_tbl.id=$pageid" : " AND $page_tbl.group_id=" . GROUP_ID ));

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
    public function lock($tables = false, $write_lock = true)
    {
        if ($this->_lock_count++ == 0) {
            $this->_lock_tables($write_lock);
        }
    }

    /**
     * Actually lock the required tables.
     */
    public function _lock_tables($write_lock)
    {
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
    public function unlock($tables = false, $force = false)
    {
        if ($this->_lock_count == 0) {
            return;
        }
        if (--$this->_lock_count <= 0 || $force) {
            $this->_unlock_tables();
            $this->_lock_count = 0;
        }
    }

    /**
     * Actually unlock the required tables.
     */
    public function _unlock_tables($write_lock)
    {
        trigger_error("virtual", E_USER_ERROR);
    }


    /**
     * Serialize data
     */
    public function _serialize($data)
    {
        if (empty($data)) {
            return '';
        }
        assert(is_array($data));
        return serialize($data);
    }

    /**
     * Unserialize data
     */
    public function _unserialize($data)
    {
        $s = empty($data) ? array() : @unserialize($data);
        if ($s === false) {
            //Fix errors due to utf8 . See http://php.net/unserialize comments
            $s = unserialize(
                preg_replace_callback(
                    '!s:(\d+):"(.*?)";!s',
                    function (array $matches) {
                        $length = strlen($matches[2]);
                        return "s:$length:\"{$matches[2]}\";";
                    },
                    $data
                )
            );
        }
        return $s;
    }

    /**
     * Callback for PEAR (DB) errors.
     *
     * @access protected
     *
     * @param A PEAR_error object.
     */
    public function _pear_error_callback($error)
    {
        if ($this->_is_false_error($error)) {
            return;
        }

        $this->_dbh->setErrorHandling(PEAR_ERROR_PRINT);    // prevent recursive loops.
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
    public function _is_false_error($error)
    {
        if ($error->getCode() != DB_ERROR) {
            return false;
        }

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

    public function _pear_error_message($error)
    {
        $class = static::class;
        $message = "$class: fatal database error\n"
             . "\t" . $error->getMessage() . "\n"
             . "\t(" . $error->getDebugInfo() . ")\n";

        // Prevent password from being exposed during a connection error
        $safe_dsn = preg_replace(
            '| ( :// .*? ) : .* (?=@) |xs',
            '\\1:XXXXXXXX',
            $this->_dsn
        );
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
    public function _pear_notice_filter($err)
    {
        return ( $err->isNotice()
                 && preg_match('|DB[/\\\\]common.php$|', $err->errfile)
                 && $err->errline == 126
                 && preg_match('/Undefined offset: +0\b/', $err->errstr) );
    }

    /* some variables and functions for DB backend abstraction (action=upgrade) */
    public function database()
    {
        return $this->_dbh->dsn['database'];
    }
    public function backendType()
    {
        return $this->_dbh->phptype;
    }
    public function connection()
    {
        return $this->_dbh->connection;
    }
    public function getRow($query)
    {
        return $this->_dbh->getRow($query);
    }

    public function listOfTables()
    {
        return $this->_dbh->getListOf('tables');
    }
}

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
class WikiDB_backend_PearDB_generic_iter extends WikiDB_backend_iterator
{
    public function __construct(
        $backend,
        $query_result,
        $field_list = null
    ) {
        if (DB::isError($query_result)) {
            // This shouldn't happen, I thought.
            $backend->_pear_error_callback($query_result);
        }

        $this->_backend = &$backend;
        $this->_result = $query_result;
    }

    public function count()
    {
        if (!$this->_result) {
            return false;
        }
        return $this->_result->numRows();
    }

    public function next()
    {
        $backend = &$this->_backend;
        if (!$this->_result) {
            return false;
        }

        $record = $this->_result->fetchRow(DB_FETCHMODE_ASSOC);
        if (!$record) {
            $this->free();
            return false;
        }

        return $record;
    }

    public function free()
    {
        if ($this->_result) {
            $this->_result->free();
            $this->_result = false;
        }
    }
}

class WikiDB_backend_PearDB_iter extends WikiDB_backend_PearDB_generic_iter
{

    public function next()
    {
        $backend = &$this->_backend;
        if (!$this->_result) {
            return false;
        }

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

class WikiDB_backend_PearDB_search extends WikiDB_backend_search_sql
{
    // no surrounding quotes because we know it's a string
    // function _quote($word) { return $this->_dbh->addq($word); }
}

// $Log: PearDB.php,v $
// Revision 1.94  2005/11/14 22:24:33  rurban
// fix fulltext search,
// Eliminate stoplist words,
//
// Revision 1.93  2005/10/10 19:42:15  rurban
// fix wanted_pages SQL syntax
//
// Revision 1.92  2005/09/14 06:04:43  rurban
// optimize searching for ALL (ie %), use the stoplist on PDO
//
// Revision 1.91  2005/09/11 14:55:05  rurban
// implement fulltext stoplist
//
// Revision 1.90  2005/09/11 13:25:12  rurban
// enhance LIMIT support
//
// Revision 1.89  2005/09/10 21:30:16  rurban
// enhance titleSearch
//
// Revision 1.88  2005/08/06 13:20:05  rurban
// add comments
//
// Revision 1.87  2005/02/10 19:04:24  rurban
// move getRow up one level to our backend class
//
// Revision 1.86  2005/01/29 19:51:02  rurban
// Bugs item #1077769 fixed by frugal.
// Deleted the wrong page. Fix all other tables also.
//
// Revision 1.85  2005/01/25 08:03:35  rurban
// support DATABASE_PERSISTENT besides dsn database?persistent=false; move lock_count up (per Charles Corrigan)
//
// Revision 1.84  2005/01/18 20:55:47  rurban
// reformatting and two bug fixes: adding missing parens
//
// Revision 1.83  2005/01/18 10:11:29  rurban
// Oops. Again thanks to Charles Corrigan
//
// Revision 1.82  2005/01/18 08:55:51  rurban
// fix quoting
//
// Revision 1.81  2005/01/17 08:53:09  rurban
// pagedata fix by Charles Corrigan
//
// Revision 1.80  2004/12/22 18:33:31  rurban
// fix page _id_cache logic for _get_pageid create_if_missing
//
// Revision 1.79  2004/12/10 02:45:27  rurban
// SQL optimization:
//   put _cached_html from pagedata into a new seperate blob, not huge serialized string.
//   it is only rarelely needed: for current page only, if-not-modified
//   but was extracted for every simple page iteration.
//
// Revision 1.78  2004/12/08 12:55:51  rurban
// support new non-destructive delete_page via generic backend method
//
// Revision 1.77  2004/12/06 19:50:04  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.76  2004/11/30 17:45:53  rurban
// exists_links backend implementation
//
// Revision 1.75  2004/11/28 20:42:33  rurban
// Optimize PearDB _extract_version_data and _extract_page_data.
//
// Revision 1.74  2004/11/27 14:39:05  rurban
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
// Revision 1.73  2004/11/26 18:39:02  rurban
// new regex search parser and SQL backends (90% complete, glob and pcre backends missing)
//
// Revision 1.72  2004/11/25 17:20:51  rurban
// and again a couple of more native db args: backlinks
//
// Revision 1.71  2004/11/23 13:35:48  rurban
// add case_exact search
//
// Revision 1.70  2004/11/21 11:59:26  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.69  2004/11/20 17:49:39  rurban
// add fast exclude support to SQL get_all_pages
//
// Revision 1.68  2004/11/20 17:35:58  rurban
// improved WantedPages SQL backends
// PageList::sortby new 3rd arg valid_fields (override db fields)
// WantedPages sql pager inexact for performance reasons:
//   assume 3 wantedfrom per page, to be correct, no getTotal()
// support exclude argument for get_all_pages, new _sql_set()
//
// Revision 1.67  2004/11/10 19:32:24  rurban
// * optimize increaseHitCount, esp. for mysql.
// * prepend dirs to the include_path (phpwiki_dir for faster searches)
// * Pear_DB version logic (awful but needed)
// * fix broken ADODB quote
// * _extract_page_data simplification
//
// Revision 1.66  2004/11/10 15:29:21  rurban
// * requires newer Pear_DB (as the internal one): quote() uses now escapeSimple for strings
// * ACCESS_LOG_SQL: fix cause request not yet initialized
// * WikiDB: moved SQL specific methods upwards
// * new Pear_DB quoting: same as ADODB and as newer Pear_DB.
//   fixes all around: WikiGroup, WikiUserNew SQL methods, SQL logging
//
// Revision 1.65  2004/11/09 17:11:17  rurban
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
// Revision 1.64  2004/11/07 16:02:52  rurban
// new sql access log (for spam prevention), and restructured access log class
// dbh->quote (generic)
// pear_db: mysql specific parts seperated (using replace)
//
// Revision 1.63  2004/11/01 10:43:58  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.62  2004/10/14 19:19:34  rurban
// loadsave: check if the dumped file will be accessible from outside.
// and some other minor fixes. (cvsclient native not yet ready)
//
// Revision 1.61  2004/10/14 17:19:17  rurban
// allow most_popular sortby arguments
//
// Revision 1.60  2004/07/09 10:06:50  rurban
// Use backend specific sortby and sortable_columns method, to be able to
// select between native (Db backend) and custom (PageList) sorting.
// Fixed PageList::AddPageList (missed the first)
// Added the author/creator.. name to AllPagesBy...
//   display no pages if none matched.
// Improved dba and file sortby().
// Use &$request reference
//
// Revision 1.59  2004/07/08 21:32:36  rurban
// Prevent from more warnings, minor db and sort optimizations
//
// Revision 1.58  2004/07/08 16:56:16  rurban
// use the backendType abstraction
//
// Revision 1.57  2004/07/05 12:57:54  rurban
// add mysql timeout
//
// Revision 1.56  2004/07/04 10:24:43  rurban
// forgot the expressions
//
// Revision 1.55  2004/07/03 16:51:06  rurban
// optional DBADMIN_USER:DBADMIN_PASSWD for action=upgrade (if no ALTER permission)
// added atomic mysql REPLACE for PearDB as in ADODB
// fixed _lock_tables typo links => link
// fixes unserialize ADODB bug in line 180
//
// Revision 1.54  2004/06/29 08:52:24  rurban
// Use ...version() $need_content argument in WikiDB also:
// To reduce the memory footprint for larger sets of pagelists,
// we don't cache the content (only true or false) and
// we purge the pagedata (_cached_html) also.
// _cached_html is only cached for the current pagename.
// => Vastly improved page existance check, ACL check, ...
//
// Now only PagedList info=content or size needs the whole content, esp. if sortable.
//
// Revision 1.53  2004/06/27 10:26:03  rurban
// oci8 patch by Philippe Vanhaesendonck + some ADODB notes+fixes
//
// Revision 1.52  2004/06/25 14:15:08  rurban
// reduce memory footprint by caching only requested pagedate content (improving most page iterators)
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
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
