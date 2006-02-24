<?php //-*-php-*-
rcs_id('$Id$');

//require_once('lib/stdlib.php');
require_once('lib/PageType.php');

//FIXME: arg on get*Revision to hint that content is wanted.

/**
 * The classes in the file define the interface to the
 * page database.
 *
 * @package WikiDB
 * @author Geoffrey T. Dairiki <dairiki@dairiki.org>
 */

/**
 * Force the creation of a new revision.
 * @see WikiDB_Page::createRevision()
 */
define('WIKIDB_FORCE_CREATE', -1);

// FIXME:  used for debugging only.  Comment out if cache does not work
define('USECACHE', 1);

/** 
 * Abstract base class for the database used by PhpWiki.
 *
 * A <tt>WikiDB</tt> is a container for <tt>WikiDB_Page</tt>s which in
 * turn contain <tt>WikiDB_PageRevision</tt>s.
 *
 * Conceptually a <tt>WikiDB</tt> contains all possible
 * <tt>WikiDB_Page</tt>s, whether they have been initialized or not.
 * Since all possible pages are already contained in a WikiDB, a call
 * to WikiDB::getPage() will never fail (barring bugs and
 * e.g. filesystem or SQL database problems.)
 *
 * Also each <tt>WikiDB_Page</tt> always contains at least one
 * <tt>WikiDB_PageRevision</tt>: the default content (e.g. "Describe
 * [PageName] here.").  This default content has a version number of
 * zero.
 *
 * <tt>WikiDB_PageRevision</tt>s have read-only semantics. One can
 * only create new revisions or delete old ones --- one can not modify
 * an existing revision.
 */
class WikiDB {
    /**
     * Open a WikiDB database.
     *
     * This is a static member function. This function inspects its
     * arguments to determine the proper subclass of WikiDB to
     * instantiate, and then it instantiates it.
     *
     * @access public
     *
     * @param hash $dbparams Database configuration parameters.
     * Some pertinent paramters are:
     * <dl>
     * <dt> dbtype
     * <dd> The back-end type.  Current supported types are:
     *   <dl>
     *   <dt> SQL
     *   <dd> Generic SQL backend based on the PEAR/DB database abstraction
     *       library.
     *   <dt> dba
     *   <dd> Dba based backend.
     *   </dl>
     *
     * <dt> dsn
     * <dd> (Used by the SQL backend.)
     *      The DSN specifying which database to connect to.
     *
     * <dt> prefix
     * <dd> Prefix to be prepended to database table (and file names).
     *
     * <dt> directory
     * <dd> (Used by the dba backend.)
     *      Which directory db files reside in.
     *
     * <dt> timeout
     * <dd> (Used by the dba backend.)
     *      Timeout in seconds for opening (and obtaining lock) on the
     *      db files.
     *
     * <dt> dba_handler
     * <dd> (Used by the dba backend.)
     *
     *      Which dba handler to use. Good choices are probably either
     *      'gdbm' or 'db2'.
     * </dl>
     *
     * @return WikiDB A WikiDB object.
     **/
    function open ($dbparams) {
        $dbtype = $dbparams{'dbtype'};
        include_once("lib/WikiDB/$dbtype.php");
				
        $class = 'WikiDB_' . $dbtype;
        return new $class ($dbparams);
    }


    /**
     * Constructor.
     *
     * @access private
     * @see open()
     */
    function WikiDB (&$backend, $dbparams) {
        $this->_backend = &$backend;
        $this->_cache = new WikiDB_cache($backend);

        // If the database doesn't yet have a timestamp, initialize it now.
        if ($this->get('_timestamp') === false)
            $this->touch();
        
        //FIXME: devel checking.
        //$this->_backend->check();
    }
    
    /**
     * Get any user-level warnings about this WikiDB.
     *
     * Some back-ends, e.g. by default create there data files in the
     * global /tmp directory. We would like to warn the user when this
     * happens (since /tmp files tend to get wiped periodically.)
     * Warnings such as these may be communicated from specific
     * back-ends through this method.
     *
     * @access public
     *
     * @return string A warning message (or <tt>false</tt> if there is
     * none.)
     */
    function genericWarnings() {
        return false;
    }
     
    /**
     * Close database connection.
     *
     * The database may no longer be used after it is closed.
     *
     * Closing a WikiDB invalidates all <tt>WikiDB_Page</tt>s,
     * <tt>WikiDB_PageRevision</tt>s and <tt>WikiDB_PageIterator</tt>s
     * which have been obtained from it.
     *
     * @access public
     */
    function close () {
        $this->_backend->close();
        $this->_cache->close();
    }
    
    /**
     * Get a WikiDB_Page from a WikiDB.
     *
     * A {@link WikiDB} consists of the (infinite) set of all possible pages,
     * therefore this method never fails.
     *
     * @access public
     * @param string $pagename Which page to get.
     * @return WikiDB_Page The requested WikiDB_Page.
     */
    function getPage($pagename) {
        static $error_displayed = false;
        if (DEBUG) {
            if (!(is_string($pagename) and $pagename != '')) {
                if ($error_displayed) return false;
                $error_displayed = true;
                if (function_exists("xdebug_get_function_stack"))
                    var_dump(xdebug_get_function_stack());
                trigger_error("empty pagename",E_USER_WARNING);
                return false;
            }
        } else 
            assert(is_string($pagename) and $pagename != '');
        return new WikiDB_Page($this, $pagename);
    }

    /**
     * Determine whether page exists (in non-default form).
     *
     * <pre>
     *   $is_page = $dbi->isWikiPage($pagename);
     * </pre>
     * is equivalent to
     * <pre>
     *   $page = $dbi->getPage($pagename);
     *   $current = $page->getCurrentRevision();
     *   $is_page = ! $current->hasDefaultContents();
     * </pre>
     * however isWikiPage may be implemented in a more efficient
     * manner in certain back-ends.
     *
     * @access public
     *
     * @param string $pagename string Which page to check.
     *
     * @return boolean True if the page actually exists with
     * non-default contents in the WikiDataBase.
     */
    function isWikiPage ($pagename) {
        $page = $this->getPage($pagename);
        $current = $page->getCurrentRevision();
        return ! $current->hasDefaultContents();
    }

    /**
     * Delete page from the WikiDB. 
     *
     * Deletes all revisions of the page from the WikiDB. Also resets
     * all page meta-data to the default values.
     *
     * @access public
     *
     * @param string $pagename Name of page to delete.
     */
    function deletePage($pagename) {
        $this->_cache->delete_page($pagename);

        //How to create a RecentChanges entry with explaining summary?
        /*
        $page = $this->getPage($pagename);
        $current = $page->getCurrentRevision();
        $meta = $current->_data;
        $version = $current->getVersion();
        $meta['summary'] = _("removed");
        $page->save($current->getPackedContent(), $version + 1, $meta);
        */
    }

