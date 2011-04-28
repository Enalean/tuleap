<?php rcs_id('$Id: dbaBase.php,v 1.23 2005/09/11 13:21:45 rurban Exp $');

require_once('lib/WikiDB/backend.php');

// FIXME:padding of data?  Is it needed?  dba_optimize() seems to do a good
// job at packing 'gdbm' (and 'db2') databases.

/*
 * Tables:
 *
 *  page:
 *   Index: pagename
 *  Values: latestversion . ':' . flags . ':' serialized hash of page meta data
 *           Currently flags = 1 if latest version has empty content.
 *
 *  version
 *   Index: version:pagename
 *   Value: serialized hash of revision meta data, including:
 *          + quasi-meta-data %content
 *
 *  links
 *   index: 'o' . pagename
 *   value: serialized list of pages (names) which pagename links to.
 *   index: 'i' . pagename
 *   value: serialized list of pages which link to pagename
 *
 *  TODO:
 *  Don't keep tables locked the whole time
 *
 *  index table with:
 *   list of pagenames for get_all_pages
 *   mostpopular list?
 *   RecentChanges support: 
 *     lists of most recent edits (major, minor, either).
 *   
 *
 *  Separate hit table, so we don't have to update the whole page entry
 *  each time we get a hit.  (Maybe not so important though...).
 */     

require_once('lib/DbaPartition.php');

