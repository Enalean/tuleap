<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright 1999,2000,2001,2002,2004,2005 $ThePhpWikiProgrammingTeam
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\PHPWiki\WikiPage;

require_once('lib/PageType.php');
require_once('lib/WikiNotification.php');
/**
 * The classes in the file define the interface to the
 * page database.
 *
 */

/**
 * Force the creation of a new revision.
 * @see WikiDB_Page::createRevision()
 */
if (!defined('WIKIDB_FORCE_CREATE')) {
    define('WIKIDB_FORCE_CREATE', -1);
}

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
class WikiDB
{
    /**
     * Open a WikiDB database.
     *
     * This is a static member function. This function inspects its
     * arguments to determine the proper subclass of WikiDB to
     * instantiate, and then it instantiates it.
     *
     * @return WikiDB A WikiDB object.
     **/
    public static function open()
    {
        include_once __DIR__ . '/WikiDB/SQL.php';
        return new WikiDB_SQL();
    }


    /**
     *
     *
     * @access private
     * @see open()
     */
    public function __construct(&$backend)
    {
        $this->_backend = &$backend;

        $this->_cache = new WikiDB_cache($backend);
        if (!empty($GLOBALS['request'])) {
            $GLOBALS['request']->_dbi = $this;
        }

        // If the database doesn't yet have a timestamp, initialize it now.
        if ($this->get('_timestamp') === false) {
            $this->touch();
        }

        //FIXME: devel checking.
        //$this->_backend->check();
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
    public function close()
    {
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
    public function getPage($pagename)
    {
        static $error_displayed = false;
        $pagename = (string) $pagename;
        if (DEBUG) {
            if ($pagename === '') {
                if ($error_displayed) {
                    return false;
                }
                $error_displayed = true;
                if (function_exists("xdebug_get_function_stack")) {
                    var_dump(xdebug_get_function_stack());
                }
                trigger_error("empty pagename", E_USER_WARNING);
                return false;
            }
        } else {
            assert($pagename != '');
        }
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
     * @return bool True if the page actually exists with
 * non-default contents in the WikiDataBase.
     */
    public function isWikiPage($pagename)
    {
        $page = $this->getPage($pagename);
        return $page->exists();
    }

    /**
     * Delete page from the WikiDB.
     *
     * Deletes the page from the WikiDB with the possibility to revert and diff.
     * //Also resets all page meta-data to the default values.
     *
     * Note: purgePage() effectively destroys all revisions of the page from the WikiDB.
     *
     * @access public
     *
     * @param string $pagename Name of page to delete.
     */
    public function deletePage($pagename)
    {
        // don't create empty revisions of already purged pages.
        if ($this->_backend->get_latest_version($pagename)) {
            $result = $this->_cache->delete_page($pagename);
        } else {
            $result = -1;
        }

        /* Generate notification emails? */
        if (! $this->isWikiPage($pagename) and !isa($GLOBALS['request'], 'MockRequest')) {
            $notify = $this->get('notify');
            if (!empty($notify) and is_array($notify)) {
                global $request;
                //TODO: deferr it (quite a massive load if you remove some pages).
                //TODO: notification class which catches all changes,
                //  and decides at the end of the request what to mail.
                //  (type, page, who, what, users, emails)
                // could be used for PageModeration and RSS2 Cloud xml-rpc also.
                $page = new WikiDB_Page($this, $pagename);
                list($emails, $userids) = $page->getPageChangeEmails($notify);
                if (!empty($emails)) {
                    // Codendi specific
                    $user              = UserManager::instance()->getCurrentUser();
                    $subject           = sprintf(_("Page removed %s"), $pagename);
                    $body              = $subject . "\n" .
                                         sprintf(_("Removed by: %s"), $user->getRealName() . ' (' . $user->getEmail() . ')') .
                                         "\n\n";
                    $goto_link         = WikiURL($pagename, array('action' => 'PageHistory'), true);
                    $wiki_notification = new WikiNotification($emails, WIKI_NAME, $subject, $body, $goto_link, GROUP_ID);
                    if ($wiki_notification->send()) {
                        trigger_error(
                            sprintf(_("PageChange Notification of %s sent to %s"), $pagename, join(',', $userids)),
                            E_USER_NOTICE
                        );
                    } else {
                        trigger_error(
                            sprintf(_("PageChange Notification Error: Couldn't send %s to %s"), $pagename, join(',', $userids)),
                            E_USER_WARNING
                        );
                    }
                }
            }
        }

        //How to create a RecentChanges entry with explaining summary? Dynamically
        /*
        $page = $this->getPage($pagename);
        $current = $page->getCurrentRevision();
        $meta = $current->_data;
        $version = $current->getVersion();
        $meta['summary'] = _("removed");
        $page->save($current->getPackedContent(), $version + 1, $meta);
        */
        return $result;
    }

    /**
     * Completely remove the page from the WikiDB, without undo possibility.
     */
    public function purgePage($pagename)
    {
        $result = $this->_cache->purge_page($pagename);
        $this->deletePage($pagename); // just for the notification
        return $result;
    }

    /**
     * Retrieve all pages.
     *
     * Gets the set of all pages with non-default contents.
     *
     * @access public
     *
     * @param bool $include_defaulted Normally pages whose most
 * recent revision has empty content are considered to be
 * non-existant. Unless $include_defaulted is set to true, those
 * pages will not be returned.
     *
     * @return WikiDB_PageIterator A WikiDB_PageIterator which contains all pages
     *     in the WikiDB which have non-default contents.
     */
    public function getAllPages(
        $include_empty = false,
        $sortby = false,
        $limit = false,
        $exclude = false
    ) {
        $result = $this->_backend->get_all_pages($include_empty, $sortby, $limit, $exclude);
        return new WikiDB_PageIterator(
            $this,
            $result,
            array(
                'include_empty' => $include_empty,
                'exclude' => $exclude,
                'limit' => $limit
            )
        );
    }

    /**
     * $include_empty = true: include also empty pages
     * exclude: comma-seperated list pagenames: TBD: array of pagenames
     */
    public function numPages($include_empty = false, $exclude = '')
    {
        if (method_exists($this->_backend, 'numPages')) {
            // FIXME: currently are all args ignored.
            $count = $this->_backend->numPages($include_empty, $exclude);
        } else {
            // FIXME: exclude ignored.
            $iter = $this->getAllPages($include_empty, false, false, $exclude);
            $count = $iter->count();
            $iter->free();
        }
        return (int) $count;
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
     * FIXME: clarify $search syntax. provide glob=>TextSearchQuery converters
     *
     * @access public
     * @param TextSearchQuery $search A TextSearchQuery object
     * @return WikiDB_PageIterator A WikiDB_PageIterator containing the matching pages.
     * @see TextSearchQuery
     */
    public function titleSearch($search, $sortby = 'pagename', $limit = false, $exclude = false)
    {
        $result = $this->_backend->text_search($search, false, $sortby, $limit, $exclude);
        return new WikiDB_PageIterator(
            $this,
            $result,
            array(
                'exclude' => $exclude,
                'limit' => $limit
            )
        );
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
    public function fullSearch($search, $sortby = 'pagename', $limit = false, $exclude = false)
    {
        $result = $this->_backend->text_search($search, true, $sortby, $limit, $exclude);
        return new WikiDB_PageIterator(
            $this,
            $result,
            array(
                'exclude' => $exclude,
                'limit' => $limit,
                'stoplisted' => $result->stoplisted
            )
        );
    }

    /**
     * Find the pages with the greatest hit counts.
     *
     * Pages are returned in reverse order by hit count.
     *
     * @access public
     *
     * @param int $limit The maximum number of pages to return.
 * Set $limit to zero to return all pages.  If $limit < 0, pages will
 * be sorted in decreasing order of popularity.
     *
     * @return WikiDB_PageIterator A WikiDB_PageIterator containing the matching
     * pages.
     */
    public function mostPopular($limit = 20, $sortby = '-hits')
    {
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
    public function mostRecent($params = false)
    {
        $result = $this->_backend->most_recent($params);
        return new WikiDB_PageRevisionIterator($this, $result);
    }

    /**
     * @access public
     *
     * @return Iterator A generic iterator containing rows of (duplicate) pagename, wantedfrom.
     */
    public function wantedPages($exclude_from = '', $exclude = '', $sortby = false, $limit = false)
    {
        return $this->_backend->wanted_pages($exclude_from, $exclude, $sortby, $limit);
        //return new WikiDB_PageIterator($this, $result);
    }


    /**
     * Call the appropriate backend method.
     *
     * @access public
     * @param string $from Page to rename
     * @param string $to   New name
     * @param bool $updateWikiLinks If the text in all pages should be replaced.
     * @return bool true or false
     */
    public function renamePage($from, $to, $updateWikiLinks = false)
    {
        assert(is_string($from) && $from != '');
        assert(is_string($to) && $to != '');
        $result = false;
        if (method_exists($this->_backend, 'rename_page')) {
            $oldpage = $this->getPage($from);
            $newpage = $this->getPage($to);
            //update all WikiLinks in existing pages
            //non-atomic! i.e. if rename fails the links are not undone
            if ($updateWikiLinks) {
                require_once('lib/plugin/WikiAdminSearchReplace.php');
                $links = $oldpage->getBackLinks();
                while ($linked_page = $links->next()) {
                    WikiPlugin_WikiAdminSearchReplace::replaceHelper($this, $linked_page->getName(), $from, $to);
                }
                $links = $newpage->getBackLinks();
                while ($linked_page = $links->next()) {
                    WikiPlugin_WikiAdminSearchReplace::replaceHelper($this, $linked_page->getName(), $from, $to);
                }
            }
            if ($oldpage->exists() and ! $newpage->exists()) {
                if ($result = $this->_backend->rename_page($from, $to)) {
                    //create a RecentChanges entry with explaining summary
                    $page = $this->getPage($to);
                    $current = $page->getCurrentRevision();
                    $meta = $current->_data;
                    $version = $current->getVersion();
                    $meta['summary'] = sprintf(_("renamed from %s"), $from);
                    $page->save($current->getPackedContent(), $version + 1, $meta);
                }
            } elseif (!$oldpage->getCurrentRevision(false) and !$newpage->exists()) {
                // if a version 0 exists try it also.
                $result = $this->_backend->rename_page($from, $to);
            }
        } else {
            trigger_error(_("WikiDB::renamePage() not yet implemented for this backend"), E_USER_WARNING);
        }
        /* Generate notification emails? */
        if ($result and !isa($GLOBALS['request'], 'MockRequest')) {
            $notify = $this->get('notify');
            if (!empty($notify) and is_array($notify)) {
                list($emails, $userids) = $oldpage->getPageChangeEmails($notify);
                if (!empty($emails)) {
                    $oldpage->sendPageRenameNotification($to, $meta, $emails, $userids);
                }
            }
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
    public function getTimestamp()
    {
        $ts = $this->get('_timestamp');
        return sprintf("%d %d", $ts[0], $ts[1]);
    }

    /**
     * Update the database timestamp.
     *
     */
    public function touch()
    {
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
    public function get($key)
    {
        if (!$key || $key[0] == '%') {
            return false;
        }
        /*
         * Hack Alert: We can use any page (existing or not) to store
         * this data (as long as we always use the same one.)
         */
        $gd = $this->getPage('global_data');
        $data = $gd->get('__global');

        if ($data && isset($data[$key])) {
            return $data[$key];
        } else {
            return false;
        }
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
    public function set($key, $newval)
    {
        if (!$key || $key[0] == '%') {
            return;
        }

        $gd = $this->getPage('global_data');
        $data = $gd->get('__global');
        if ($data === false) {
            $data = array();
        }

        if (empty($newval)) {
            unset($data[$key]);
        } else {
            $data[$key] = $newval;
        }

        $gd->set('__global', $data);
    }

    /* TODO: these are really backend methods */

    // SQL result: for simple select or create/update queries
    // returns the database specific resource type
    public function genericSqlQuery($sql, $args = false)
    {
        if (function_exists('debug_backtrace')) { // >= 4.3.0
            echo "<pre>", printSimpleTrace(debug_backtrace()), "</pre>\n";
        }
        trigger_error("no SQL database", E_USER_ERROR);
        return false;
    }

    // SQL iter: for simple select or create/update queries
    // returns the generic iterator object (count,next)
    public function genericSqlIter($sql, $field_list = null)
    {
        if (function_exists('debug_backtrace')) { // >= 4.3.0
            echo "<pre>", printSimpleTrace(debug_backtrace()), "</pre>\n";
        }
        trigger_error("no SQL database", E_USER_ERROR);
        return false;
    }

    // see backend upstream methods
    // ADODB adds surrounding quotes, SQL not yet!
    public function quote($s)
    {
        return $s;
    }

    public function isOpen()
    {
        global $request;
        if (!$request->_dbi) {
            return false;
        } else {
            return false; /* so far only needed for sql so false it. later we have to check dba also */
        }
    }

    public function getParam($param)
    {
        global $DBParams;
        if (isset($DBParams[$param])) {
            return $DBParams[$param];
        } elseif ($param == 'prefix') {
            return '';
        } else {
            return false;
        }
    }

    public function getAuthParam($param)
    {
        global $DBAuthParams;
        if (isset($DBAuthParams[$param])) {
            return $DBAuthParams[$param];
        } elseif ($param == 'USER_AUTH_ORDER') {
            return $GLOBALS['USER_AUTH_ORDER'];
        } elseif ($param == 'USER_AUTH_POLICY') {
            return $GLOBALS['USER_AUTH_POLICY'];
        } else {
            return false;
        }
    }
}


/**
 * An abstract base class which representing a wiki-page within a
 * WikiDB.
 *
 * A WikiDB_Page contains a number (at least one) of
 * WikiDB_PageRevisions.
 */
class WikiDB_Page
{
    public function __construct(&$wikidb, $pagename)
    {
        $this->_wikidb = &$wikidb;
        $this->_pagename = $pagename;
        if (DEBUG) {
            if (!(is_string($pagename) and $pagename != '')) {
                trigger_error("empty pagename", E_USER_WARNING);
                return;
            }
        } else {
            assert(is_string($pagename) and $pagename != '');
        }
    }

    /**
     * Get the name of the wiki page.
     *
     * @access public
     *
     * @return string The page name.
     */
    public function getName()
    {
        return $this->_pagename;
    }

    // To reduce the memory footprint for larger sets of pagelists,
    // we don't cache the content (only true or false) and
    // we purge the pagedata (_cached_html) also
    public function exists()
    {
        if (isset($this->_wikidb->_cache->_id_cache[$this->_pagename])) {
            return true;
        }
        $current = $this->getCurrentRevision(false);
        if (!$current) {
            return false;
        }
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
     * @param int $version Which revision to delete.  (You can also
 * use a WikiDB_PageRevision object here.)
     */
    public function deleteRevision($version)
    {
        $backend = &$this->_wikidb->_backend;
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;

        $version = $this->_coerce_to_version($version);
        if ($version == 0) {
            return;
        }

        $backend->lock(array('page','version'));
        $latestversion = $cache->get_latest_version($pagename);
        if ($latestversion && ($version == $latestversion)) {
            $backend->unlock(array('page','version'));
            trigger_error(sprintf("Attempt to delete most recent revision of '%s'", $pagename), E_USER_ERROR);
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
    public function mergeRevision($version)
    {
        $backend = &$this->_wikidb->_backend;
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;

        $version = $this->_coerce_to_version($version);
        if ($version == 0) {
            return;
        }

        $backend->lock(array('version'));
        $latestversion = $cache->get_latest_version($pagename);
        if ($latestversion && $version == $latestversion) {
            $backend->unlock(array('version'));
            trigger_error(sprintf("Attempt to merge most recent revision of '%s'", $pagename), E_USER_ERROR);
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
                    $cache->update_versiondata(
                        $pagename,
                        $previous,
                        array('%content' => $versiondata['%content'], '_supplanted' => $versiondata['_supplanted'])
                    );
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
    public function createRevision($version, &$content, $metadata, $links)
    {
        $backend = &$this->_wikidb->_backend;
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;
        $cache->invalidate_cache($pagename);

        $backend->lock(array('version','page','recent','link','nonempty'));

        $latestversion = $backend->get_latest_version($pagename);
        $newversion = ($latestversion ? $latestversion : 0) + 1;
        assert($newversion >= 1);

        if ($version != WIKIDB_FORCE_CREATE and $version != $newversion) {
            $backend->unlock(array('version','page','recent','link','nonempty'));
            return false;
        }

        $data = $metadata;

        foreach ($data as $key => $val) {
            if (empty($val) || $key[0] == '_' || $key[0] == '%') {
                unset($data[$key]);
            }
        }

        assert(!empty($data['author']));
        if (empty($data['author_id'])) {
            @$data['author_id'] = $data['author'];
        }

        if (empty($data['mtime'])) {
            $data['mtime'] = time();
        }

        if ($latestversion and $version != WIKIDB_FORCE_CREATE) {
            // Ensure mtimes are monotonic.
            $pdata = $cache->get_versiondata($pagename, $latestversion);
            if ($data['mtime'] < $pdata['mtime']) {
                trigger_error(sprintf(_("%s: Date of new revision is %s"), $pagename, "'non-monotonic'"), E_USER_NOTICE);
                $data['orig_mtime'] = $data['mtime'];
                $data['mtime'] = $pdata['mtime'];
            }

        // FIXME: use (possibly user specified) 'mtime' time or
        // time()?
            $cache->update_versiondata($pagename, $latestversion, array('_supplanted' => $data['mtime']));
        }

        $data['%content'] = &$content;

        $cache->set_versiondata($pagename, $newversion, $data);

        //$cache->update_pagedata($pagename, array(':latestversion' => $newversion,
        //':deleted' => empty($content)));

        $backend->set_links($pagename, $links);

        $backend->unlock(array('version','page','recent','link','nonempty'));

        return new WikiDB_PageRevision($this->_wikidb, $pagename, $newversion, $data);
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
    public function save($wikitext, $version, $meta)
    {
        $formatted = new TransformedText($this, $wikitext, $meta);
        $type = $formatted->getType();
        $meta['pagetype'] = $type->getName();
        $links = $formatted->getWikiPageLinks();

        $backend = &$this->_wikidb->_backend;
        $newrevision = $this->createRevision($version, $wikitext, $meta, $links);
        if ($newrevision and !WIKIDB_NOCACHE_MARKUP) {
            $this->set('_cached_html', $formatted->pack());
        }

        /* Generate notification emails? */
        if (ENABLE_EMAIL_NOTIFIFICATION && isa($newrevision, 'WikiDB_PageRevision')) {
            // Save didn't fail because of concurrent updates.
            $notify = $this->_wikidb->get('notify');
            if (!empty($notify) and is_array($notify) and !isa($GLOBALS['request'], 'MockRequest')) {
                list($emails, $userids) = $this->getPageChangeEmails($notify);
                if (!empty($emails)) {
                    $this->sendPageChangeNotification($wikitext, $version, $meta, $emails, $userids);
                }
            }
            $newrevision->_transformedContent = $formatted;
        }

        return $newrevision;
    }

    public function getPageChangeEmails($notify)
    {
        $emails = array();
        $userids = array();
        foreach ($notify as $page => $users) {
            if (glob_match($page, $this->_pagename)) {
                foreach ($users as $userid => $user) {
                    $um           = UserManager::instance();
                    $dbUser       = $um->getUserByUserName($userid);
                    $wiki         = new Wiki($_REQUEST['group_id']);
                    $wp           = new WikiPage($_REQUEST['group_id'], $_REQUEST['pagename']);
                    $project      = ProjectManager::instance()->getProject($_REQUEST['group_id']);
                    $url_verifier = new URLVerification();

                    $user_can_access_project = false;
                    try {
                        $user_can_access_project = $dbUser !== null &&
                            $url_verifier->userCanAccessProject($dbUser, $project);
                    } catch (Project_AccessException $e) {
                        continue;
                    }

                    if ($user_can_access_project &&
                        $wiki->isAutorized($dbUser->getId()) &&
                        $wp->isAutorized($dbUser->getId())
                    ) {
                        if (!$user) { // handle the case for ModeratePage: no prefs, just userid's.
                            global $request;
                            $u = $request->getUser();
                            if ($u->UserName() == $userid) {
                                $prefs = $u->getPreferences();
                            } else {
                                // not current user
                                if (ENABLE_USER_NEW) {
                                    $u = WikiUser($userid);
                                    $u->getPreferences();
                                    $prefs = &$u->_prefs;
                                } else {
                                    $u = new WikiUser($GLOBALS['request'], $userid);
                                    $prefs = $u->getPreferences();
                                }
                            }
                            $emails[] = user_getemail_from_unix($userid);
                            $userids[] = $userid;
                        } else {
                            if (!empty($user['verified']) and !empty($user['email'])) {
                                $emails[]  = user_getemail_from_unix($userid);
                                $userids[] = $userid;
                            } elseif (!empty($user['email'])) {
                                global $request;
                                // do a dynamic emailVerified check update
                                $u = $request->getUser();
                                if ($u->UserName() == $userid) {
                                    if ($request->_prefs->get('emailVerified')) {
                                        $emails[] =  user_getemail_from_unix($userid);
                                        $userids[] = $userid;
                                        $notify[$page][$userid]['verified'] = 1;
                                        $request->_dbi->set('notify', $notify);
                                    }
                                } else {
                                    // not current user
                                    if (ENABLE_USER_NEW) {
                                        $u = WikiUser($userid);
                                        $u->getPreferences();
                                        $prefs = &$u->_prefs;
                                    } else {
                                        $u = new WikiUser($GLOBALS['request'], $userid);
                                        $prefs = $u->getPreferences();
                                    }
                                    if ($prefs->get('emailVerified')) {
                                        $emails[] = user_getemail_from_unix($userid);
                                        $userids[] = $userid;
                                        $notify[$page][$userid]['verified'] = 1;
                                        $request->_dbi->set('notify', $notify);
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
            }
        }
        $emails = array_unique($emails);
        $userids = array_unique($userids);
        return array($emails, $userids);
    }

    /**
     * Send udiff for a changed page to multiple users.
     * See rename and remove methods also
     */
    public function sendPageChangeNotification(&$wikitext, $version, $meta, $emails, $userids)
    {
        global $request;
        if (@is_array($request->_deferredPageChangeNotification)) {
            // collapse multiple changes (loaddir) into one email
            $request->_deferredPageChangeNotification[]
            = array($this->_pagename, $emails, $userids);
            return;
        }
        $backend = &$this->_wikidb->_backend;
        //$backend = &$request->_dbi->_backend;
        $subject = _("Page change") . ' ' . $this->_pagename;
        $previous = $backend->get_previous_version($this->_pagename, $version);
        if (!isset($meta['mtime'])) {
            $meta['mtime'] = time();
        }
        if ($previous) {
            $difflink = WikiURL($this->_pagename, array('action' => 'diff'), true);
            $difflink .= "&versions%5b%5d=" . $previous . "&versions%5b%5d=" . $version;
            $cache = &$this->_wikidb->_cache;
            //$cache = &$request->_dbi->_cache;
            $this_content = explode("\n", $wikitext);
            $prevdata = $cache->get_versiondata($this->_pagename, $previous, true);
            if (empty($prevdata['%content'])) {
                $prevdata = $backend->get_versiondata($this->_pagename, $previous, true);
            }
            $other_content = explode("\n", $prevdata['%content']);

            include_once("lib/difflib.php");
            $diff2 = new Diff($other_content, $this_content);
            //$context_lines = max(4, count($other_content) + 1,
            //                     count($this_content) + 1);
            $fmt = new UnifiedDiffFormatter(/*$context_lines*/);
            $content  = $this->_pagename . " " . $previous . " " .
                Iso8601DateTime($prevdata['mtime']) . "\n";
            $content .= $this->_pagename . " " . $version . " " .
                Iso8601DateTime($meta['mtime']) . "\n";
            $content .= $fmt->format($diff2);
        } else {
            $difflink = WikiURL($this->_pagename, array(), true);
            $content = $this->_pagename . " " . $version . " " .
                Iso8601DateTime($meta['mtime']) . "\n";
            $content .= _("New page");
        }
        // Codendi specific
        $user              = UserManager::instance()->getCurrentUser();
        $body              = $subject . "\n" .
                             sprintf(_("Edited by: %s"), $user->getRealName() . ' (' . $user->getEmail() . ')') . "\n" .
                             $difflink;
        $wiki_notification = new WikiNotification($emails, WIKI_NAME, $subject, $body, $difflink, GROUP_ID);
        if ($wiki_notification->send()) {
            trigger_error(
                sprintf(_("PageChange Notification of %s sent to %s"), $this->_pagename, join(',', $userids)),
                E_USER_NOTICE
            );
        } else {
            trigger_error(
                sprintf(_("PageChange Notification Error: Couldn't send %s to %s"), $this->_pagename, join(',', $userids)),
                E_USER_WARNING
            );
        }
    }

    /** support mass rename / remove (not yet tested)
     */
    public function sendPageRenameNotification($to, &$meta, $emails, $userids)
    {
        global $request;
        if (@is_array($request->_deferredPageRenameNotification)) {
            $request->_deferredPageRenameNotification[] = array($this->_pagename,
                                                                $to, $meta, $emails, $userids);
        } else {
            $oldname = $this->_pagename;
            // Codendi specific
            $user              = UserManager::instance()->getCurrentUser();
            $goto_link         = WikiURL($to, array(), true);
            $subject           = sprintf(_("Page rename %s to %s"), $oldname, $to);
            $body              = $subject . "\n" .
                                 sprintf(_("Edited by: %s"), $user->getRealName() . ' (' . $user->getEmail() . ')') . "\n" .
                                 $goto_link;
            $wiki_notification = new WikiNotification($emails, WIKI_NAME, $subject, $body, $goto_link, GROUP_ID);
            if ($wiki_notification->send()) {
                trigger_error(
                    sprintf(_("PageChange Notification of %s sent to %s"), $oldname, join(',', $userids)),
                    E_USER_NOTICE
                );
            } else {
                trigger_error(
                    sprintf(_("PageChange Notification Error: Couldn't send %s to %s"), $oldname, join(',', $userids)),
                    E_USER_WARNING
                );
            }
        }
    }

    /**
     * Get the most recent revision of a page.
     *
     * @access public
     *
     * @return WikiDB_PageRevision The current WikiDB_PageRevision object.
     */
    public function getCurrentRevision($need_content = true)
    {
        $backend = &$this->_wikidb->_backend;
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;

        // Prevent deadlock in case of memory exhausted errors
        // Pure selection doesn't really need locking here.
        //   sf.net bug#927395
        // I know it would be better to lock, but with lots of pages this deadlock is more
        // severe than occasionally get not the latest revision.
        // In spirit to wikiwiki: read fast, edit slower.
        //$backend->lock();
        $version = $cache->get_latest_version($pagename);
        // getRevision gets the content also!
        $revision = $this->getRevision($version, $need_content);
        //$backend->unlock();
        assert($revision);
        return $revision;
    }

    /**
     * Get a specific revision of a WikiDB_Page.
     *
     * @access public
     *
     * @param int $version Which revision to get.
     *
     * @return WikiDB_PageRevision The requested WikiDB_PageRevision object, or
     * false if the requested revision does not exist in the {@link WikiDB}.
     * Note that version zero of any page always exists.
     */
    public function getRevision($version, $need_content = true)
    {
        $cache = &$this->_wikidb->_cache;
        $pagename = &$this->_pagename;

        if (! $version or $version == -1) { // 0 or false
            return new WikiDB_PageRevision($this->_wikidb, $pagename, 0);
        }

        assert($version > 0);
        $vdata = $cache->get_versiondata($pagename, $version, $need_content);
        if (! $vdata) {
            return new WikiDB_PageRevision($this->_wikidb, $pagename, 0);
        }
        return new WikiDB_PageRevision($this->_wikidb, $pagename, $version, $vdata);
    }

    /**
     * Get previous page revision.
     *
     * This method find the most recent revision before a specified
     * version.
     *
     * @access public
     *
     * @param int $version Find most recent revision before this version.
 * You can also use a WikiDB_PageRevision object to specify the $version.
     *
     * @return WikiDB_PageRevision The requested WikiDB_PageRevision object, or false if the
     * requested revision does not exist in the {@link WikiDB}.  Note that
     * unless $version is greater than zero, a revision (perhaps version zero,
     * the default revision) will always be found.
     */
    public function getRevisionBefore($version = false, $need_content = true)
    {
        $backend = &$this->_wikidb->_backend;
        $pagename = &$this->_pagename;
        if ($version === false) {
            $version = $this->_wikidb->_cache->get_latest_version($pagename);
        } else {
            $version = $this->_coerce_to_version($version);
        }

        if ($version == 0) {
            return false;
        }
        //$backend->lock();
        $previous = $backend->get_previous_version($pagename, $version);
        $revision = $this->getRevision($previous, $need_content);
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
     *   WikiDB_PageRevisionIterator containing all revisions of this
     *   WikiDB_Page in reverse order by version number.
     */
    public function getAllRevisions()
    {
        $backend = &$this->_wikidb->_backend;
        $revs = $backend->get_all_revisions($this->_pagename);
        return new WikiDB_PageRevisionIterator($this->_wikidb, $revs);
    }

    /**
     * Find pages which link to or are linked from a page.
     *
     * @access public
     *
     * @param bool $reversed Which links to find: true for backlinks (default).
     *
     * @return WikiDB_PageIterator A WikiDB_PageIterator containing
     * all matching pages.
     */
    public function getLinks(
        $reversed = true,
        $include_empty = false,
        $sortby = false,
        $limit = false,
        $exclude = false
    ) {
        $backend = &$this->_wikidb->_backend;
        $result =  $backend->get_links($this->_pagename, $reversed, $include_empty, $sortby, $limit, $exclude);
        return new WikiDB_PageIterator(
            $this->_wikidb,
            $result,
            array(
                'include_empty' => $include_empty,
                'sortby' => $sortby,
                'limit' => $limit,
                'exclude' => $exclude
            )
        );
    }

    /**
     * All Links from other pages to this page.
     */
    public function getBackLinks($include_empty = false, $sortby = false, $limit = false, $exclude = false)
    {
        return $this->getLinks(true, $include_empty, $sortby, $limit, $exclude);
    }
    /**
     * Forward Links: All Links from this page to other pages.
     */
    public function getPageLinks($include_empty = false, $sortby = false, $limit = false, $exclude = false)
    {
        return $this->getLinks(false, $include_empty, $sortby, $limit, $exclude);
    }

    /**
     * possibly faster link existance check. not yet accelerated.
     */
    public function existLink($link, $reversed = false)
    {
        $backend = &$this->_wikidb->_backend;
        if (method_exists($backend, 'exists_link')) {
            return $backend->exists_link($this->_pagename, $link, $reversed);
        }
        //$cache = &$this->_wikidb->_cache;
        // TODO: check cache if it is possible
        $iter = $this->getLinks($reversed, false);
        while ($page = $iter->next()) {
            if ($page->getName() == $link) {
                return $page;
            }
        }
        $iter->free();
        return false;
    }

    /**
     * Access WikiDB_Page non version-specific meta-data.
     *
     * @access public
     *
     * @param string $key Which meta data to get.
     * Some reserved meta-data keys are:
     * <dl>
     * <dt>'date'  <dd> Created as unixtime
     * <dt>'locked'<dd> Is page locked? 'yes' or 'no'
     * <dt>'hits'  <dd> Page hit counter.
     * <dt>'_cached_html' <dd> Transformed CachedMarkup object, serialized + optionally gzipped.
     *                         In SQL stored now in an extra column.
     * Optional data:
     * <dt>'pref'  <dd> Users preferences, stored only in homepages.
     * <dt>'owner' <dd> Default: first author_id. We might add a group with a dot here:
     *                  E.g. "owner.users"
     * <dt>'perm'  <dd> Permission flag to authorize read/write/execution of
     *                  page-headers and content.
     + <dt>'moderation'<dd> ModeratedPage data
     * <dt>'score' <dd> Page score (not yet implement, do we need?)
     * </dl>
     *
     * @return scalar The requested value, or false if the requested data
     * is not set.
     */
    public function get($key)
    {
        $cache = &$this->_wikidb->_cache;
        $backend = &$this->_wikidb->_backend;
        if (!$key || $key[0] == '%') {
            return false;
        }
        // several new SQL backends optimize this.
        if (!WIKIDB_NOCACHE_MARKUP
            and $key == '_cached_html'
            and method_exists($backend, 'get_cached_html')
        ) {
            return $backend->get_cached_html($this->_pagename);
        }
        $data = $cache->get_pagedata($this->_pagename);
        return isset($data[$key]) ? $data[$key] : false;
    }

    /**
     * Get all the page meta-data as a hash.
     *
     * @return hash The page meta-data.
     */
    public function getMetaData()
    {
        $cache = &$this->_wikidb->_cache;
        $data = $cache->get_pagedata($this->_pagename);
        $meta = array();
        foreach ($data as $key => $val) {
            if (/*!empty($val) &&*/ $key[0] != '%') {
                $meta[$key] = $val;
            }
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
    public function set($key, $newval)
    {
        $cache = &$this->_wikidb->_cache;
        $backend = &$this->_wikidb->_backend;
        $pagename = &$this->_pagename;

        assert($key && $key[0] != '%');

        // several new SQL backends optimize this.
        if (!WIKIDB_NOCACHE_MARKUP
            and $key == '_cached_html'
            and method_exists($backend, 'set_cached_html')
        ) {
            return $backend->set_cached_html($pagename, $newval);
        }

        $data = $cache->get_pagedata($pagename);

        if (!empty($newval)) {
            if (!empty($data[$key]) && $data[$key] == $newval) {
                return;         // values identical, skip update.
            }
        } else {
            if (empty($data[$key])) {
                return; // values identical, skip update.
            }
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
     * but less expensive (ignores the pagadata string)
     *
     * Note that this method may be implemented in more efficient ways
     * in certain backends.
     *
     * @access public
     */
    public function increaseHitCount()
    {
        if (method_exists($this->_wikidb->_backend, 'increaseHitCount')) {
            $this->_wikidb->_backend->increaseHitCount($this->_pagename);
        } else {
            @$newhits = $this->get('hits') + 1;
            $this->set('hits', $newhits);
        }
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
    public function asString()
    {
        ob_start();
        printf("[%s:%s\n", static::class, $this->getName());
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
     * @return int The version number.
     */
    public function _coerce_to_version($version_or_pagerevision)
    {
        if (method_exists($version_or_pagerevision, "getContent")) {
            $version = $version_or_pagerevision->getVersion();
        } else {
            $version = (int) $version_or_pagerevision;
        }

        assert($version >= 0);
        return $version;
    }

    public function isUserPage($include_empty = true)
    {
        if (!$include_empty and !$this->exists()) {
            return false;
        }
        return $this->get('pref') ? true : false;
    }

    // May be empty. Either the stored owner (/Chown), or the first authorized author
    public function getOwner()
    {
        if ($owner = $this->get('owner')) {
            return ($owner == "The PhpWiki programming team") ? ADMIN_USER : $owner;
        }
        // check all revisions forwards for the first author_id
        $backend = &$this->_wikidb->_backend;
        $pagename = &$this->_pagename;
        $latestversion = $backend->get_latest_version($pagename);
        for ($v = 1; $v <= $latestversion; $v++) {
            $rev = $this->getRevision($v, false);
            if ($rev and $owner = $rev->get('author_id')) {
                return ($owner == "The PhpWiki programming team") ? ADMIN_USER : $owner;
            }
        }
        return '';
    }

    // The authenticated author of the first revision or empty if not authenticated then.
    public function getCreator()
    {
        if ($current = $this->getRevision(1, false)) {
            return $current->get('author_id');
        }
        return '';
    }

    // The authenticated author of the current revision.
    public function getAuthor()
    {
        if ($current = $this->getCurrentRevision(false)) {
            return $current->get('author_id');
        }
        return '';
    }
}

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
    //var $_transformedContent = false; // set by WikiDB_Page::save()

    public function __construct(&$wikidb, $pagename, $version, $versiondata = false)
    {
        $this->_wikidb = &$wikidb;
        $this->_pagename = $pagename;
        $this->_version = $version;
        $this->_data = $versiondata ? $versiondata : array();
        $this->_transformedContent = false; // set by WikiDB_Page::save()
    }

    /**
     * Get the WikiDB_Page which this revision belongs to.
     *
     * @access public
     *
     * @return WikiDB_Page The WikiDB_Page which this revision belongs to.
     */
    public function getPage()
    {
        return new WikiDB_Page($this->_wikidb, $this->_pagename);
    }

    /**
     * Get the version number of this revision.
     *
     * @access public
     *
     * @return int The version number of this revision.
     */
    public function getVersion()
    {
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
     * @return bool Returns true if the page has default content.
     */
    public function hasDefaultContents()
    {
        $data = &$this->_data;
        return empty($data['%content']); // FIXME: what if it's the number 0? <>'' or === false
    }

    /**
     * Get the content as an array of lines.
     *
     * @access public
     *
     * @return array An array of lines.
     * The lines should contain no trailing white space.
     */
    public function getContent()
    {
        return explode("\n", $this->getPackedContent());
    }

   /**
     * Get the pagename of the revision.
     *
     * @access public
     *
     * @return string pagename.
     */
    public function getPageName()
    {
        return $this->_pagename;
    }
    public function getName()
    {
        return $this->_pagename;
    }

    /**
     * Determine whether revision is the latest.
     *
     * @access public
     *
     * @return bool True iff the revision is the latest (most recent) one.
     */
    public function isCurrent()
    {
        if (!isset($this->_iscurrent)) {
            $page = $this->getPage();
            $current = $page->getCurrentRevision(false);
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
    public function getTransformedContent($pagetype_override = false)
    {
        $backend = &$this->_wikidb->_backend;

        if ($pagetype_override) {
            // Figure out the normal page-type for this page.
            $type = PageType::GetPageType($this->get('pagetype'));
            if ($type->getName() == $pagetype_override) {
                $pagetype_override = false; // Not really an override...
            }
        }

        if ($pagetype_override) {
            // Overriden page type, don't cache (or check cache).
            return new TransformedText(
                $this->getPage(),
                $this->getPackedContent(),
                $this->getMetaData(),
                $pagetype_override
            );
        }

        $possibly_cache_results = true;

        if (!USECACHE or WIKIDB_NOCACHE_MARKUP) {
            if (WIKIDB_NOCACHE_MARKUP == 'purge') {
                // flush cache for this page.
                $page = $this->getPage();
                $page->set('_cached_html', ''); // ignored with !USECACHE
            }
            $possibly_cache_results = false;
        } elseif (USECACHE and !$this->_transformedContent) {
            //$backend->lock();
            if ($this->isCurrent()) {
                $page = $this->getPage();
                $this->_transformedContent = TransformedText::unpack($page->get('_cached_html'));
            } else {
                $possibly_cache_results = false;
            }
            //$backend->unlock();
        }

        if (!$this->_transformedContent) {
            $this->_transformedContent = new TransformedText(
                $this->getPage(),
                $this->getPackedContent(),
                $this->getMetaData()
            );

            if ($possibly_cache_results and !WIKIDB_NOCACHE_MARKUP) {
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
    public function getPackedContent()
    {
        $data = &$this->_data;

        if (empty($data['%content'])) {
            include_once('lib/InlineParser.php');

            // A feature similar to taglines at http://www.wlug.org.nz/
            // Lib from http://www.aasted.org/quote/
            if (defined('FORTUNE_DIR')
                and is_dir(FORTUNE_DIR)
                and in_array($GLOBALS['request']->getArg('action'), array('create','edit'))
            ) {
                include_once("lib/fortune.php");
                $fortune = new Fortune();
                $quote = str_replace("\n<br>", "\n", $fortune->quoteFromDir(FORTUNE_DIR));
                return sprintf(
                    "<verbatim>\n%s</verbatim>\n\n" . _("Describe %s here."),
                    $quote,
                    "[" . WikiEscape($this->_pagename) . "]"
                );
            }
            // Replace empty content with default value.
            return sprintf(_("Describe %s here."), "[" . WikiEscape($this->_pagename) . "]");
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

    public function _get_content()
    {
        $cache = &$this->_wikidb->_cache;
        $pagename = $this->_pagename;
        $version = $this->_version;

        assert($version > 0);

        $newdata = $cache->get_versiondata($pagename, $version, true);
        if ($newdata) {
            assert(is_string($newdata['%content']));
            return $newdata['%content'];
        } else {
            // else revision has been deleted... What to do?
            return PHPWikiSprintf("Oops! Revision %s of %s seems to have been deleted!", $version, $pagename);
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
    public function get($key)
    {
        if (! $key || $key[0] == '%') {
            return false;
        }
        $data = &$this->_data;
        return isset($data[$key]) ? $data[$key] : false;
    }

    /**
     * Get all the revision page meta-data as a hash.
     *
     * @return hash The revision meta-data.
     */
    public function getMetaData()
    {
        $meta = array();
        foreach ($this->_data as $key => $val) {
            if (! empty($val) && $key[0] != '%') {
                $meta[$key] = $val;
            }
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
    public function asString()
    {
        ob_start();
        printf("[%s:%d\n", static::class, $this->get('version'));
        print_r($this->_data);
        echo $this->getPackedContent() . "\n]\n";
        $strval = ob_get_contents();
        ob_end_clean();
        return $strval;
    }
}


/**
 * Class representing a sequence of WikiDB_Pages.
 * TODO: Enhance to php5 iterators
 * TODO:
 *   apply filters for options like 'sortby', 'limit', 'exclude'
 *   for simple queries like titleSearch, where the backend is not ready yet.
 */
class WikiDB_PageIterator
{
    public function __construct(&$wikidb, &$iter, $options = false)
    {
        $this->_iter = $iter; // a WikiDB_backend_iterator
        $this->_wikidb = &$wikidb;
        $this->_options = $options;
    }

    public function count()
    {
        return $this->_iter->count();
    }

    /**
     * Get next WikiDB_Page in sequence.
     *
     * @access public
     *
     * @return WikiDB_Page The next WikiDB_Page in the sequence.
     */
    public function next()
    {
        if (! ($next = $this->_iter->next())) {
            return false;
        }

        $pagename = &$next['pagename'];
        if (!is_string($pagename)) { // Bug #1327912 fixed by Joachim Lous
            $pagename = strval($pagename);
        }
        if (!$pagename) {
            trigger_error('empty pagename in WikiDB_PageIterator::next()', E_USER_WARNING);
            var_dump($next);
            return false;
        }
        // There's always hits, but we cache only if more
        // (well not with file, cvs and dba)
        if (isset($next['pagedata']) and count($next['pagedata']) > 1) {
            $this->_wikidb->_cache->cache_data($next);
        // cache existing page id's since we iterate over all links in GleanDescription
        // and need them later for LinkExistingWord
        } elseif ($this->_options and array_key_exists('include_empty', $this->_options)
                  and !$this->_options['include_empty'] and isset($next['id'])) {
            $this->_wikidb->_cache->_id_cache[$next['pagename']] = $next['id'];
        }
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
    public function free()
    {
        $this->_iter->free();
    }

    public function asArray()
    {
        $result = array();
        while ($page = $this->next()) {
            $result[] = $page;
        }
        //$this->reset();
        return $result;
    }

    /**
     * Apply filters for options like 'sortby', 'limit', 'exclude'
     * for simple queries like titleSearch, where the backend is not ready yet.
     * Since iteration is usually destructive for SQL results,
     * we have to generate a copy.
     */
    public function applyFilters($options = false)
    {
        if (!$options) {
            $options = $this->_options;
        }
        if (isset($options['sortby'])) {
            $array = array();
            /* this is destructive */
            while ($page = $this->next()) {
                $result[] = $page->getName();
            }
            $this->_doSort($array, $options['sortby']);
        }
        /* the rest is not destructive.
         * reconstruct a new iterator
         */
        $pagenames = array();
        $i = 0;
        if (isset($options['limit'])) {
            $limit = $options['limit'];
        } else {
            $limit = 0;
        }
        if (isset($options['exclude'])) {
            $exclude = $options['exclude'];
        }
        if (is_string($exclude) and !is_array($exclude)) {
            $exclude = PageList::explodePageList($exclude, false, false, $limit);
        }
        foreach ($array as $pagename) {
            if ($limit and $i++ > $limit) {
                return new WikiDB_Array_PageIterator($pagenames);
            }
            if (!empty($exclude) and !in_array($pagename, $exclude)) {
                $pagenames[] = $pagename;
            } elseif (empty($exclude)) {
                $pagenames[] = $pagename;
            }
        }
        return new WikiDB_Array_PageIterator($pagenames);
    }

    /* pagename only */
    public function _doSort(&$array, $sortby)
    {
        $sortby = PageList::sortby($sortby, 'init');
        if ($sortby == '+pagename') {
            sort($array, SORT_STRING);
        } elseif ($sortby == '-pagename') {
            rsort($array, SORT_STRING);
        }
        reset($array);
    }
}

/**
 * A class which represents a sequence of WikiDB_PageRevisions.
 * TODO: Enhance to php5 iterators
 */
class WikiDB_PageRevisionIterator
{
    public function __construct(&$wikidb, &$revisions, $options = false)
    {
        $this->_revisions = $revisions;
        $this->_wikidb = &$wikidb;
        $this->_options = $options;
    }

    public function count()
    {
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
    public function next()
    {
        if (! ($next = $this->_revisions->next())) {
            return false;
        }

        //$this->_wikidb->_cache->cache_data($next);

        $pagename = $next['pagename'];
        $version = $next['version'];
        $versiondata = $next['versiondata'];
        if (DEBUG) {
            if (!(is_string($pagename) and $pagename != '')) {
                trigger_error("empty pagename", E_USER_WARNING);
                return false;
            }
        } else {
            assert(is_string($pagename) and $pagename != '');
        }
        if (DEBUG) {
            if (!is_array($versiondata)) {
                trigger_error("empty versiondata", E_USER_WARNING);
                return false;
            }
        } else {
            assert(is_array($versiondata));
        }
        if (DEBUG) {
            if (!($version > 0)) {
                trigger_error("invalid version", E_USER_WARNING);
                return false;
            }
        } else {
            assert($version > 0);
        }

        return new WikiDB_PageRevision($this->_wikidb, $pagename, $version, $versiondata);
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
    public function free()
    {
        $this->_revisions->free();
    }

    public function asArray()
    {
        $result = array();
        while ($rev = $this->next()) {
            $result[] = $rev;
        }
        $this->free();
        return $result;
    }
}

/** pseudo iterator
 */
class WikiDB_Array_PageIterator
{
    public function __construct($pagenames)
    {
        global $request;
        $this->_dbi = $request->getDbh();
        $this->_pages = $pagenames;
        reset($this->_pages);
    }
    public function next()
    {
        $c = current($this->_pages);
        next($this->_pages);
        return $c !== false ? $this->_dbi->getPage($c) : false;
    }
    public function count()
    {
        return count($this->_pages);
    }
    public function free()
    {
    }
    public function asArray()
    {
        reset($this->_pages);
        return $this->_pages;
    }
}

class WikiDB_Array_generic_iter
{
    public function __construct($result)
    {
        // $result may be either an array or a query result
        if (is_array($result)) {
            $this->_array = $result;
        } elseif (is_object($result)) {
            $this->_array = $result->asArray();
        } else {
            $this->_array = array();
        }
        if (! empty($this->_array)) {
            reset($this->_array);
        }
    }
    public function next()
    {
        $c = current($this->_array);
        next($this->_array);
        return $c !== false ? $c : false;
    }
    public function count()
    {
        return count($this->_array);
    }
    public function free()
    {
    }
    public function asArray()
    {
        if (!empty($this->_array)) {
            reset($this->_array);
        }
        return $this->_array;
    }
}

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

    public function __construct(&$backend)
    {
        $this->_backend = &$backend;

        $this->_pagedata_cache = array();
        $this->_versiondata_cache = array();
        array_push($this->_versiondata_cache, array());
        $this->_glv_cache = array();
        $this->_id_cache = array(); // formerly ->_dbi->_iwpcache (nonempty pages => id)
    }

    public function close()
    {
        $this->_pagedata_cache = array();
        $this->_versiondata_cache = array();
        $this->_glv_cache = array();
        $this->_id_cache = array();
    }

    public function get_pagedata($pagename)
    {
        assert(is_string($pagename) && $pagename != '');
        if (USECACHE) {
            $cache = &$this->_pagedata_cache;
            if (!isset($cache[$pagename]) || !is_array($cache[$pagename])) {
                $cache[$pagename] = $this->_backend->get_pagedata($pagename);
                if (empty($cache[$pagename])) {
                    $cache[$pagename] = array();
                }
            }
            return $cache[$pagename];
        } else {
            return $this->_backend->get_pagedata($pagename);
        }
    }

    public function update_pagedata($pagename, $newdata)
    {
        assert(is_string($pagename) && $pagename != '');

        $this->_backend->update_pagedata($pagename, $newdata);

        if (USECACHE) {
            if (! empty($this->_pagedata_cache[$pagename]) && is_array($this->_pagedata_cache[$pagename])) {
                $cachedata = &$this->_pagedata_cache[$pagename];
                foreach ($newdata as $key => $val) {
                    $cachedata[$key] = $val;
                }
            } else {
                $this->_pagedata_cache[$pagename] = $newdata;
            }
        }
    }

    public function invalidate_cache($pagename)
    {
        unset($this->_pagedata_cache[$pagename]);
        unset($this->_versiondata_cache[$pagename]);
        unset($this->_glv_cache[$pagename]);
        unset($this->_id_cache[$pagename]);
        //unset ($this->_backend->_page_data);
    }

    public function delete_page($pagename)
    {
        $result = $this->_backend->delete_page($pagename);
        $this->invalidate_cache($pagename);
        return $result;
    }

    public function purge_page($pagename)
    {
        $result = $this->_backend->purge_page($pagename);
        $this->invalidate_cache($pagename);
        return $result;
    }

    // FIXME: ugly and wrong. may overwrite full cache with partial cache
    public function cache_data($data)
    {
        //if (isset($data['pagedata']))
        //    $this->_pagedata_cache[$data['pagename']] = $data['pagedata'];
    }

    public function get_versiondata($pagename, $version, $need_content = false)
    {
        //  FIXME: Seriously ugly hackage
        $readdata = false;
        if (USECACHE) {   //temporary - for debugging
            assert(is_string($pagename) && $pagename != '');
            // There is a bug here somewhere which results in an assertion failure at line 105
            // of ArchiveCleaner.php  It goes away if we use the next line.
            //$need_content = true;
            $nc = $need_content ? '1' : '0';
            $cache = &$this->_versiondata_cache;
            if (! isset($cache[$pagename][$version][$nc])
                || !(is_array($cache[$pagename]))
                || !(is_array($cache[$pagename][$version]))
            ) {
                       $cache[$pagename][$version][$nc] =
                       $this->_backend->get_versiondata($pagename, $version, $need_content);
                       $readdata = true;
                // If we have retrieved all data, we may as well set the cache for
                // $need_content = false
                if ($need_content) {
                    $cache[$pagename][$version]['0'] = $cache[$pagename][$version]['1'];
                }
            }
            $vdata = $cache[$pagename][$version][$nc];
        } else {
            $vdata = $this->_backend->get_versiondata($pagename, $version, $need_content);
            $readdata = true;
        }
        if ($readdata && $vdata && !empty($vdata['%pagedata'])) {
            $this->_pagedata_cache[$pagename] = $vdata['%pagedata'];
        }
        return $vdata;
    }

    public function set_versiondata($pagename, $version, $data)
    {
        //unset($this->_versiondata_cache[$pagename][$version]);

        $new = $this->_backend->set_versiondata($pagename, $version, $data);
        // Update the cache
        $this->_versiondata_cache[$pagename][$version]['1'] = $data;
        $this->_versiondata_cache[$pagename][$version]['0'] = $data;
        // Is this necessary?
        unset($this->_glv_cache[$pagename]);
    }

    public function update_versiondata($pagename, $version, $data)
    {
        $new = $this->_backend->update_versiondata($pagename, $version, $data);
        // Update the cache
        $this->_versiondata_cache[$pagename][$version]['1'] = $data;
        // FIXME: hack
        $this->_versiondata_cache[$pagename][$version]['0'] = $data;
        // Is this necessary?
        unset($this->_glv_cache[$pagename]);
    }

    public function delete_versiondata($pagename, $version)
    {
        $new = $this->_backend->delete_versiondata($pagename, $version);
        if (isset($this->_versiondata_cache[$pagename][$version])) {
            unset($this->_versiondata_cache[$pagename][$version]);
        }
        // dirty latest version cache only if latest version gets deleted
        if (isset($this->_glv_cache[$pagename]) and $this->_glv_cache[$pagename] == $version) {
            unset($this->_glv_cache[$pagename]);
        }
    }

    public function get_latest_version($pagename)
    {
        if (USECACHE) {
            assert(is_string($pagename) && $pagename != '');
            $cache = &$this->_glv_cache;
            if (!isset($cache[$pagename])) {
                $cache[$pagename] = $this->_backend->get_latest_version($pagename);
                if (empty($cache[$pagename])) {
                    $cache[$pagename] = 0;
                }
            }
            return $cache[$pagename];
        } else {
            return $this->_backend->get_latest_version($pagename);
        }
    }
}

function _sql_debuglog($msg, $newline = true, $shutdown = false)
{
    static $fp = false;
    static $i = 0;
    if (!$fp) {
        $stamp = strftime("%y%m%d-%H%M%S");
        $fp = fopen("/tmp/sql-$stamp.log", "a");
        register_shutdown_function("_sql_debuglog_shutdown_function");
    } elseif ($shutdown) {
        fclose($fp);
        return;
    }
    if ($newline) {
        fputs($fp, "[$i++] $msg");
    } else {
        fwrite($fp, $msg);
    }
}

function _sql_debuglog_shutdown_function()
{
    _sql_debuglog('', false, true);
}

// $Log: WikiDB.php,v $
// fix bug #1327912 numeric pagenames can break plugins (Joachim Lous)
// pass stoplist through iterator
//
// Revision 1.137  2005/10/12 06:16:18  rurban
// better From header
//
// Revision 1.136  2005/10/03 16:14:57  rurban
// improve description
//
// Revision 1.135  2005/09/11 14:19:44  rurban
// enable LIMIT support for fulltext search
//
// Revision 1.134  2005/09/10 21:28:10  rurban
// applyFilters hack to use filters after methods, which do not support them (titleSearch)
//
// Revision 1.133  2005/08/27 09:39:10  rurban
// dumphtml when not at admin page: dump the current or given page
//
// Revision 1.132  2005/08/07 10:10:07  rurban
// clean whole version cache
//
// Revision 1.131  2005/04/23 11:30:12  rurban
// allow emtpy WikiDB::getRevisionBefore(), for simplier templates (revert)
//
// Revision 1.130  2005/04/06 06:19:30  rurban
// Revert the previous wrong bugfix #1175761: USECACHE was mixed with WIKIDB_NOCACHE_MARKUP.
// Fix WIKIDB_NOCACHE_MARKUP in main (always set it) and clarify it in WikiDB
//
// Revision 1.129  2005/04/06 05:50:29  rurban
// honor !USECACHE for _cached_html, fixes #1175761
//
// Revision 1.128  2005/04/01 16:11:42  rurban
// just whitespace
//
// Revision 1.127  2005/02/18 20:43:40  uckelman
// WikiDB::genericWarnings() is no longer used.
//
// Revision 1.126  2005/02/04 17:58:06  rurban
// minor versioncache improvement. part 2/3 of Charles Corrigan cache patch. not sure about the 0/1 issue
//
// Revision 1.125  2005/02/03 05:08:39  rurban
// ref fix by Charles Corrigan
//
// Revision 1.124  2005/01/29 20:43:32  rurban
// protect against empty request: on some occasion this happens
//
// Revision 1.123  2005/01/25 06:58:21  rurban
// reformatting
//
// Revision 1.122  2005/01/20 10:18:17  rurban
// reformatting
//
// Revision 1.121  2005/01/04 20:25:01  rurban
// remove old [%pagedata][_cached_html] code
//
// Revision 1.120  2004/12/23 14:12:31  rurban
// dont email on unittest
//
// Revision 1.119  2004/12/20 16:05:00  rurban
// gettext msg unification
//
// Revision 1.118  2004/12/13 13:22:57  rurban
// new BlogArchives plugin for the new blog theme. enable default box method
// for all plugins. Minor search improvement.
//
// Revision 1.117  2004/12/13 08:15:09  rurban
// false is wrong. null might be better but lets play safe.
//
// Revision 1.116  2004/12/10 22:15:00  rurban
// fix $page->get('_cached_html)
// refactor upgrade db helper _convert_cached_html() to be able to call them from WikiAdminUtils also.
// support 2nd genericSqlQuery param (bind huge arg)
//
// Revision 1.115  2004/12/10 02:45:27  rurban
// SQL optimization:
//   put _cached_html from pagedata into a new seperate blob, not huge serialized string.
//   it is only rarelely needed: for current page only, if-not-modified
//   but was extracted for every simple page iteration.
//
// Revision 1.114  2004/12/09 22:24:44  rurban
// optimize on _DEBUG_SQL only. but now again on every 50th request, not just save.
//
// Revision 1.113  2004/12/06 19:49:55  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.112  2004/11/30 17:45:53  rurban
// exists_links backend implementation
//
// Revision 1.111  2004/11/28 20:39:43  rurban
// deactivate pagecache overwrite: it is wrong
//
// Revision 1.110  2004/11/26 18:39:01  rurban
// new regex search parser and SQL backends (90% complete, glob and pcre backends missing)
//
// Revision 1.109  2004/11/25 17:20:50  rurban
// and again a couple of more native db args: backlinks
//
// Revision 1.108  2004/11/23 13:35:31  rurban
// add case_exact search
//
// Revision 1.107  2004/11/21 11:59:16  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.106  2004/11/20 17:35:56  rurban
// improved WantedPages SQL backends
// PageList::sortby new 3rd arg valid_fields (override db fields)
// WantedPages sql pager inexact for performance reasons:
//   assume 3 wantedfrom per page, to be correct, no getTotal()
// support exclude argument for get_all_pages, new _sql_set()
//
// Revision 1.105  2004/11/20 09:16:27  rurban
// Fix bad-style Cut&Paste programming errors, detected by Charles Corrigan.
//
// Revision 1.104  2004/11/19 19:22:03  rurban
// ModeratePage part1: change status
//
// Revision 1.103  2004/11/16 17:29:04  rurban
// fix remove notification error
// fix creation + update id_cache update
//
// Revision 1.102  2004/11/11 18:31:26  rurban
// add simple backtrace on such general failures to get at least an idea where
//
// Revision 1.101  2004/11/10 19:32:22  rurban
// * optimize increaseHitCount, esp. for mysql.
// * prepend dirs to the include_path (phpwiki_dir for faster searches)
// * Pear_DB version logic (awful but needed)
// * fix broken ADODB quote
// * _extract_page_data simplification
//
// Revision 1.100  2004/11/10 15:29:20  rurban
// * requires newer Pear_DB (as the internal one): quote() uses now escapeSimple for strings
// * ACCESS_LOG_SQL: fix cause request not yet initialized
// * WikiDB: moved SQL specific methods upwards
// * new Pear_DB quoting: same as ADODB and as newer Pear_DB.
//   fixes all around: WikiGroup, WikiUserNew SQL methods, SQL logging
//
// Revision 1.99  2004/11/09 17:11:05  rurban
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
// Revision 1.98  2004/11/07 18:34:29  rurban
// more logging fixes
//
// Revision 1.97  2004/11/07 16:02:51  rurban
// new sql access log (for spam prevention), and restructured access log class
// dbh->quote (generic)
// pear_db: mysql specific parts seperated (using replace)
//
// Revision 1.96  2004/11/05 22:32:15  rurban
// encode the subject to be 7-bit safe
//
// Revision 1.95  2004/11/05 20:53:35  rurban
// login cleanup: better debug msg on failing login,
// checked password less immediate login (bogo or anon),
// checked olduser pref session error,
// better PersonalPage without password warning on minimal password length=0
//   (which is default now)
//
// Revision 1.94  2004/11/01 10:43:56  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.93  2004/10/14 17:17:57  rurban
// remove dbi WikiDB_Page param: use global request object instead. (memory)
// allow most_popular sortby arguments
//
// Revision 1.92  2004/10/05 17:00:04  rurban
// support paging for simple lists
// fix RatingDb sql backend.
// remove pages from AllPages (this is ListPages then)
//
// Revision 1.91  2004/10/04 23:41:19  rurban
// delete notify: fix, @unset syntax error
//
// Revision 1.90  2004/09/28 12:50:22  rurban
// https://sourceforge.net/forum/forum.php?thread_id=1150924&forum_id=18929
//
// Revision 1.89  2004/09/26 10:54:42  rurban
// silence deferred check
//
// Revision 1.88  2004/09/25 18:16:40  rurban
// unset more unneeded _cached_html. (Guess this should fix sf.net now)
//
// Revision 1.87  2004/09/25 16:25:40  rurban
// notify on rename and remove (to be improved)
//
// Revision 1.86  2004/09/23 18:52:06  rurban
// only fortune at create
//
// Revision 1.85  2004/09/16 08:00:51  rurban
// just some comments
//
// Revision 1.84  2004/09/14 10:34:30  rurban
// fix TransformedText call to use refs
//
// Revision 1.83  2004/09/08 13:38:00  rurban
// improve loadfile stability by using markup=2 as default for undefined markup-style.
// use more refs for huge objects.
// fix debug=static issue in WikiPluginCached
//
// Revision 1.82  2004/09/06 12:08:49  rurban
// memory_limit on unix workaround
// VisualWiki: default autosize image
//
// Revision 1.81  2004/09/06 08:28:00  rurban
// rename genericQuery to genericSqlQuery
//
// Revision 1.80  2004/07/09 13:05:34  rurban
// just aesthetics
//
// Revision 1.79  2004/07/09 10:06:49  rurban
// Use backend specific sortby and sortable_columns method, to be able to
// select between native (Db backend) and custom (PageList) sorting.
// Fixed PageList::AddPageList (missed the first)
// Added the author/creator.. name to AllPagesBy...
//   display no pages if none matched.
// Improved dba and file sortby().
// Use &$request reference
//
// Revision 1.78  2004/07/08 21:32:35  rurban
// Prevent from more warnings, minor db and sort optimizations
//
// Revision 1.77  2004/07/08 19:04:42  rurban
// more unittest fixes (file backend, metadata RatingsDb)
//
// Revision 1.76  2004/07/08 17:31:43  rurban
// improve numPages for file (fixing AllPagesTest)
//
// Revision 1.75  2004/07/05 13:56:22  rurban
// sqlite autoincrement fix
//
// Revision 1.74  2004/07/03 16:51:05  rurban
// optional DBADMIN_USER:DBADMIN_PASSWD for action=upgrade (if no ALTER permission)
// added atomic mysql REPLACE for PearDB as in ADODB
// fixed _lock_tables typo links => link
// fixes unserialize ADODB bug in line 180
//
// Revision 1.73  2004/06/29 08:52:22  rurban
// Use ...version() $need_content argument in WikiDB also:
// To reduce the memory footprint for larger sets of pagelists,
// we don't cache the content (only true or false) and
// we purge the pagedata (_cached_html) also.
// _cached_html is only cached for the current pagename.
// => Vastly improved page existance check, ACL check, ...
//
// Now only PagedList info=content or size needs the whole content, esp. if sortable.
//
// Revision 1.72  2004/06/25 14:15:08  rurban
// reduce memory footprint by caching only requested pagedate content (improving most page iterators)
//
// Revision 1.71  2004/06/21 16:22:30  rurban
// add DEFAULT_DUMP_DIR and HTML_DUMP_DIR constants, for easier cmdline dumps,
// fixed dumping buttons locally (images/buttons/),
// support pages arg for dumphtml,
// optional directory arg for dumpserial + dumphtml,
// fix a AllPages warning,
// show dump warnings/errors on DEBUG,
// don't warn just ignore on wikilens pagelist columns, if not loaded.
// RateIt pagelist column is called "rating", not "ratingwidget" (Dan?)
//
// Revision 1.70  2004/06/18 14:39:31  rurban
// actually check USECACHE
//
// Revision 1.69  2004/06/13 15:33:20  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.68  2004/06/08 21:03:20  rurban
// updated RssParser for XmlParser quirks (store parser object params in globals)
//
// Revision 1.67  2004/06/07 19:12:49  rurban
// fixed rename version=0, bug #966284
//
// Revision 1.66  2004/06/07 18:57:27  rurban
// fix rename: Change pagename in all linked pages
//
// Revision 1.65  2004/06/04 20:32:53  rurban
// Several locale related improvements suggested by Pierrick Meignen
// LDAP fix by John Cole
// reanable admin check without ENABLE_PAGEPERM in the admin plugins
//
// Revision 1.64  2004/06/04 16:50:00  rurban
// add random quotes to empty pages
//
// Revision 1.63  2004/06/04 11:58:38  rurban
// added USE_TAGLINES
//
// Revision 1.62  2004/06/03 22:24:41  rurban
// reenable admin check on !ENABLE_PAGEPERM, honor s=Wildcard arg, fix warning after Remove
//
// Revision 1.61  2004/06/02 17:13:48  rurban
// fix getRevisionBefore assertion
//
// Revision 1.60  2004/05/28 10:09:58  rurban
// fix bug #962117, incorrect init of auth_dsn
//
// Revision 1.59  2004/05/27 17:49:05  rurban
// renamed DB_Session to DbSession (in CVS also)
// added WikiDB->getParam and WikiDB->getAuthParam method to get rid of globals
// remove leading slash in error message
// added force_unlock parameter to File_Passwd (no return on stale locks)
// fixed adodb session AffectedRows
// added FileFinder helpers to unify local filenames and DATA_PATH names
// editpage.php: new edit toolbar javascript on ENABLE_EDIT_TOOLBAR
//
// Revision 1.58  2004/05/18 13:59:14  rurban
// rename simpleQuery to genericQuery
//
// Revision 1.57  2004/05/16 22:07:35  rurban
// check more config-default and predefined constants
// various PagePerm fixes:
//   fix default PagePerms, esp. edit and view for Bogo and Password users
//   implemented Creator and Owner
//   BOGOUSERS renamed to BOGOUSER
// fixed syntax errors in signin.tmpl
//
// Revision 1.56  2004/05/15 22:54:49  rurban
// fixed important WikiDB bug with DEBUG > 0: wrong assertion
// improved SetAcl (works) and PagePerms, some WikiGroup helpers.
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
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