    /**
     * Retrieve all pages.
     *
     * Gets the set of all pages with non-default contents.
     *
     * FIXME: do we need this?  I think so.  The simple searches
     *        need this stuff.
     *
     * @access public
     *
     * @param boolean $include_defaulted Normally pages whose most
     * recent revision has empty content are considered to be
     * non-existant. Unless $include_defaulted is set to true, those
     * pages will not be returned.
     *
     * @return WikiDB_PageIterator A WikiDB_PageIterator which contains all pages
     *     in the WikiDB which have non-default contents.
     */
    function getAllPages($include_defaulted=false, $sortby=false, $limit=false) {
        $result = $this->_backend->get_all_pages($include_defaulted,$sortby,$limit);
        return new WikiDB_PageIterator($this, $result);
    }

    // Do we need this?
    //function nPages() { 
    //}
    // Yes, for paging. Renamed.
    function numPages($filter=false, $exclude='') {
    	if (method_exists($this->_backend,'numPages'))
            $count = $this->_backend->numPages($filter,$exclude);
        else {
            $iter = $this->getAllPages();
            $count = $iter->count();
        }
        return (int)$count;
    }
    
    /**
     * Title search.
     *
     * Search for pages containing (or not containing) certain words
     * in their names.
     *
     * Pages are returned in alphabetical order whenever it is
     * practical to do so.
     *
     * FIXME: should titleSearch and fullSearch be combined?  I think so.
     *
     * @access public
     * @param TextSearchQuery $search A TextSearchQuery object
     * @return WikiDB_PageIterator A WikiDB_PageIterator containing the matching pages.
     * @see TextSearchQuery
     */
    function titleSearch($search) {
        $result = $this->_backend->text_search($search);
        return new WikiDB_PageIterator($this, $result);
    }

    /**
     * Full text search.
     *
     * Search for pages containing (or not containing) certain words
     * in their entire text (this includes the page content and the
     * page name).
     *
     * Pages are returned in alphabetical order whenever it is
     * practical to do so.
     *
     * @access public
     *
     * @param TextSearchQuery $search A TextSearchQuery object.
     * @return WikiDB_PageIterator A WikiDB_PageIterator containing the matching pages.
     * @see TextSearchQuery
     */
    function fullSearch($search) {
        $result = $this->_backend->text_search($search, 'full_text');
        return new WikiDB_PageIterator($this, $result);
    }

    /**
     * Find the pages with the greatest hit counts.
     *
     * Pages are returned in reverse order by hit count.
     *
     * @access public
     *
     * @param integer $limit The maximum number of pages to return.
     * Set $limit to zero to return all pages.  If $limit < 0, pages will
     * be sorted in decreasing order of popularity.
     *
     * @return WikiDB_PageIterator A WikiDB_PageIterator containing the matching
     * pages.
     */
    function mostPopular($limit = 20, $sortby = '') {
        // we don't support sortby=mtime here
        if (strstr($sortby,'mtime'))
            $sortby = '';
        $result = $this->_backend->most_popular($limit, $sortby);
        return new WikiDB_PageIterator($this, $result);
    }

    /**
     * Find recent page revisions.
     *
     * Revisions are returned in reverse order by creation time.
     *
     * @access public
     *
     * @param hash $params This hash is used to specify various optional
     *   parameters:
     * <dl>
     * <dt> limit 
     *    <dd> (integer) At most this many revisions will be returned.
     * <dt> since
     *    <dd> (integer) Only revisions since this time (unix-timestamp) will be returned. 
     * <dt> include_minor_revisions
     *    <dd> (boolean) Also include minor revisions.  (Default is not to.)
     * <dt> exclude_major_revisions
     *    <dd> (boolean) Don't include non-minor revisions.
     *         (Exclude_major_revisions implies include_minor_revisions.)
     * <dt> include_all_revisions
     *    <dd> (boolean) Return all matching revisions for each page.
     *         Normally only the most recent matching revision is returned
     *         for each page.
     * </dl>
     *
     * @return WikiDB_PageRevisionIterator A WikiDB_PageRevisionIterator containing the
     * matching revisions.
     */
    function mostRecent($params = false) {
        $result = $this->_backend->most_recent($params);
        return new WikiDB_PageRevisionIterator($this, $result);
    }

    /**
     * Call the appropriate backend method.
     *
     * @access public
     * @param string $from Page to rename
     * @param string $to   New name
     * @param boolean $updateWikiLinks If the text in all pages should be replaced.
     * @return boolean     true or false
     */
    function renamePage($from, $to, $updateWikiLinks = false) {
        assert(is_string($from) && $from != '');
        assert(is_string($to) && $to != '');
        $result = false;
        if (method_exists($this->_backend,'rename_page')) {
            $oldpage = $this->getPage($from);
            $newpage = $this->getPage($to);
            if ($oldpage->exists() and ! $newpage->exists()) {
                if ($result = $this->_backend->rename_page($from, $to)) {
                    //update all WikiLinks in existing pages
                    if ($updateWikiLinks) {
                        //trigger_error(_("WikiDB::renamePage(..,..,updateWikiLinks) not yet implemented"),E_USER_WARNING);
                        require_once('lib/plugin/WikiAdminSearchReplace.php');
                        $links = $oldpage->getLinks();
                        while ($linked_page = $links->next()) {
                            WikiPlugin_WikiAdminSearchReplace::replaceHelper($this,$linked_page->getName(),$from,$to);
                        }
                        $links = $newpage->getLinks();
                        while ($linked_page = $links->next()) {
                            WikiPlugin_WikiAdminSearchReplace::replaceHelper($this,$linked_page->getName(),$from,$to);
                        }
                    }
                    //create a RecentChanges entry with explaining summary
                    $page = $this->getPage($to);
                    $current = $page->getCurrentRevision();
                    $meta = $current->_data;
                    $version = $current->getVersion();
                    $meta['summary'] = sprintf(_("renamed from %s"),$from);
                    $page->save($current->getPackedContent(), $version + 1, $meta);
                }
            }
        } else {
            trigger_error(_("WikiDB::renamePage() not yet implemented for this backend"),E_USER_WARNING);
        }
        return $result;
    }

    /** Get timestamp when database was last modified.
     *
     * @return string A string consisting of two integers,
     * separated by a space.  The first is the time in
     * unix timestamp format, the second is a modification
     * count for the database.
     *
     * The idea is that you can cast the return value to an
     * int to get a timestamp, or you can use the string value
     * as a good hash for the entire database.
     */
    function getTimestamp() {
        $ts = $this->get('_timestamp');
        return sprintf("%d %d", $ts[0], $ts[1]);
    }
    
    /**
     * Update the database timestamp.
     *
     */
    function touch() {
        $ts = $this->get('_timestamp');
        $this->set('_timestamp', array(time(), $ts[1] + 1));
    }

        
    /**
     * Access WikiDB global meta-data.
     *
     * NOTE: this is currently implemented in a hackish and
     * not very efficient manner.
     *
     * @access public
     *
     * @param string $key Which meta data to get.
     * Some reserved meta-data keys are:
     * <dl>
     * <dt>'_timestamp' <dd> Data used by getTimestamp().
     * </dl>
     *
     * @return scalar The requested value, or false if the requested data
     * is not set.
     */
    function get($key) {
        if (!$key || $key[0] == '%')
            return false;
        /*
         * Hack Alert: We can use any page (existing or not) to store
         * this data (as long as we always use the same one.)
         */
        $gd = $this->getPage('global_data');
        $data = $gd->get('__global');

        if ($data && isset($data[$key]))
            return $data[$key];
        else
            return false;
    }