class WikiDB_backend_dbaBase
extends WikiDB_backend
{
    function WikiDB_backend_dbaBase (&$dba) {
        $this->_db = &$dba;
        // TODO: page and version tables should be in their own files, probably.
        // We'll pack them all in one for now (testing).
        // 2004-07-09 10:07:30 rurban: It's fast enough this way.
        $this->_pagedb = new DbaPartition($dba, 'p');
        $this->_versiondb = new DbaPartition($dba, 'v');
        $linkdbpart = new DbaPartition($dba, 'l');
        $this->_linkdb = new WikiDB_backend_dbaBase_linktable($linkdbpart);
        $this->_dbdb = new DbaPartition($dba, 'd');
    }

    function sortable_columns() {
        return array('pagename','mtime'/*,'author_id','author'*/);
    }
    
    function close() {
        $this->_db->close();
    }

    function optimize() {
        $this->_db->optimize();
    }

    function sync() {
        $this->_db->sync();
    }

    function rebuild() {
        $this->_linkdb->rebuild();
        $this->optimize();
    }
    
    function check() {
        return $this->_linkdb->check();
    }

    function get_pagedata($pagename) {
        $result = $this->_pagedb->get($pagename);
        if (!$result)
            return false;
        list(,,$packed) = explode(':', $result, 3);
        $data = unserialize($packed);
        return $data;
    }
            
    function update_pagedata($pagename, $newdata) {
        $result = $this->_pagedb->get($pagename);
        if ($result) {
            list($latestversion,$flags,$data) = explode(':', $result, 3);
            $data = unserialize($data);
        }
        else {
            $latestversion = $flags = 0;
            $data = array();
        }
        
        foreach ($newdata as $key => $val) {
            if (empty($val))
                unset($data[$key]);
            else
                $data[$key] = $val;
        }
        $this->_pagedb->set($pagename,
                            (int)$latestversion . ':'
                            . (int)$flags . ':'
                            . serialize($data));
    }

    function get_latest_version($pagename) {
        return (int) $this->_pagedb->get($pagename);
    }

    function get_previous_version($pagename, $version) {
        $versdb = &$this->_versiondb;

        while (--$version > 0) {
            if ($versdb->exists($version . ":$pagename"))
                return $version;
        }
        return false;
    }

    //check $want_content
    function get_versiondata($pagename, $version, $want_content=false) {
        $data = $this->_versiondb->get((int)$version . ":$pagename");
        if (empty($data)) return false;
        else {
            $data = unserialize($data);
            if (!$want_content)
                $data['%content'] = !empty($data['%content']);
            return $data;
        }
    }
        
    /**
     * See ADODB for a better delete_page(), which can be undone and is seen in RecentChanges.
     * See backend.php
     */
    //function delete_page($pagename) { $this->purge_page($pagename);  }

    /**
     * Completely delete page from the database.
     */
    function purge_page($pagename) {
        $pagedb = &$this->_pagedb;
        $versdb = &$this->_versiondb;

        $version = $this->get_latest_version($pagename);
        while ($version > 0) {
            $versdb->set($version-- . ":$pagename", false);
        }
        $pagedb->set($pagename, false);

        $this->set_links($pagename, false);
    }

    function rename_page($pagename, $to) {
        $result = $this->_pagedb->get($pagename);
        if ($result) {
            list($version,$flags,$data) = explode(':', $result, 3);
            $data = unserialize($data);
        }
        else
            return false;

        $this->_pagedb->delete($pagename);
        $data['pagename'] = $to;
        $this->_pagedb->set($to,
                            (int)$version . ':'
                            . (int)$flags . ':'
                            . serialize($data));
        // move over the latest version only
        $pvdata = $this->get_versiondata($pagename, $version, true);
        $this->set_versiondata($to, $version, $pvdata);
	return true;
    }
            
    /**
     * Delete an old revision of a page.
     */
    function delete_versiondata($pagename, $version) {
        $versdb = &$this->_versiondb;

        $latest = $this->get_latest_version($pagename);

        assert($version > 0);
        assert($version <= $latest);
        
        $versdb->set((int)$version . ":$pagename", false);

        if ($version == $latest) {
            $previous = $this->get_previous_version($version);
            if ($previous > 0) {
                $pvdata = $this->get_versiondata($pagename, $previous);
                $is_empty = empty($pvdata['%content']);
            }
            else
                $is_empty = true;
            $this->_update_latest_version($pagename, $previous, $is_empty);
        }
    }

    /**
     * Create a new revision of a page.
     */
    function set_versiondata($pagename, $version, $data) {
        $versdb = &$this->_versiondb;

        $versdb->set((int)$version . ":$pagename", serialize($data));
        if ($version > $this->get_latest_version($pagename))
            $this->_update_latest_version($pagename, $version, empty($data['%content']));
    }

    function _update_latest_version($pagename, $latest, $flags) {
        $pagedb = &$this->_pagedb;

        $pdata = $pagedb->get($pagename);
        if ($pdata)
            list(,,$pagedata) = explode(':',$pdata,3);
        else
            $pagedata = serialize(array());
        
        $pagedb->set($pagename, (int)$latest . ':' . (int)$flags . ":$pagedata");
    }

    function numPages($include_empty=false, $exclude=false) {
        $pagedb = &$this->_pagedb;
        $count = 0;
        for ($page = $pagedb->firstkey(); $page!== false; $page = $pagedb->nextkey()) {
            if (!$page) {
                assert(!empty($page));
                continue;
            }
            if ($exclude and in_array($page, $exclude)) continue; 
            if (!$include_empty) {
            	if (!($data = $pagedb->get($page))) continue;
                list($latestversion,$flags,) = explode(':', $data, 3);
                unset($data);
                if ($latestversion == 0 || $flags != 0)
                    continue;   // current content is empty 
            }
            $count++;
        }
        return $count;
    }

    function get_all_pages($include_empty=false, $sortby=false, $limit=false, $exclude=false) {
        $pagedb = &$this->_pagedb;
        $pages = array();
        for ($page = $pagedb->firstkey(); $page!== false; $page = $pagedb->nextkey()) {
            if (!$page) {
                assert(!empty($page));
                continue;
            }
            if ($exclude and in_array($page, $exclude)) continue; 
            if ($limit and count($pages) > $limit) break;
            if (!$include_empty) {
            	if (!($data = $pagedb->get($page))) continue;
                list($latestversion,$flags,) = explode(':', $data, 3);
                unset($data);
                if ($latestversion == 0 || $flags != 0)
                    continue;   // current content is empty 
            }
            $pages[] = $page;
        }
        return new WikiDB_backend_dbaBase_pageiter($this, $pages, 
                                                   array('sortby'=>$sortby,
                                                         'limit' =>$limit));
    }

    function set_links($pagename, $links) {
        $this->_linkdb->set_links($pagename, $links);
    }

    function get_links($pagename, $reversed=true, $include_empty=false,
                       $sortby=false, $limit=false, $exclude=false) {
        $links = $this->_linkdb->get_links($pagename, $reversed);
        return new WikiDB_backend_dbaBase_pageiter($this, $links, 
                                                   array('sortby'=>$sortby,
                                                         'limit' =>$limit,
                                                         'exclude'=>$exclude,
                                                         ));
    }
};