    /**
     * Set global meta-data.
     *
     * NOTE: this is currently implemented in a hackish and
     * not very efficient manner.
     *
     * @see get
     * @access public
     *
     * @param string $key  Meta-data key to set.
     * @param string $newval  New value.
     */
    function set($key, $newval) {
        if (!$key || $key[0] == '%')
            return;
        
        $gd = $this->getPage('global_data');
        
        $data = $gd->get('__global');
        if ($data === false)
            $data = array();

        if (empty($newval))
            unset($data[$key]);
        else
            $data[$key] = $newval;

        $gd->set('__global', $data);
    }

    // simple select or create/update queries which do trigger_error
    function simpleQuery($sql) {
        global $DBParams;
        if ($DBParams['dbtype'] == 'SQL') {
            $result = $this->_backend->_dbh->query($sql);
            if (DB::isError($result)) {
                $msg = $result->getMessage();
                trigger_error("SQL Error: ".DB::errorMessage($result),E_USER_WARNING);
                return false;
            } else {
                return $result;
            }
        } elseif ($DBParams['dbtype'] == 'ADODB') {
            if (!($result = $this->_backend->_dbh->Execute($sql))) {
                trigger_error("SQL Error: ".$this->_backend->_dbh->ErrorMsg(),E_USER_WARNING);
                return false;
            } else {
                return $result;
            }
        }
    }

};


/**
 * An abstract base class which representing a wiki-page within a
 * WikiDB.
 *
 * A WikiDB_Page contains a number (at least one) of
 * WikiDB_PageRevisions.
 */
class WikiDB_Page 
{
    function WikiDB_Page(&$wikidb, $pagename) {
        $this->_wikidb = &$wikidb;
        $this->_pagename = $pagename;
        if (DEBUG) {
            if (!(is_string($pagename) and $pagename != '')) {
                if (function_exists("xdebug_get_function_stack")) {
                    echo "xdebug_get_function_stack(): "; var_dump(xdebug_get_function_stack());

                }
                trigger_error("empty pagename",E_USER_WARNING);
                return false;
            }
        } else assert(is_string($pagename) and $pagename != '');
    }

    /**
     * Get the name of the wiki page.
     *
     * @access public
     *
     * @return string The page name.
     */
    function getName() {
        return $this->_pagename;
    }

    function exists() {
        $current = $this->getCurrentRevision();
        return ! $current->hasDefaultContents();
    }

    /**
     * Delete an old revision of a WikiDB_Page.
     *
     * Deletes the specified revision of the page.
     * It is a fatal error to attempt to delete the current revision.
     *
     * @access public
     *
     * @param integer $version Which revision to delete.  (You can also
     *  use a WikiDB_PageRevision object here.)
     */
    function deleteRevision($version) {
        $backend = &$this->_wikidb->_backend;
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;

        $version = $this->_coerce_to_version($version);
        if ($version == 0)
            return;

        $backend->lock(array('page','version'));
        $latestversion = $cache->get_latest_version($pagename);
        if ($latestversion && $version == $latestversion) {
            $backend->unlock(array('page','version'));
            trigger_error(sprintf("Attempt to delete most recent revision of '%s'",
                                  $pagename), E_USER_ERROR);
            return;
        }

        $cache->delete_versiondata($pagename, $version);
        $backend->unlock(array('page','version'));
    }

    /*
     * Delete a revision, or possibly merge it with a previous
     * revision.
     *
     * The idea is this:
     * Suppose an author make a (major) edit to a page.  Shortly
     * after that the same author makes a minor edit (e.g. to fix
     * spelling mistakes he just made.)
     *
     * Now some time later, where cleaning out old saved revisions,
     * and would like to delete his minor revision (since there's
     * really no point in keeping minor revisions around for a long
     * time.)
     *
     * Note that the text after the minor revision probably represents
     * what the author intended to write better than the text after
     * the preceding major edit.
     *
     * So what we really want to do is merge the minor edit with the
     * preceding edit.
     *
     * We will only do this when:
     * <ul>
     * <li>The revision being deleted is a minor one, and
     * <li>It has the same author as the immediately preceding revision.
     * </ul>
     */
    function mergeRevision($version) {
        $backend = &$this->_wikidb->_backend;
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;

        $version = $this->_coerce_to_version($version);
        if ($version == 0)
            return;

        $backend->lock(array('version'));
        $latestversion = $backend->get_latest_version($pagename);
        if ($latestversion && $version == $latestversion) {
            $backend->unlock(array('version'));
            trigger_error(sprintf("Attempt to merge most recent revision of '%s'",
                                  $pagename), E_USER_ERROR);
            return;
        }

        $versiondata = $cache->get_versiondata($pagename, $version, true);
        if (!$versiondata) {
            // Not there? ... we're done!
            $backend->unlock(array('version'));
            return;
        }

        if ($versiondata['is_minor_edit']) {
            $previous = $backend->get_previous_version($pagename, $version);
            if ($previous) {
                $prevdata = $cache->get_versiondata($pagename, $previous);
                if ($prevdata['author_id'] == $versiondata['author_id']) {
                    // This is a minor revision, previous version is
                    // by the same author. We will merge the
                    // revisions.
                    $cache->update_versiondata($pagename, $previous,
                                               array('%content' => $versiondata['%content'],
                                                     '_supplanted' => $versiondata['_supplanted']));
                }
            }
        }

        $cache->delete_versiondata($pagename, $version);
        $backend->unlock(array('version'));
    }

    
    /**
     * Create a new revision of a {@link WikiDB_Page}.
     *
     * @access public
     *
     * @param int $version Version number for new revision.  
     * To ensure proper serialization of edits, $version must be
     * exactly one higher than the current latest version.
     * (You can defeat this check by setting $version to
     * {@link WIKIDB_FORCE_CREATE} --- not usually recommended.)
     *
     * @param string $content Contents of new revision.
     *
     * @param hash $metadata Metadata for new revision.
     * All values in the hash should be scalars (strings or integers).
     *
     * @param array $links List of pagenames which this page links to.
     *
     * @return WikiDB_PageRevision  Returns the new WikiDB_PageRevision object. If
     * $version was incorrect, returns false
     */
    function createRevision($version, &$content, $metadata, $links) {
        $backend = &$this->_wikidb->_backend;
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;
                
        $backend->lock(array('version','page','recent','links','nonempty'));

        $latestversion = $backend->get_latest_version($pagename);
        $newversion = $latestversion + 1;
        assert($newversion >= 1);

        if ($version != WIKIDB_FORCE_CREATE && $version != $newversion) {
            $backend->unlock(array('version','page','recent','links'));
            return false;
        }

        $data = $metadata;
        
        foreach ($data as $key => $val) {
            if (empty($val) || $key[0] == '_' || $key[0] == '%')
                unset($data[$key]);
        }
			
        assert(!empty($data['author']));
        if (empty($data['author_id']))
            @$data['author_id'] = $data['author'];
		
        if (empty($data['mtime']))
            $data['mtime'] = time();

        if ($latestversion) {
            // Ensure mtimes are monotonic.
            $pdata = $cache->get_versiondata($pagename, $latestversion);
            if ($data['mtime'] < $pdata['mtime']) {
                trigger_error(sprintf(_("%s: Date of new revision is %s"),
                                      $pagename,"'non-monotonic'"),
                              E_USER_NOTICE);
                $data['orig_mtime'] = $data['mtime'];
                $data['mtime'] = $pdata['mtime'];
            }
            
	    // FIXME: use (possibly user specified) 'mtime' time or
	    // time()?
            $cache->update_versiondata($pagename, $latestversion,
                                       array('_supplanted' => $data['mtime']));
        }

        $data['%content'] = &$content;

        $cache->set_versiondata($pagename, $newversion, $data);

        //$cache->update_pagedata($pagename, array(':latestversion' => $newversion,
        //':deleted' => empty($content)));
        
        $backend->set_links($pagename, $links);

        $backend->unlock(array('version','page','recent','links','nonempty'));

        return new WikiDB_PageRevision($this->_wikidb, $pagename, $newversion,
                                       $data);
    }

    /** A higher-level interface to createRevision.
     *
     * This takes care of computing the links, and storing
     * a cached version of the transformed wiki-text.
     *
     * @param string $wikitext  The page content.
     *
     * @param int $version Version number for new revision.  
     * To ensure proper serialization of edits, $version must be
     * exactly one higher than the current latest version.
     * (You can defeat this check by setting $version to
     * {@link WIKIDB_FORCE_CREATE} --- not usually recommended.)
     *
     * @param hash $meta  Meta-data for new revision.
     */
    function save($wikitext, $version, $meta) {
	$formatted = new TransformedText($this, $wikitext, $meta);
        $type = $formatted->getType();
	$meta['pagetype'] = $type->getName();
	$links = $formatted->getWikiPageLinks();

	$backend = &$this->_wikidb->_backend;
	$newrevision = $this->createRevision($version, $wikitext, $meta, $links);
	if ($newrevision)
            if (!defined('WIKIDB_NOCACHE_MARKUP') or !WIKIDB_NOCACHE_MARKUP)
                $this->set('_cached_html', $formatted->pack());

	// FIXME: probably should have some global state information
	// in the backend to control when to optimize.
        //
        // We're doing this here rather than in createRevision because
        // postgres can't optimize while locked.
        if (time() % 50 == 0) {
            if ($backend->optimize())
                trigger_error(sprintf(_("Optimizing %s"),'backend'), E_USER_NOTICE);
        }

        /* Generate notification emails? */
        if (isa($newrevision, 'wikidb_pagerevision')) {
            // Save didn't fail because of concurrent updates.
            $notify = $this->_wikidb->get('notify');
            if (!empty($notify) and is_array($notify)) {
                list($emails,$userids) = $this->getPageChangeEmails($notify);
                if (!empty($emails))
                    $this->sendPageChangeNotification($wikitext, $version, $meta, $emails, $userids);
            }
        }

        $newrevision->_transformedContent = $formatted;
	return $newrevision;
    }

    function getPageChangeEmails($notify) {
        $emails = array(); $userids = array();
        foreach ($notify as $page => $users) {
            if (glob_match($page,$this->_pagename)) {
                foreach ($users as $userid => $user) {
                    if (!empty($user['verified']) and !empty($user['email'])) {
                        $emails[]  = user_getemail_from_unix($userid);
                        $userids[] = $userid;
                    } elseif (!empty($user['email'])) {
                        global $request;
                        // do a dynamic emailVerified check update
                        $u = $request->getUser();
                        if ($u->UserName() == $userid) {
                            if ($request->_prefs->get('emailVerified')) {
                                $emails[] = user_getemail_from_unix($userid);
                                $userids[] = $userid;
                                $notify[$page][$userid]['verified'] = 1;
                                $request->_dbi->set('notify',$notify);
                            }
                        } else {
                            $u = WikiUser($userid);
                            if ($u->_prefs->get('emailVerified')) {
                                $emails[] = user_getemail_from_unix($userid);
                                $userids[] = $userid;
                                $notify[$page][$userid]['verified'] = 1;
                                $request->_dbi->set('notify',$notify);
                            }
                        }
                        // ignore verification
                        /*
                        if (DEBUG) {
                            if (!in_array($user['email'],$emails))
                                $emails[] = $user['email'];
                        }
                        */
                    }
                }
            }
        }
        $emails = array_unique($emails);
        $userids = array_unique($userids);
        return array($emails,$userids);
    }

    function sendPageChangeNotification(&$wikitext, $version, $meta, $emails, $userids) {
        $backend = &$this->_wikidb->_backend;
        $subject = _("Page change").' '.$this->_pagename;
        $previous = $backend->get_previous_version($this->_pagename, $version);
        if (!isset($meta['mtime'])) $meta['mtime'] = time();
        if ($previous) {
            $difflink = WikiURL($this->_pagename,array('action'=>'diff'),true);
            $cache = &$this->_wikidb->_cache;
            $this_content = explode("\n", $wikitext);
            $prevdata = $cache->get_versiondata($this->_pagename, $previous, true);
            if (empty($prevdata['%content']))
                $prevdata = $backend->get_versiondata($this->_pagename, $previous, true);
            $other_content = explode("\n", $prevdata['%content']);
            
            include_once("lib/diff.php");
            $diff2 = new Diff($other_content, $this_content);
            $context_lines = max(4, count($other_content) + 1,
                                 count($this_content) + 1);
            $fmt = new UnifiedDiffFormatter($context_lines);
            $content  = $this->_pagename . " " . $previous . " " . Iso8601DateTime($prevdata['mtime']) . "\n";
            $content .= $this->_pagename . " " . $version . " " .  Iso8601DateTime($meta['mtime']) . "\n";
            $content .= $fmt->format($diff2);
            
        } else {
            $difflink = WikiURL($this->_pagename,array(),true);
            $content = $this->_pagename . " " . $version . " " .  Iso8601DateTime($meta['mtime']) . "\n";
            $content .= _("New Page");
        }
        $editedby = sprintf(_("Edited by: %s"), $meta['author']);
        $emails = join(',',$emails);
        if (mail($emails,"[".WIKI_NAME."] ".$subject, 
                 $subject."\n".
                 $editedby."\n".
                 $difflink))
            trigger_error(sprintf(_("PageChange Notification of %s sent to %s"),
                                  $this->_pagename, join(',',$userids)), E_USER_NOTICE);
        else
            trigger_error(sprintf(_("PageChange Notification Error: Couldn't send %s to %s"),
                                  $this->_pagename, join(',',$userids)), E_USER_WARNING);
    }