function WikiDB_backend_dbaBase_sortby_pagename_ASC ($a, $b) {
    return strcasecmp($a, $b);
}
function WikiDB_backend_dbaBase_sortby_pagename_DESC ($a, $b) {
    return strcasecmp($b, $a);
}
function WikiDB_backend_dbaBase_sortby_mtime_ASC ($a, $b) {
    return WikiDB_backend_dbaBase_sortby_num($a, $b, 'mtime');
}
function WikiDB_backend_dbaBase_sortby_mtime_DESC ($a, $b) {
    return WikiDB_backend_dbaBase_sortby_num($b, $a, 'mtime');
}
/*
function WikiDB_backend_dbaBase_sortby_hits_ASC ($a, $b) {
    return WikiDB_backend_dbaBase_sortby_num($a, $b, 'hits');
}
function WikiDB_backend_dbaBase_sortby_hits_DESC ($a, $b) {
    return WikiDB_backend_dbaBase_sortby_num($b, $a, 'hits');
}
*/
function WikiDB_backend_dbaBase_sortby_num($aname, $bname, $field) {
    global $request;
    $dbi = $request->getDbh();
    // fields are stored in versiondata
    $av = $dbi->_backend->get_latest_version($aname);
    $bv = $dbi->_backend->get_latest_version($bname);
    $a = $dbi->_backend->get_versiondata($aname, $av, false);
    if (!$a) return 0;
    $b = $dbi->_backend->get_versiondata($bname, $bv, false);
    if (!$b) return 0;
    if ((!isset($a[$field]) && !isset($b[$field])) || ($a[$field] === $b[$field])) {
        return 0; 
    } else {
        return (!isset($a[$field]) || ($a[$field] < $b[$field])) ? -1 : 1;
    }
}

class WikiDB_backend_dbaBase_pageiter
extends WikiDB_backend_iterator
{
    function WikiDB_backend_dbaBase_pageiter(&$backend, &$pages, $options=false) {
        $this->_backend = $backend;
        $this->_options = $options;
        if ($pages) { 
            if (!empty($options['sortby'])) {
                $sortby = WikiDB_backend::sortby($options['sortby'], 'db', array('pagename','mtime'));
                if ($sortby and !strstr($sortby, "hits ")) { // check for which column to sortby
                    usort($pages, 'WikiDB_backend_dbaBase_sortby_'.str_replace(' ','_',$sortby));
                }
            }
            if (!empty($options['limit'])) {
                list($offset,$limit) = WikiDB_backend::limit($options['limit']);
                $pages = array_slice($pages, $offset, $limit);
            }
            $this->_pages = $pages;
        } else 
            $this->_pages = array();
    }

    function next() {
        if ( ! ($pagename = array_shift($this->_pages)) )
            return false;
        if (!empty($options['exclude']) and in_array($pagename, $options['exclude']))
            return $this->next();
        return array('pagename' => $pagename);
    }
            
    function count() {
        return count($this->_pages);
    }

    function asArray() {
        reset($this->_pages);
        return $this->_pages;
    }

    function free() {
        $this->_pages = array();
    }
};

class WikiDB_backend_dbaBase_linktable 
{
    function WikiDB_backend_dbaBase_linktable(&$dba) {
        $this->_db = &$dba;
    }

    //FIXME: try storing link lists as hashes rather than arrays.
    // (backlink deletion would be faster.)
    function get_links($page, $reversed=true) {
        return $this->_get_links($reversed ? 'i' : 'o', $page);
    }
    