    /**
     * Get the most recent revision of a page.
     *
     * @access public
     *
     * @return WikiDB_PageRevision The current WikiDB_PageRevision object. 
     */
    function getCurrentRevision() {
        $backend = &$this->_wikidb->_backend;
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;
        
        // Prevent deadlock in case of memory exhausted errors
        // Pure selection doesn't really need locking here.
        //   sf.net bug#927395
        // I know it would be better, but with lots of pages this deadlock is more 
        // severe than occasionally get not the latest revision.
        //$backend->lock();
        $version = $cache->get_latest_version($pagename);
        $revision = $this->getRevision($version);
        //$backend->unlock();
        assert($revision);
        return $revision;
    }

    /**
     * Get a specific revision of a WikiDB_Page.
     *
     * @access public
     *
     * @param integer $version  Which revision to get.
     *
     * @return WikiDB_PageRevision The requested WikiDB_PageRevision object, or
     * false if the requested revision does not exist in the {@link WikiDB}.
     * Note that version zero of any page always exists.
     */
    function getRevision($version) {
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;
        
        if ($version == 0)
            return new WikiDB_PageRevision($this->_wikidb, $pagename, 0);

        assert($version > 0);
        $vdata = $cache->get_versiondata($pagename, $version);
        if (!$vdata)
            return false;
        return new WikiDB_PageRevision($this->_wikidb, $pagename, $version,
                                       $vdata);
    }

    /**
     * Get previous page revision.
     *
     * This method find the most recent revision before a specified
     * version.
     *
     * @access public
     *
     * @param integer $version  Find most recent revision before this version.
     *  You can also use a WikiDB_PageRevision object to specify the $version.
     *
     * @return WikiDB_PageRevision The requested WikiDB_PageRevision object, or false if the
     * requested revision does not exist in the {@link WikiDB}.  Note that
     * unless $version is greater than zero, a revision (perhaps version zero,
     * the default revision) will always be found.
     */
    function getRevisionBefore($version) {
        $backend = &$this->_wikidb->_backend;
        $pagename = &$this->_pagename;

        $version = $this->_coerce_to_version($version);

        if ($version == 0)
            return false;
        //$backend->lock();
        $previous = $backend->get_previous_version($pagename, $version);
        $revision = $this->getRevision($previous);
        //$backend->unlock();
        assert($revision);
        return $revision;
    }

    /**
     * Get all revisions of the WikiDB_Page.
     *
     * This does not include the version zero (default) revision in the
     * returned revision set.
     *
     * @return WikiDB_PageRevisionIterator A
     * WikiDB_PageRevisionIterator containing all revisions of this
     * WikiDB_Page in reverse order by version number.
     */
    function getAllRevisions() {
        $backend = &$this->_wikidb->_backend;
        $revs = $backend->get_all_revisions($this->_pagename);
        return new WikiDB_PageRevisionIterator($this->_wikidb, $revs);
    }
    
    /**
     * Find pages which link to or are linked from a page.
     *
     * @access public
     *
     * @param boolean $reversed Which links to find: true for backlinks (default).
     *
     * @return WikiDB_PageIterator A WikiDB_PageIterator containing
     * all matching pages.
     */
    function getLinks($reversed = true) {
        $backend = &$this->_wikidb->_backend;
        $result =  $backend->get_links($this->_pagename, $reversed);
        return new WikiDB_PageIterator($this->_wikidb, $result);
    }
            
    /**
     * Access WikiDB_Page meta-data.
     *
     * @access public
     *
     * @param string $key Which meta data to get.
     * Some reserved meta-data keys are:
     * <dl>
     * <dt>'locked'<dd> Is page locked?
     * <dt>'hits'  <dd> Page hit counter.
     * <dt>'pref'  <dd> Users preferences, stored in homepages.
     * <dt>'owner' <dd> Default: first author_id. We might add a group with a dot here:
     *                  E.g. "owner.users"
     * <dt>'perm'  <dd> Permission flag to authorize read/write/execution of 
     *                  page-headers and content.
     * <dt>'score' <dd> Page score (not yet implement, do we need?)
     * </dl>
     *
     * @return scalar The requested value, or false if the requested data
     * is not set.
     */
    function get($key) {
        $cache = &$this->_wikidb->_cache;
        if (!$key || $key[0] == '%')
            return false;
        $data = $cache->get_pagedata($this->_pagename);
        return isset($data[$key]) ? $data[$key] : false;
    }

    /**
     * Get all the page meta-data as a hash.
     *
     * @return hash The page meta-data.
     */
    function getMetaData() {
        $cache = &$this->_wikidb->_cache;
        $data = $cache->get_pagedata($this->_pagename);
        $meta = array();
        foreach ($data as $key => $val) {
            if (/*!empty($val) &&*/ $key[0] != '%')
                $meta[$key] = $val;
        }
        return $meta;
    }

    /**
     * Set page meta-data.
     *
     * @see get
     * @access public
     *
     * @param string $key  Meta-data key to set.
     * @param string $newval  New value.
     */
    function set($key, $newval) {
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;
        
        assert($key && $key[0] != '%');

        $data = $cache->get_pagedata($pagename);

        if (!empty($newval)) {
            if (!empty($data[$key]) && $data[$key] == $newval)
                return;         // values identical, skip update.
        }
        else {
            if (empty($data[$key]))
                return;         // values identical, skip update.
        }

        $cache->update_pagedata($pagename, array($key => $newval));
    }

    /**
     * Increase page hit count.
     *
     * FIXME: IS this needed?  Probably not.
     *
     * This is a convenience function.
     * <pre> $page->increaseHitCount(); </pre>
     * is functionally identical to
     * <pre> $page->set('hits',$page->get('hits')+1); </pre>
     *
     * Note that this method may be implemented in more efficient ways
     * in certain backends.
     *
     * @access public
     */
    function increaseHitCount() {
        @$newhits = $this->get('hits') + 1;
        $this->set('hits', $newhits);
    }

    /**
     * Return a string representation of the WikiDB_Page
     *
     * This is really only for debugging.
     *
     * @access public
     *
     * @return string Printable representation of the WikiDB_Page.
     */
    function asString () {
        ob_start();
        printf("[%s:%s\n", get_class($this), $this->getName());
        print_r($this->getMetaData());
        echo "]\n";
        $strval = ob_get_contents();
        ob_end_clean();
        return $strval;
    }


    /**
     * @access private
     * @param integer_or_object $version_or_pagerevision
     * Takes either the version number (and int) or a WikiDB_PageRevision
     * object.
     * @return integer The version number.
     */
    function _coerce_to_version($version_or_pagerevision) {
        if (method_exists($version_or_pagerevision, "getContent"))
            $version = $version_or_pagerevision->getVersion();
        else
            $version = (int) $version_or_pagerevision;

        assert($version >= 0);
        return $version;
    }