    function set_links($page, $newlinks) {

        $oldlinks = $this->_get_links('o', $page);

        if (!is_array($newlinks)) {
            assert(empty($newlinks));
            $newlinks = array();
        }
        else {
            $newlinks = array_unique($newlinks);
        }
        sort($newlinks);
        $this->_set_links('o', $page, $newlinks);

        reset($newlinks);
        reset($oldlinks);
        $new = current($newlinks);
        $old = current($oldlinks);
        while ($new !== false || $old !== false) {
            if ($old === false || ($new !== false && $new < $old)) {
                // $new is a new link (not in $oldlinks).
                $this->_add_backlink($new, $page);
                $new = next($newlinks);
            }
            elseif ($new === false || $old < $new) {
                // $old is a obsolete link (not in $newlinks).
                $this->_delete_backlink($old, $page);
                $old = next($oldlinks);
            }
            else {
                // Unchanged link (in both $newlist and $oldlinks).
                assert($new == $old);
                $new = next($newlinks);
                $old = next($oldlinks);
            }
        }
    }

    /**
     * Rebuild the back-link index.
     *
     * This should never be needed, but if the database gets hosed for some reason,
     * this should put it back into a consistent state.
     *
     * We assume the forward links in the our table are correct, and recalculate
     * all the backlinks appropriately.
     */
    function rebuild () {
        $db = &$this->_db;

        // Delete the backlink tables, make a list of page names.
        $okeys = array();
        $ikeys = array();
        for ($key = $db->firstkey(); $key; $key = $db->nextkey()) {
            if ($key[0] == 'i')
                $ikeys[] = $key;
            elseif ($key[0] == 'o')
                $okeys[] = $key;
            else {
                trigger_error("Bad key in linktable: '$key'", E_USER_WARNING);
                $ikeys[] = $key;
            }
        }
        foreach ($ikeys as $key) {
            $db->delete($key);
        }
        foreach ($okeys as $key) {
            $page = substr($key,1);
            $links = $this->_get_links('o', $page);
            $db->delete($key);
            $this->set_links($page, $links);
        }
    }

    function check() {
        $db = &$this->_db;

        // FIXME: check for sortedness and uniqueness in links lists.

        for ($key = $db->firstkey(); $key; $key = $db->nextkey()) {
            if (strlen($key) < 1 || ($key[0] != 'i' && $key[0] != 'o')) {
                $errs[] = "Bad key '$key' in table";
                continue;
            }
            $page = substr($key, 1);
            if ($key[0] == 'o') {
                // Forward links.
                foreach($this->_get_links('o', $page) as $link) {
                    if (!$this->_has_link('i', $link, $page))
                        $errs[] = "backlink entry missing for link '$page'->'$link'";
                }
            }
            else {
                assert($key[0] == 'i');
                // Backlinks.
                foreach($this->_get_links('i', $page) as $link) {
                    if (!$this->_has_link('o', $link, $page))
                        $errs[] = "link entry missing for backlink '$page'<-'$link'";
                }
            }
        }

        return isset($errs) ? $errs : false;
    }
    
        
    function _add_backlink($page, $linkedfrom) {
        $backlinks = $this->_get_links('i', $page);
        $backlinks[] = $linkedfrom;
        sort($backlinks);
        $this->_set_links('i', $page, $backlinks);
    }
    
    function _delete_backlink($page, $linkedfrom) {
        $backlinks = $this->_get_links('i', $page);
        foreach ($backlinks as $key => $backlink) {
            if ($backlink == $linkedfrom)
                unset($backlinks[$key]);
        }
        $this->_set_links('i', $page, $backlinks);
    }
    
    function _has_link($which, $page, $link) {
        $links = $this->_get_links($which, $page);
        foreach($links as $l) {
            if ($l == $link)
                return true;
        }
        return false;
    }
    
    function _get_links($which, $page) {
        $data = $this->_db->get($which . $page);
        return $data ? unserialize($data) : array();
    }

    function _set_links($which, $page, &$links) {
        $key = $which . $page;
        if ($links)
            $this->_db->set($key, serialize($links));
        else
            $this->_db->set($key, false);
    }
}

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>