    function isUserPage ($include_empty = true) {
        if ($include_empty) {
            $current = $this->getCurrentRevision();
            if ($current->hasDefaultContents()) {
                return false;
            }
        }
        return $this->get('pref') ? true : false;
    }

};

/**
 * This class represents a specific revision of a WikiDB_Page within
 * a WikiDB.
 *
 * A WikiDB_PageRevision has read-only semantics. You may only create
 * new revisions (and delete old ones) --- you cannot modify existing
 * revisions.
 */
class WikiDB_PageRevision
{
    var $_transformedContent = false; // set by WikiDB_Page::save()
    
    function WikiDB_PageRevision(&$wikidb, $pagename, $version,
                                 $versiondata = false)
        {
            $this->_wikidb = &$wikidb;
            $this->_pagename = $pagename;
            $this->_version = $version;
            $this->_data = $versiondata ? $versiondata : array();
        }
    
    /**
     * Get the WikiDB_Page which this revision belongs to.
     *
     * @access public
     *
     * @return WikiDB_Page The WikiDB_Page which this revision belongs to.
     */
    function getPage() {
        return new WikiDB_Page($this->_wikidb, $this->_pagename);
    }

    /**
     * Get the version number of this revision.
     *
     * @access public
     *
     * @return integer The version number of this revision.
     */
    function getVersion() {
        return $this->_version;
    }
    
    /**
     * Determine whether this revision has defaulted content.
     *
     * The default revision (version 0) of each page, as well as any
     * pages which are created with empty content have their content
     * defaulted to something like:
     * <pre>
     *   Describe [ThisPage] here.
     * </pre>
     *
     * @access public
     *
     * @return boolean Returns true if the page has default content.
     */
    function hasDefaultContents() {
        $data = &$this->_data;
        return empty($data['%content']);
    }

    /**
     * Get the content as an array of lines.
     *
     * @access public
     *
     * @return array An array of lines.
     * The lines should contain no trailing white space.
     */
    function getContent() {
        return explode("\n", $this->getPackedContent());
    }
	
	/**
     * Get the pagename of the revision.
     *
     * @access public
     *
     * @return string pagename.
     */
    function getPageName() {
        return $this->_pagename;
    }

    /**
     * Determine whether revision is the latest.
     *
     * @access public
     *
     * @return boolean True iff the revision is the latest (most recent) one.
     */
    function isCurrent() {
        if (!isset($this->_iscurrent)) {
            $page = $this->getPage();
            $current = $page->getCurrentRevision();
            $this->_iscurrent = $this->getVersion() == $current->getVersion();
        }
        return $this->_iscurrent;
    }

    /**
     * Get the transformed content of a page.
     *
     * @param string $pagetype  Override the page-type of the revision.
     *
     * @return object An XmlContent-like object containing the page transformed
     * contents.
     */
    function getTransformedContent($pagetype_override=false) {
	$backend = &$this->_wikidb->_backend;
        
	if ($pagetype_override) {
	    // Figure out the normal page-type for this page.
            $type = PageType::GetPageType($this->get('pagetype'));
	    if ($type->getName() == $pagetype_override)
		$pagetype_override = false; // Not really an override...
	}

        if ($pagetype_override) {
            // Overriden page type, don't cache (or check cache).
	    return new TransformedText($this->getPage(),
                                       $this->getPackedContent(),
                                       $this->getMetaData(),
                                       $pagetype_override);
        }

        $possibly_cache_results = true;

        if (defined('WIKIDB_NOCACHE_MARKUP') and WIKIDB_NOCACHE_MARKUP) {
            if (WIKIDB_NOCACHE_MARKUP == 'purge') {
                // flush cache for this page.
                $page = $this->getPage();
                $page->set('_cached_html', false);
            }
            $possibly_cache_results = false;
        }
        elseif (!$this->_transformedContent) {
            //$backend->lock();
            if ($this->isCurrent()) {
                $page = $this->getPage();
                $this->_transformedContent = TransformedText::unpack($page->get('_cached_html'));
            }
            else {
                $possibly_cache_results = false;
            }
            //$backend->unlock();
	}
        
        if (!$this->_transformedContent) {
            $this->_transformedContent
                = new TransformedText($this->getPage(),
                                      $this->getPackedContent(),
                                      $this->getMetaData());
            
            if ($possibly_cache_results) {
                // If we're still the current version, cache the transfomed page.
                //$backend->lock();
                if ($this->isCurrent()) {
                    $page->set('_cached_html', $this->_transformedContent->pack());
                }
                //$backend->unlock();
            }
        }

        return $this->_transformedContent;
    }

    /**
     * Get the content as a string.
     *
     * @access public
     *
     * @return string The page content.
     * Lines are separated by new-lines.
     */
    function getPackedContent() {
        $data = &$this->_data;

        
        if (empty($data['%content'])) {
            include_once('lib/InlineParser.php');
            // Replace empty content with default value.
            return sprintf(_("Describe %s here."), 
			   "[" . WikiEscape($this->_pagename) . "]");
        }

        // There is (non-default) content.
        assert($this->_version > 0);
        
        if (!is_string($data['%content'])) {
            // Content was not provided to us at init time.
            // (This is allowed because for some backends, fetching
            // the content may be expensive, and often is not wanted
            // by the user.)
            //
            // In any case, now we need to get it.
            $data['%content'] = $this->_get_content();
            assert(is_string($data['%content']));
        }
        
        return $data['%content'];
    }

    function _get_content() {
        $cache = &$this->_wikidb->_cache;
        $pagename = $this->_pagename;
        $version = $this->_version;

        assert($version > 0);
        
        $newdata = $cache->get_versiondata($pagename, $version, true);
        if ($newdata) {
            assert(is_string($newdata['%content']));
            return $newdata['%content'];
        }
        else {
            // else revision has been deleted... What to do?
            return __sprintf("Oops! Revision %s of %s seems to have been deleted!",
                             $version, $pagename);
        }
    }

    /**
     * Get meta-data for this revision.
     *
     *
     * @access public
     *
     * @param string $key Which meta-data to access.
     *
     * Some reserved revision meta-data keys are:
     * <dl>
     * <dt> 'mtime' <dd> Time this revision was created (seconds since midnight Jan 1, 1970.)
     *        The 'mtime' meta-value is normally set automatically by the database
     *        backend, but it may be specified explicitly when creating a new revision.
     * <dt> orig_mtime
     *  <dd> To ensure consistency of RecentChanges, the mtimes of the versions
     *       of a page must be monotonically increasing.  If an attempt is
     *       made to create a new revision with an mtime less than that of
     *       the preceeding revision, the new revisions timestamp is force
     *       to be equal to that of the preceeding revision.  In that case,
     *       the originally requested mtime is preserved in 'orig_mtime'.
     * <dt> '_supplanted' <dd> Time this revision ceased to be the most recent.
     *        This meta-value is <em>always</em> automatically maintained by the database
     *        backend.  (It is set from the 'mtime' meta-value of the superceding
     *        revision.)  '_supplanted' has a value of 'false' for the current revision.
     *
     * FIXME: this could be refactored:
     * <dt> author
     *  <dd> Author of the page (as he should be reported in, e.g. RecentChanges.)
     * <dt> author_id
     *  <dd> Authenticated author of a page.  This is used to identify
     *       the distinctness of authors when cleaning old revisions from
     *       the database.
     * <dt> 'is_minor_edit' <dd> Set if change was marked as a minor revision by the author.
     * <dt> 'summary' <dd> Short change summary entered by page author.
     * </dl>
     *
     * Meta-data keys must be valid C identifers (they have to start with a letter
     * or underscore, and can contain only alphanumerics and underscores.)
     *
     * @return string The requested value, or false if the requested value
     * is not defined.
     */
    function get($key) {
        if (!$key || $key[0] == '%')
            return false;
        $data = &$this->_data;
        return isset($data[$key]) ? $data[$key] : false;
    }

    /**
     * Get all the revision page meta-data as a hash.
     *
     * @return hash The revision meta-data.
     */
    function getMetaData() {
        $meta = array();
        foreach ($this->_data as $key => $val) {
            if (!empty($val) && $key[0] != '%')
                $meta[$key] = $val;
        }
        return $meta;
    }
    
            
    /**
     * Return a string representation of the revision.
     *
     * This is really only for debugging.
     *
     * @access public
     *
     * @return string Printable representation of the WikiDB_Page.
     */
    function asString () {
        ob_start();
        printf("[%s:%d\n", get_class($this), $this->get('version'));
        print_r($this->_data);
        echo $this->getPackedContent() . "\n]\n";
        $strval = ob_get_contents();
        ob_end_clean();
        return $strval;
    }
};


/**
 * A class which represents a sequence of WikiDB_Pages.
 */
class WikiDB_PageIterator
{
    function WikiDB_PageIterator(&$wikidb, &$pages) {
        $this->_pages = $pages;
        $this->_wikidb = &$wikidb;
    }
    
    function count () {
        return $this->_pages->count();
    }

    /**
     * Get next WikiDB_Page in sequence.
     *
     * @access public
     *
     * @return WikiDB_Page The next WikiDB_Page in the sequence.
     */
    function next () {
        if ( ! ($next = $this->_pages->next()) )
            return false;

        $pagename = &$next['pagename'];
        if (!$pagename) {
            trigger_error(__FILE__.':'.__LINE__.' empty pagename in WikiDB_PageIterator::next()',E_USER_WARNING);
            var_dump($next);
            return false;
        }
        if (isset($next['pagedata']))
            $this->_wikidb->_cache->cache_data($next);

        return new WikiDB_Page($this->_wikidb, $pagename);
    }

    /**
     * Release resources held by this iterator.
     *
     * The iterator may not be used after free() is called.
     *
     * There is no need to call free(), if next() has returned false.
     * (I.e. if you iterate through all the pages in the sequence,
     * you do not need to call free() --- you only need to call it
     * if you stop before the end of the iterator is reached.)
     *
     * @access public
     */
    function free() {
        $this->_pages->free();
    }

    
    function asArray() {
    	$result = array();
    	while ($page = $this->next())
            $result[] = $page;
        $this->free();
        return $result;
    }
    
    // Not yet used and problematic. Order should be set in the query, not afterwards.
    // See PageList::sortby
    function setSortby ($arg = false) {
        if (!$arg) {
            $arg = @$_GET['sortby'];
            if ($arg) {
                $sortby = substr($arg,1);
                $order  = substr($arg,0,1)=='+' ? 'ASC' : 'DESC';
            }
        }
        if (is_array($arg)) { // array('mtime' => 'desc')
            $sortby = $arg[0];
            $order = $arg[1];
        } else {
            $sortby = $arg;
            $order  = 'ASC';
        }
        // available column types to sort by:
        // todo: we must provide access methods for the generic dumb/iterator
        $this->_types = explode(',','pagename,mtime,hits,version,author,locked,minor,markup');
        if (in_array($sortby,$this->_types))
            $this->_options['sortby'] = $sortby;
        else
            trigger_error(sprintf("Argument %s '%s' ignored",'sortby',$sortby), E_USER_WARNING);
        if (in_array(strtoupper($order),'ASC','DESC')) 
            $this->_options['order'] = strtoupper($order);
        else
            trigger_error(sprintf("Argument %s '%s' ignored",'order',$order), E_USER_WARNING);
    }

};

/**
 * A class which represents a sequence of WikiDB_PageRevisions.
 */
class WikiDB_PageRevisionIterator
{
    function WikiDB_PageRevisionIterator(&$wikidb, &$revisions) {
        $this->_revisions = $revisions;
        $this->_wikidb = &$wikidb;
    }
    
    function count () {
        return $this->_revisions->count();
    }

    /**
     * Get next WikiDB_PageRevision in sequence.
     *
     * @access public
     *
     * @return WikiDB_PageRevision
     * The next WikiDB_PageRevision in the sequence.
     */
    function next () {
        if ( ! ($next = $this->_revisions->next()) )
            return false;

        $this->_wikidb->_cache->cache_data($next);

        $pagename = $next['pagename'];
        $version = $next['version'];
        $versiondata = $next['versiondata'];
        if (DEBUG) {
            if (!(is_string($pagename) and $pagename != '')) {
                trigger_error("empty pagename",E_USER_WARNING);
                return false;
            }
        } else assert(is_string($pagename) and $pagename != '');
        if (DEBUG) {
            if (!is_array($versiondata)) {
                trigger_error("empty versiondata",E_USER_WARNING);
                return false;
            }
        } else assert(is_array($versiondata));
        if (DEBUG) {
            if (!($version > 0)) {
                trigger_error("invalid version",E_USER_WARNING);
                return false;
            }
        } else assert($version > 0);

        return new WikiDB_PageRevision($this->_wikidb, $pagename, $version,
                                       $versiondata);
    }

    /**
     * Release resources held by this iterator.
     *
     * The iterator may not be used after free() is called.
     *
     * There is no need to call free(), if next() has returned false.
     * (I.e. if you iterate through all the revisions in the sequence,
     * you do not need to call free() --- you only need to call it
     * if you stop before the end of the iterator is reached.)
     *
     * @access public
     */
    function free() { 
        $this->_revisions->free();
    }
};


/**
 * Data cache used by WikiDB.
 *
 * FIXME: Maybe rename this to caching_backend (or some such).
 *
 * @access private
 */
class WikiDB_cache 
{
    // FIXME: beautify versiondata cache.  Cache only limited data?

    function WikiDB_cache (&$backend) {
        $this->_backend = &$backend;

        $this->_pagedata_cache = array();
        $this->_versiondata_cache = array();
        array_push ($this->_versiondata_cache, array());
        $this->_glv_cache = array();
    }
    
    function close() {
        $this->_pagedata_cache = false;
        $this->_versiondata_cache = false;
        $this->_glv_cache = false;
    }

    function get_pagedata($pagename) {
        assert(is_string($pagename) && $pagename != '');
        $cache = &$this->_pagedata_cache;

        if (!isset($cache[$pagename]) || !is_array($cache[$pagename])) {
            $cache[$pagename] = $this->_backend->get_pagedata($pagename);
            if (empty($cache[$pagename]))
                $cache[$pagename] = array();
        }

        return $cache[$pagename];
    }
    
    function update_pagedata($pagename, $newdata) {
        assert(is_string($pagename) && $pagename != '');

        $this->_backend->update_pagedata($pagename, $newdata);

        if (is_array($this->_pagedata_cache[$pagename])) {
            $cachedata = &$this->_pagedata_cache[$pagename];
            foreach($newdata as $key => $val)
                $cachedata[$key] = $val;
        }
    }

    function invalidate_cache($pagename) {
        unset ($this->_pagedata_cache[$pagename]);
        unset ($this->_versiondata_cache[$pagename]);
        unset ($this->_glv_cache[$pagename]);
    }
    
    function delete_page($pagename) {
        $this->_backend->delete_page($pagename);
        unset ($this->_pagedata_cache[$pagename]);
        unset ($this->_glv_cache[$pagename]);
    }

    // FIXME: ugly
    function cache_data($data) {
        if (isset($data['pagedata']))
            $this->_pagedata_cache[$data['pagename']] = $data['pagedata'];
    }
    
    function get_versiondata($pagename, $version, $need_content = false) {
        //  FIXME: Seriously ugly hackage
	if (defined('USECACHE') and USECACHE) {   //temporary - for debugging
            assert(is_string($pagename) && $pagename != '');
            // there is a bug here somewhere which results in an assertion failure at line 105
            // of ArchiveCleaner.php  It goes away if we use the next line.
            $need_content = true;
            $nc = $need_content ? '1':'0';
            $cache = &$this->_versiondata_cache;
            if (!isset($cache[$pagename][$version][$nc])||
                !(is_array ($cache[$pagename])) || !(is_array ($cache[$pagename][$version]))) {
                $cache[$pagename][$version][$nc] = 
                    $this->_backend->get_versiondata($pagename,$version, $need_content);
                // If we have retrieved all data, we may as well set the cache for $need_content = false
                if ($need_content){
                    $cache[$pagename][$version]['0'] = $cache[$pagename][$version]['1'];
                }
            }
            $vdata = $cache[$pagename][$version][$nc];
	} else {
            $vdata = $this->_backend->get_versiondata($pagename, $version, $need_content);
	}
        // FIXME: ugly
        if ($vdata && !empty($vdata['%pagedata']))
            $this->_pagedata_cache[$pagename] = $vdata['%pagedata'];
        return $vdata;
    }

    function set_versiondata($pagename, $version, $data) {
        $new = $this->_backend->set_versiondata($pagename, $version, $data);
        // Update the cache
        $this->_versiondata_cache[$pagename][$version]['1'] = $data;
        // FIXME: hack
        $this->_versiondata_cache[$pagename][$version]['0'] = $data;
        // Is this necessary?
        unset($this->_glv_cache[$pagename]);
    }

    function update_versiondata($pagename, $version, $data) {
        $new = $this->_backend->update_versiondata($pagename, $version, $data);
        // Update the cache
        $this->_versiondata_cache[$pagename][$version]['1'] = $data;
        // FIXME: hack
        $this->_versiondata_cache[$pagename][$version]['0'] = $data;
        // Is this necessary?
        unset($this->_glv_cache[$pagename]);
    }

    function delete_versiondata($pagename, $version) {
        $new = $this->_backend->delete_versiondata($pagename, $version);
        unset ($this->_versiondata_cache[$pagename][$version]['1']);
        unset ($this->_versiondata_cache[$pagename][$version]['0']);
        unset ($this->_glv_cache[$pagename]);
    }
	
    function get_latest_version($pagename)  {
	if (defined('USECACHE')){
            assert (is_string($pagename) && $pagename != '');
            $cache = &$this->_glv_cache;	
            if (!isset($cache[$pagename])) {
                $cache[$pagename] = $this->_backend->get_latest_version($pagename);
                if (empty($cache[$pagename]))
                    $cache[$pagename] = 0;
            }
            return $cache[$pagename];
        } else {
            return $this->_backend->get_latest_version($pagename); 
        }
    }

};

// $Log$
// Revision 1.2  2005/07/25 09:27:04  guerin
// (This is a merge from branch CX_2_4_SUP - See Commit #23814)
//
// Applied patch 250 from ST on Partners.
//
// When phpwiki sends an email (wiki monitoring), the content of the page used to be transmitted even if the receiver doesn't have the right permission on the corresponding page.
// Now, the content of the diff is no longer sent but people are aware of page update.
//
// Fix for SR 249 on Partners:
// https://partners.xrce.xerox.com/tracker/?func=detail&aid=249&group_id=120&atid=199
//
// Revision 1.1  2005/04/12 13:33:28  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.55  2004/05/12 19:27:47  rurban
// revert wrong inline optimization.
//
// Revision 1.54  2004/05/12 10:49:55  rurban
// require_once fix for those libs which are loaded before FileFinder and
//   its automatic include_path fix, and where require_once doesn't grok
//   dirname(__FILE__) != './lib'
// upgrade fix with PearDB
// navbar.tmpl: remove spaces for IE &nbsp; button alignment
//
// Revision 1.53  2004/05/08 14:06:12  rurban
// new support for inlined image attributes: [image.jpg size=50x30 align=right]
// minor stability and portability fixes
//
// Revision 1.52  2004/05/06 19:26:16  rurban
// improve stability, trying to find the InlineParser endless loop on sf.net
//
// remove end-of-zip comments to fix sf.net bug #777278 and probably #859628
//
// Revision 1.51  2004/05/06 17:30:37  rurban
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
// Revision 1.50  2004/05/04 22:34:25  rurban
// more pdf support
//
// Revision 1.49  2004/05/03 11:16:40  rurban
// fixed sendPageChangeNotification
// subject rewording
//
// Revision 1.48  2004/04/29 23:03:54  rurban
// fixed sf.net bug #940996
//
// Revision 1.47  2004/04/29 19:39:44  rurban
// special support for formatted plugins (one-liners)
//   like <small><plugin BlaBla ></small>
// iter->asArray() helper for PopularNearby
// db_session for older php's (no &func() allowed)
//
// Revision 1.46  2004/04/26 20:44:34  rurban
// locking table specific for better databases
//
// Revision 1.45  2004/04/20 00:06:03  rurban
// themable paging support
//
// Revision 1.44  2004/04/19 18:27:45  rurban
// Prevent from some PHP5 warnings (ref args, no :: object init)
//   php5 runs now through, just one wrong XmlElement object init missing
// Removed unneccesary UpgradeUser lines
// Changed WikiLink to omit version if current (RecentChanges)
//
// Revision 1.43  2004/04/18 01:34:20  rurban
// protect most_popular from sortby=mtime
//
// Revision 1.42  2004/04/18 01:11:51  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
