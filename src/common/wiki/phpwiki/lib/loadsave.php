<?php
/*
 Copyright 1999,2000,2001,2002,2004,2005 $ThePhpWikiProgrammingTeam

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

use Tuleap\PHPWiki\WikiPage;

require_once("lib/ziplib.php");
require_once("lib/Template.php");

/**
 * ignore fatal errors during dump
 */
function _dump_error_handler(&$error)
{
    if ($error->isFatal()) {
        $error->errno = E_USER_WARNING;
        return true;
    }
    return true;         // Ignore error
    /*
    if (preg_match('/Plugin/', $error->errstr))
        return true;
    */
    // let the message come through: call the remaining handlers:
    // return false;
}

function StartLoadDump(&$request, $title, $html = '')
{
    // MockRequest is from the unit testsuite, a faked request. (may be cmd-line)
    // We are silent on unittests.
    if (isa($request, 'MockRequest')) {
        return;
    }
    // FIXME: This is a hack. This really is the worst overall hack in phpwiki.
    if ($html) {
        $html->pushContent('%BODY%');
    }
    $tmpl = Template('html', array('TITLE' => $title,
                                   'HEADER' => $title,
                                   'CONTENT' => $html ? $html : '%BODY%'));
    echo preg_replace('/%BODY%.*/D', '', $tmpl->getExpansion($html));
    $request->chunkOutput();

    // set marker for sendPageChangeNotification()
    $request->_deferredPageChangeNotification = array();
}

function EndLoadDump(&$request)
{
    if (isa($request, 'MockRequest')) {
        return;
    }
    $action = $request->getArg('action');
    $label = '';
    switch ($action) {
        case 'zip':
            $label = _("ZIP files of database");
            break;
        case 'upload':
            $label = _("Upload File");
            break;
        case 'loadfile':
            $label = _("Load File");
            break;
        case 'upgrade':
            $label = _("Upgrade");
            break;
        case 'ziphtml':
            $label = _("Dump pages as XHTML");
            break;
    }
    if ($label) {
        $label = str_replace(" ", "_", $label);
    }
    if ($action == 'browse') { // loading virgin
        $pagelink = WikiLink(HOME_PAGE);
    } else {
        $pagelink = WikiLink(new WikiPageName(_("PhpWikiAdministration"), false, $label));
    }

    // do deferred sendPageChangeNotification()
    if (!empty($request->_deferredPageChangeNotification)) {
        $pages = $all_emails = $all_users = array();
        foreach ($request->_deferredPageChangeNotification as $p) {
            list($pagename, $emails, $userids) = $p;
            $pages[] = $pagename;
            $all_emails = array_unique(array_merge($all_emails, $emails));
            $all_users = array_unique(array_merge($all_users, $userids));
        }
        $editedby = sprintf(_("Edited by: %s"), $request->_user->getId());
        $content = "Loaded the following pages:\n" . join("\n", $pages);
        if (mail(
            join(',', $all_emails),
            "[" . WIKI_NAME . "] " . _("LoadDump"),
            _("LoadDump") . "\n" .
                 $editedby . "\n\n" .
            $content
        )) {
            trigger_error(sprintf(
                _("PageChange Notification of %s sent to %s"),
                join("\n", $pages),
                join(',', $all_users)
            ), E_USER_NOTICE);
        } else {
            trigger_error(sprintf(
                _("PageChange Notification Error: Couldn't send %s to %s"),
                join("\n", $pages),
                join(',', $all_users)
            ), E_USER_WARNING);
        }
        unset($pages);
        unset($all_emails);
        unset($all_users);
    }
    unset($request->_deferredPageChangeNotification);

    PrintXML(
        HTML::p(HTML::strong(_("Complete."))),
        HTML::p(fmt("Return to %s", $pagelink))
    );
    echo "</body></html>\n";
}


////////////////////////////////////////////////////////////////
//
//  Functions for dumping.
//
////////////////////////////////////////////////////////////////

/**
 * For reference see:
 * http://www.nacs.uci.edu/indiv/ehood/MIME/2045/rfc2045.html
 * http://www.faqs.org/rfcs/rfc2045.html
 * (RFC 1521 has been superceeded by RFC 2045 & others).
 *
 * Also see http://www.faqs.org/rfcs/rfc2822.html
 */
function MailifyPage($page, $nversions = 1)
{
    $current = $page->getCurrentRevision();
    $head = '';

    if (STRICT_MAILABLE_PAGEDUMPS) {
        $from = defined('SERVER_ADMIN') ? SERVER_ADMIN : 'foo@bar';
        //This is for unix mailbox format: (not RFC (2)822)
        // $head .= "From $from  " . CTime(time()) . "\r\n";
        $head .= "Subject: " . rawurlencode($page->getName()) . "\r\n";
        $head .= "From: $from (PhpWiki)\r\n";
        // RFC 2822 requires only a Date: and originator (From:)
        // field, however the obsolete standard RFC 822 also
        // requires a destination field.
        $head .= "To: $from (PhpWiki)\r\n";
    }
    $head .= "Date: " . Rfc2822DateTime($current->get('mtime')) . "\r\n";
    $head .= sprintf(
        "Mime-Version: 1.0 (Produced by PhpWiki %s)\r\n",
        PHPWIKI_VERSION
    );

    // This should just be entered by hand (or by script?)
    // in the actual pgsrc files, since only they should have
    // RCS ids.
    //$head .= "X-Rcs-Id: \$Id\$\r\n";

    $iter = $page->getAllRevisions();
    $parts = array();
    while ($revision = $iter->next()) {
        $parts[] = MimeifyPageRevision($page, $revision);
        if ($nversions > 0 && count($parts) >= $nversions) {
            break;
        }
    }
    if (count($parts) > 1) {
        return $head . MimeMultipart($parts);
    }
    assert($parts);
    return $head . $parts[0];
}

/***
 * Compute filename to used for storing contents of a wiki page.
 *
 * Basically we do a rawurlencode() which encodes everything except
 * ASCII alphanumerics and '.', '-', and '_'.
 *
 * But we also want to encode leading dots to avoid filenames like
 * '.', and '..'. (Also, there's no point in generating "hidden" file
 * names, like '.foo'.)
 *
 * @param $pagename string Pagename.
 * @return string Filename for page.
 */
function FilenameForPage($pagename)
{
    $enc = rawurlencode($pagename);
    return preg_replace('/^\./', '%2e', $enc);
}

/**
 * The main() function which generates a zip archive of a PhpWiki.
 *
 * If $include_archive is false, only the current version of each page
 * is included in the zip file; otherwise all archived versions are
 * included as well.
 */
function MakeWikiZip(&$request)
{
    if ($request->getArg('include') == 'all') {
        $zipname         = WIKI_NAME . _("FullDump") . date('Ymd-Hi') . '.zip';
        $include_archive = true;
    } else {
        $zipname         = WIKI_NAME . _("LatestSnapshot") . date('Ymd-Hi') . '.zip';
        $include_archive = false;
    }

    $zip = new ZipWriter("Created by PhpWiki " . PHPWIKI_VERSION, $zipname);

    /* ignore fatals in plugins */
    global $ErrorManager;
    $ErrorManager->pushErrorHandler(new WikiFunctionCb('_dump_error_handler'));

    $dbi = $request->_dbi;
    $thispage = $request->getArg('pagename'); // for "Return to ..."
    if ($exclude = $request->getArg('exclude')) {   // exclude which pagenames
        $excludeList = explodePageList($exclude);
    } else {
        $excludeList = array();
    }
    if ($pages = $request->getArg('pages')) {  // which pagenames
        if ($pages == '[]') { // current page
            $pages = $thispage;
        }
        $page_iter = new WikiDB_Array_PageIterator(explodePageList($pages));
    } else {
        $page_iter = $dbi->getAllPages(false, false, false, $excludeList);
    }
    $request_args = $request->args;
    $timeout = (! $request->getArg('start_debug')) ? 30 : 240;

    while ($page = $page_iter->next()) {
        $request->args = $request_args; // some plugins might change them (esp. on POST)
        longer_timeout($timeout);     // Reset watchdog

        $current = $page->getCurrentRevision();
        if ($current->getVersion() == 0) {
            continue;
        }

        $pagename = $page->getName();
        $wpn = new WikiPageName($pagename);
        if (!$wpn->isValid()) {
            continue;
        }
        if (in_array($page->getName(), $excludeList)) {
            continue;
        }

        $attrib = array('mtime'    => $current->get('mtime'),
                        'is_ascii' => 1);
        if ($page->get('locked')) {
            $attrib['write_protected'] = 1;
        }

        if ($include_archive) {
            $content = MailifyPage($page, 0);
        } else {
            $content = MailifyPage($page);
        }

        $zip->addRegularFile(
            FilenameForPage($pagename),
            $content,
            $attrib
        );
    }
    $zip->finish();
    global $ErrorManager;
    $ErrorManager->popErrorHandler();
}

function _copyMsg($page, $smallmsg)
{
    if (!isa($GLOBALS['request'], 'MockRequest')) {
        if ($page) {
            $msg = HTML(HTML::br(), HTML($page), HTML::small($smallmsg));
        } else {
            $msg = HTML::small($smallmsg);
        }
        PrintXML($msg);
        flush();
    }
}

/* Known problem: any plugins or other code which echo()s text will
 * lead to a corrupted html zip file which may produce the following
 * errors upon unzipping:
 *
 * warning [wikihtml.zip]:  2401 extra bytes at beginning or within zipfile
 * file #58:  bad zipfile offset (local header sig):  177561
 *  (attempting to re-compensate)
 *
 * However, the actual wiki page data should be unaffected.
 */
function MakeWikiZipHtml(&$request)
{
    $request->_TemplatesProcessed = array();
    $zipname = "wikihtml.zip";
    $zip = new ZipWriter("Created by PhpWiki " . PHPWIKI_VERSION, $zipname);
    $dbi = $request->_dbi;
    $thispage = $request->getArg('pagename'); // for "Return to ..."
    if ($exclude = $request->getArg('exclude')) {   // exclude which pagenames
        $excludeList = explodePageList($exclude);
    } else {
        $excludeList = array();
    }
    if ($pages = $request->getArg('pages')) {  // which pagenames
        if ($pages == '[]') { // current page
            $pages = $thispage;
        }
        $page_iter = new WikiDB_Array_PageIterator(explodePageList($pages));
    } else {
        $page_iter = $dbi->getAllPages(false, false, false, $excludeList);
    }

    global $WikiTheme;
    if (defined('HTML_DUMP_SUFFIX')) {
        $WikiTheme->HTML_DUMP_SUFFIX = HTML_DUMP_SUFFIX;
    }
    $WikiTheme->DUMP_MODE = 'ZIPHTML';
    $_bodyAttr = @$WikiTheme->_MoreAttr['body'];
    unset($WikiTheme->_MoreAttr['body']);

    /* ignore fatals in plugins */
    global $ErrorManager;
    $ErrorManager->pushErrorHandler(new WikiFunctionCb('_dump_error_handler'));

    $request_args = $request->args;
    $timeout = (! $request->getArg('start_debug')) ? 20 : 240;

    while ($page = $page_iter->next()) {
        $request->args = $request_args; // some plugins might change them (esp. on POST)
        longer_timeout($timeout);     // Reset watchdog

        $current = $page->getCurrentRevision();
        if ($current->getVersion() == 0) {
            continue;
        }
        $pagename = $page->getName();
        if (in_array($pagename, $excludeList)) {
            continue;
        }

        $attrib = array('mtime'    => $current->get('mtime'),
                        'is_ascii' => 1);
        if ($page->get('locked')) {
            $attrib['write_protected'] = 1;
        }

        $request->setArg('pagename', $pagename); // Template::_basepage fix
        $filename = FilenameForPage($pagename) . $WikiTheme->HTML_DUMP_SUFFIX;
        $revision = $page->getCurrentRevision();

        $transformedContent = $revision->getTransformedContent();

        $template = new Template(
            'browse',
            $request,
            array('revision' => $revision,
            'CONTENT' => $transformedContent)
        );

        $data = GeneratePageasXML($template, $pagename);

        $zip->addRegularFile($filename, $data, $attrib);

        if (USECACHE) {
            $request->_dbi->_cache->invalidate_cache($pagename);
            unset($request->_dbi->_cache->_pagedata_cache);
            unset($request->_dbi->_cache->_versiondata_cache);
            unset($request->_dbi->_cache->_glv_cache);
        }
        unset($request->_dbi->_cache->_backend->_page_data);

        unset($revision->_transformedContent);
        unset($revision);
        unset($template->_request);
        unset($template);
        unset($data);
    }
    $page_iter->free();

    $attrib = false;
    // Deal with css and images here.
    if (!empty($WikiTheme->dumped_images) and is_array($WikiTheme->dumped_images)) {
        // dirs are created automatically
        //if ($WikiTheme->dumped_images) $zip->addRegularFile("images", "", $attrib);
        foreach ($WikiTheme->dumped_images as $img_file) {
            if (($from = $WikiTheme->_findFile($img_file, true)) and basename($from)) {
                $target = "images/" . basename($img_file);
                $zip->addRegularFile($target, file_get_contents($WikiTheme->_path . $from), $attrib);
            }
        }
    }
    if (!empty($WikiTheme->dumped_buttons) and is_array($WikiTheme->dumped_buttons)) {
        //if ($WikiTheme->dumped_buttons) $zip->addRegularFile("images/buttons", "", $attrib);
        foreach ($WikiTheme->dumped_buttons as $text => $img_file) {
            if (($from = $WikiTheme->_findFile($img_file, true)) and basename($from)) {
                $target = "images/buttons/" . basename($img_file);
                $zip->addRegularFile($target, file_get_contents($WikiTheme->_path . $from), $attrib);
            }
        }
    }
    if (!empty($WikiTheme->dumped_css) and is_array($WikiTheme->dumped_css)) {
        foreach ($WikiTheme->dumped_css as $css_file) {
            if (($from = $WikiTheme->_findFile(basename($css_file), true)) and basename($from)) {
                $target = basename($css_file);
                $zip->addRegularFile($target, file_get_contents($WikiTheme->_path . $from), $attrib);
            }
        }
    }

    $zip->finish();
    global $ErrorManager;
    $ErrorManager->popErrorHandler();
    $WikiTheme->HTML_DUMP_SUFFIX = '';
    $WikiTheme->DUMP_MODE = false;
    $WikiTheme->_MoreAttr['body'] = $_bodyAttr;
}


////////////////////////////////////////////////////////////////
//
//  Functions for restoring.
//
////////////////////////////////////////////////////////////////

function SavePage(&$request, &$pageinfo, $source, $filename)
{
    static $overwite_all = false;
    $pagedata    = $pageinfo['pagedata'];    // Page level meta-data.
    $versiondata = $pageinfo['versiondata']; // Revision level meta-data.

    if (empty($pageinfo['pagename'])) {
        PrintXML(HTML::dt(HTML::strong(_("Empty pagename!"))));
        return;
    }

    if (empty($versiondata['author_id'])) {
        $versiondata['author_id'] = $versiondata['author'];
    }

    $pagename = $pageinfo['pagename'];
    $content  = $pageinfo['content'];

    if ($pagename == _("InterWikiMap")) {
        $content = _tryinsertInterWikiMap($content);
    }

    $dbi = $request->_dbi;
    $page = $dbi->getPage($pagename);

    // Try to merge if updated pgsrc contents are different. This
    // whole thing is hackish
    //
    // TODO: try merge unless:
    // if (current contents = default contents && pgsrc_version >=
    // pgsrc_version) then just upgrade this pgsrc
    $needs_merge = false;
    $merging = false;
    $overwrite = false;

    if ($request->getArg('merge')) {
        $merging = true;
    } elseif ($request->getArg('overwrite')) {
        $overwrite = true;
    }

    $current = $page->getCurrentRevision();
    if ($current and (! $current->hasDefaultContents())
         && ($current->getPackedContent() != $content)
         && ($merging == true)) {
        include_once('lib/editpage.php');
        $request->setArg('pagename', $pagename);
        $r = $current->getVersion();
        $request->setArg('revision', $current->getVersion());
        $p = new LoadFileConflictPageEditor($request);
        $p->_content = $content;
        $p->_currentVersion = $r - 1;
        $p->editPage($saveFailed = true);
        return; //early return
    }

    foreach ($pagedata as $key => $value) {
        if (!empty($value)) {
            $page->set($key, $value);
        }
    }

    $mesg = HTML::dd();
    $skip = false;
    if ($source) {
        $mesg->pushContent(' ', fmt("from %s", $source));
    }

    if (!$current) {
        //FIXME: This should not happen! (empty vdata, corrupt cache or db)
        $current = $page->getCurrentRevision();
    }
    if ($current->getVersion() == 0) {
        $mesg->pushContent(' - ', _("New page"));
        $isnew = true;
    } else {
        if ((! $current->hasDefaultContents())
             && ($current->getPackedContent() != $content)) {
            if ($overwrite) {
                $mesg->pushContent(
                    ' ',
                    fmt("has edit conflicts - overwriting anyway")
                );
                $skip = false;
                if (substr_count($source, 'pgsrc')) {
                    $versiondata['author'] = _("The PhpWiki programming team");
                    // but leave authorid as userid who loaded the file
                }
            } else {
                $mesg->pushContent(' ', fmt("has edit conflicts - skipped"));
                $needs_merge = true; // hackish
                $skip = true;
            }
        } elseif ($current->getPackedContent() == $content
                 && $current->get('author') == $versiondata['author']) {
            // The page metadata is already changed, we don't need a new revision.
            // This was called previously "is identical to current version %d - skipped"
            // which is wrong, since the pagedata was stored, not skipped.
            $mesg->pushContent(
                ' ',
                fmt(
                    "content is identical to current version %d - no new revision created",
                    $current->getVersion()
                )
            );
            $skip = true;
        }
        $isnew = false;
    }

    if (! $skip) {
        // in case of failures print the culprit:
        if (!isa($request, 'MockRequest')) {
            PrintXML(HTML::dt(WikiLink($pagename)));
            flush();
        }
        $new = $page->save($content, WIKIDB_FORCE_CREATE, $versiondata);
        $dbi->touch();
        $mesg->pushContent(' ', fmt(
            "- saved to database as version %d",
            $new->getVersion()
        ));
    }
    if ($needs_merge) {
        $f = $source;
        // hackish, $source contains needed path+filename
        $f = str_replace(sprintf(_("MIME file %s"), ''), '', $f);
        $f = str_replace(sprintf(_("Serialized file %s"), ''), '', $f);
        $f = str_replace(sprintf(_("plain file %s"), ''), '', $f);
        //check if uploaded file? they pass just the content, but the file is gone
        if (@stat($f)) {
            global $WikiTheme;
            $meb = Button(
                array('action' => 'loadfile',
                                'merge' => true,
                                'source' => $f),
                _("Merge Edit"),
                _("PhpWikiAdministration"),
                'wikiadmin'
            );
            $owb = Button(
                array('action' => 'loadfile',
                                'overwrite' => true,
                                'source' => $f),
                _("Restore Anyway"),
                _("PhpWikiAdministration"),
                'wikiunsafe'
            );
            $mesg->pushContent(' ', $meb, " ", $owb);
            if (!$overwite_all) {
                $args = $request->getArgs();
                $args['overwrite'] = 1;
                $owb = Button(
                    $args,
                    _("Overwrite All"),
                    _("PhpWikiAdministration"),
                    'wikiunsafe'
                );
                $mesg->pushContent(HTML::div(array('class' => 'hint'), $owb));
                $overwite_all = true;
            }
        } else {
            $mesg->pushContent(HTML::em(_(" Sorry, cannot merge.")));
        }
    }

    if (!isa($request, 'MockRequest')) {
        if ($skip) {
            PrintXML(HTML::dt(HTML::em(WikiLink($pagename))), $mesg);
        } else {
            PrintXML($mesg);
        }
        flush();
    }
}

// action=revert (by diff)
function RevertPage(&$request)
{
    $mesg = HTML::dd();
    $pagename = $request->getArg('pagename');
    $version = $request->getArg('version');
    if (!$version) {
        PrintXML(
            HTML::dt(fmt("Revert"), " ", WikiLink($pagename)),
            HTML::dd(_("missing required version argument"))
        );
        return;
    }
    $dbi = $request->_dbi;
    $page = $dbi->getPage($pagename);
    $current = $page->getCurrentRevision();
    if ($current->getVersion() == 0) {
        $mesg->pushContent(' ', _("no page content"));
        PrintXML(
            HTML::dt(fmt("Revert"), " ", WikiLink($pagename)),
            $mesg
        );
        return;
    }
    if ($current->getVersion() == $version) {
        $mesg->pushContent(' ', _("same version page"));
        return;
    }
    $rev = $page->getRevision($version);
    $content = $rev->getPackedContent();
    $versiondata = $rev->_data;
    $versiondata['summary'] = sprintf(_("revert to version %d"), $version);
    $new = $page->save($content, $current->getVersion() + 1, $versiondata);
    $dbi->touch();
    $mesg->pushContent(' ', fmt(
        "- version %d saved to database as version %d",
        $version,
        $new->getVersion()
    ));
    PrintXML(
        HTML::dt(fmt("Revert"), " ", WikiLink($pagename)),
        $mesg
    );
    flush();
}

function _tryinsertInterWikiMap($content)
{
    $goback = false;
    if (strpos($content, "<verbatim>")) {
        //$error_html = " The newly loaded pgsrc already contains a verbatim block.";
        $goback = true;
    }
    if (!$goback && !defined('INTERWIKI_MAP_FILE')) {
        $error_html = sprintf(" " . _("%s: not defined"), "INTERWIKI_MAP_FILE");
        $goback = true;
    }
    $mapfile = FindFile(INTERWIKI_MAP_FILE, 1);
    if (!$goback && !file_exists($mapfile)) {
        $error_html = sprintf(" " . _("%s: file not found"), INTERWIKI_MAP_FILE);
        $goback = true;
    }

    if (!empty($error_html)) {
        trigger_error(_("Default InterWiki map file not loaded.")
                      . $error_html, E_USER_NOTICE);
    }
    if ($goback) {
        return $content;
    }

    // if loading from virgin setup do echo, otherwise trigger_error E_USER_NOTICE
    if (!isa($GLOBALS['request'], 'MockRequest')) {
        echo sprintf(_("Loading InterWikiMap from external file %s."), $mapfile),"<br />";
    }

    $fd = fopen($mapfile, "rb");
    $data = fread($fd, filesize($mapfile));
    fclose($fd);
    $content = $content . "\n<verbatim>\n$data</verbatim>\n";
    return $content;
}

function ParseSerializedPage($text, $default_pagename, $user)
{
    if (!preg_match('/^a:\d+:{[si]:\d+/', $text)) {
        return false;
    }

    $pagehash = unserialize($text);

    // Split up pagehash into four parts:
    //   pagename
    //   content
    //   page-level meta-data
    //   revision-level meta-data

    if (!defined('FLAG_PAGE_LOCKED')) {
        define('FLAG_PAGE_LOCKED', 1);
    }
    $pageinfo = array('pagedata'    => array(),
                      'versiondata' => array());

    $pagedata = &$pageinfo['pagedata'];
    $versiondata = &$pageinfo['versiondata'];

    // Fill in defaults.
    if (empty($pagehash['pagename'])) {
        $pagehash['pagename'] = $default_pagename;
    }
    if (empty($pagehash['author'])) {
        $pagehash['author'] = $user->getId();
    }

    foreach ($pagehash as $key => $value) {
        switch ($key) {
            case 'pagename':
            case 'version':
            case 'hits':
                $pageinfo[$key] = $value;
                break;
            case 'content':
                $pageinfo[$key] = join("\n", $value);
                break;
            case 'flags':
                if (($value & FLAG_PAGE_LOCKED) != 0) {
                    $pagedata['locked'] = 'yes';
                }
                break;
            case 'owner':
            case 'created':
                $pagedata[$key] = $value;
                break;
            case 'acl':
            case 'perm':
                $pagedata['perm'] = ParseMimeifiedPerm($value);
                break;
            case 'lastmodified':
                $versiondata['mtime'] = $value;
                break;
            case 'author':
            case 'author_id':
            case 'summary':
                $versiondata[$key] = $value;
                break;
        }
    }
    return $pageinfo;
}

function SortByPageVersion($a, $b)
{
    return $a['version'] - $b['version'];
}

/**
 * Security alert! We should not allow to import config.ini into our wiki (or from a sister wiki?)
 * because the sql passwords are in plaintext there. And the webserver must be able to read it.
 * Detected by Santtu Jarvi.
 */
function LoadFile(&$request, $filename, $text = false, $mtime = false)
{
    if (preg_match("/config$/", dirname($filename))             // our or other config
        and preg_match("/config.*\.ini/", basename($filename))) { // backups and other versions also
        trigger_error(sprintf("Refused to load %s", $filename), E_USER_WARNING);
        return;
    }
    if (!is_string($text)) {
        // Read the file.
        $stat  = stat($filename);
        $mtime = $stat[9];
        $text  = implode("", file($filename));
    }

    if (! $request->getArg('start_debug')) {
        @set_time_limit(30); // Reset watchdog
    } else {
        @set_time_limit(240);
    }

    // FIXME: basename("filewithnoslashes") seems to return garbage sometimes.
    $basename = basename("/dummy/" . $filename);

    if (!$mtime) {
        $mtime = time();    // Last resort.
    }

    $default_pagename = rawurldecode($basename);
    if (($parts = ParseMimeifiedPages($text))) {
        usort($parts, 'SortByPageVersion');
        foreach ($parts as $pageinfo) {
            $pageinfo['pagename'] = $default_pagename;
            SavePage($request, $pageinfo, sprintf(
                _("MIME file %s"),
                $filename
            ), $basename);
        }
    } elseif (($pageinfo = ParseSerializedPage(
        $text,
        $default_pagename,
        $request->getUser()
    ))) {
        SavePage($request, $pageinfo, sprintf(
            _("Serialized file %s"),
            $filename
        ), $basename);
    } else {
        $user = $request->getUser();

        // Assume plain text file.
        $pageinfo = array('pagename' => $default_pagename,
                          'pagedata' => array(),
                          'versiondata'
                          => array('author' => $user->getId()),
                          'content'  => preg_replace(
                              '/[ \t\r]*\n/',
                              "\n",
                              chop($text)
                          )
                          );
        SavePage(
            $request,
            $pageinfo,
            sprintf(_("plain file %s"), $filename),
            $basename
        );
    }
}

function LoadDir(&$request, $dirname, $files = false, $exclude = false)
{
    $fileset = new LimitedFileSet($dirname, $files, $exclude);

    if (!$files and ($skiplist = $fileset->getSkippedFiles())) {
        PrintXML(HTML::dt(HTML::strong(_("Skipping"))));
        $list = HTML::ul();
        foreach ($skiplist as $file) {
            $list->pushContent(HTML::li(WikiLink($file)));
        }
        PrintXML(HTML::dd($list));
    }

    // Defer HomePage loading until the end. If anything goes wrong
    // the pages can still be loaded again.
    $files = $fileset->getFiles();
    if (in_array(HOME_PAGE, $files)) {
        $files = array_diff($files, array(HOME_PAGE));
        $files[] = HOME_PAGE;
    }
    $timeout = (! $request->getArg('start_debug')) ? 20 : 120;
    foreach ($files as $file) {
        longer_timeout($timeout);     // longer timeout per page
        if (substr($file, -1, 1) != '~') {  // refuse to load backup files
            LoadFile($request, "$dirname/$file");
        }
    }
}

function LoadZip(&$request, $zipfile, $files = false, $exclude = false)
{
    $zip = new ZipReader($zipfile);
    $timeout = (! $request->getArg('start_debug')) ? 20 : 120;
    while (list ($fn, $data, $attrib) = $zip->readFile()) {
        // FIXME: basename("filewithnoslashes") seems to return
        // garbage sometimes.
        $fn = basename("/dummy/" . $fn);
        if (($files && !in_array($fn, $files))
             || ($exclude && in_array($fn, $exclude))) {
            PrintXML(
                HTML::dt(WikiLink($fn)),
                HTML::dd(_("Skipping"))
            );
            flush();
            continue;
        }
        longer_timeout($timeout);     // longer timeout per page
        LoadFile($request, $fn, $data, $attrib['mtime']);
    }
}

class LimitedFileSet extends fileSet
{
    public function __construct($dirname, $_include, $exclude)
    {
        $this->_includefiles = $_include;
        $this->_exclude = $exclude;
        $this->_skiplist = array();
        parent::__construct($dirname);
    }

    public function _filenameSelector($fn)
    {
        $incl = &$this->_includefiles;
        $excl = &$this->_exclude;

        if (($incl && !in_array($fn, $incl))
             || ($excl && in_array($fn, $excl))) {
            $this->_skiplist[] = $fn;
            return false;
        } else {
            return true;
        }
    }

    public function getSkippedFiles()
    {
        return $this->_skiplist;
    }
}


function IsZipFile($filename_or_fd)
{
    // See if it looks like zip file
    if (is_string($filename_or_fd)) {
        $fd    = fopen($filename_or_fd, "rb");
        $magic = fread($fd, 4);
        fclose($fd);
    } else {
        $fpos  = ftell($filename_or_fd);
        $magic = fread($filename_or_fd, 4);
        fseek($filename_or_fd, $fpos);
    }

    return $magic == ZIP_LOCHEAD_MAGIC || $magic == ZIP_CENTHEAD_MAGIC;
}


function LoadAny(&$request, $file_or_dir, $files = false, $exclude = false)
{
    // Try urlencoded filename for accented characters.
    if (!file_exists($file_or_dir)) {
        // Make sure there are slashes first to avoid confusing phps
        // with broken dirname or basename functions.
        // FIXME: windows uses \ and :
        if (is_integer(strpos($file_or_dir, "/"))) {
            $file_or_dir = FindFile($file_or_dir);
            // Panic
            if (!file_exists($file_or_dir)) {
                $file_or_dir = dirname($file_or_dir) . "/"
                    . urlencode(basename($file_or_dir));
            }
        } else {
            // This is probably just a file.
            $file_or_dir = urlencode($file_or_dir);
        }
    }

    $type = filetype($file_or_dir);
    if ($type == 'link') {
        // For symbolic links, use stat() to determine
        // the type of the underlying file.
        list(,,$mode) = stat($file_or_dir);
        $type = ($mode >> 12) & 017;
        if ($type == 010) {
            $type = 'file';
        } elseif ($type == 004) {
            $type = 'dir';
        }
    }

    if (! $type) {
        $request->finish(fmt("Unable to load: %s", $file_or_dir));
    } elseif ($type == 'dir') {
        LoadDir($request, $file_or_dir, $files, $exclude);
    } elseif ($type != 'file' && !preg_match('/^(http|ftp):/', $file_or_dir)) {
        $request->finish(fmt("Bad file type: %s", $type));
    } elseif (IsZipFile($file_or_dir)) {
        LoadZip($request, $file_or_dir, $files, $exclude);
    } else /* if (!$files || in_array(basename($file_or_dir), $files)) */
    {
        LoadFile($request, $file_or_dir);
    }
}

function RakeSandboxAtUserRequest(&$request)
{
    $source = $request->getArg('source');
    $finder = new FileFinder;
    $source = $finder->slashifyPath($source);
    $page = rawurldecode(basename($source));
    StartLoadDump($request, fmt(
        "Loading '%s'",
        HTML(
            dirname($source),
            dirname($source) ? "/" : "",
            WikiLink($page, 'auto')
        )
    ));
    if ($source !== 'pgsrc/SandBox') {
        trigger_error($GLOBALS['Language']->getText('wiki_action_denied', 'import_page'), E_USER_ERROR);
    }
    echo "<dl>\n";
    LoadAny($request, $source);
    echo "</dl>\n";
    EndLoadDump($request);
}

/**
 * HomePage was not found so first-time install is supposed to run.
 * - import all pgsrc pages.
 * - Todo: installer interface to edit config/config.ini settings
 * - Todo: ask for existing old index.php to convert to config/config.ini
 * - Todo: theme-specific pages:
 *   blog - HomePage, ADMIN_USER/Blogs
 */
function SetupWiki(&$request)
{
    global $GenericPages, $LANG;

    //FIXME: This is a hack (err, "interim solution")
    // This is a bogo-bogo-login:  Login without
    // saving login information in session state.
    // This avoids logging in the unsuspecting
    // visitor as "The PhpWiki programming team".
    //
    // This really needs to be cleaned up...
    // (I'm working on it.)
    $real_user = $request->_user;
    if (ENABLE_USER_NEW) {
        $request->_user = new _BogoUser(_("The PhpWiki programming team"));
    } else {
        $request->_user = new WikiUser(
            $request,
            _("The PhpWiki programming team"),
            WIKIAUTH_BOGO
        );
    }
    // Get the localised wiki loading message
    $message = _("Loading up virgin wiki. Please wait until the end of the process, this will take few minutes.");

    StartLoadDump($request, $message);
    echo "<dl>\n";

    $pgsrc = FindLocalizedFile(WIKI_PGSRC);
    $default_pgsrc = FindFile(DEFAULT_WIKI_PGSRC);

    $request->setArg('overwrite', true);
    if ($default_pgsrc != $pgsrc) {
        LoadAny($request, $default_pgsrc, $GenericPages);
    }
    // Codendi - Commented-out the following line to make sure that wiki pages are properly instanciated
    // If the line is not commented, we randomly run into error messages like this at wiki init:
    // "...pagename has edit conflicts - skipped  (Merge Edit ) (Restore Anyway)"
    //$request->setArg('overwrite', false);
    LoadAny($request, $pgsrc);
    $dbi = $request->_dbi;

    // Ensure that all mandatory pages are loaded
    $finder = new FileFinder;
    foreach (array_merge(
        explode(':', 'OldTextFormattingRules:TextFormattingRules:PhpWikiAdministration'),
        $GLOBALS['AllActionPages'],
        array(constant('HOME_PAGE'))
    ) as $f) {
        $page = gettext($f);
        $epage = urlencode($page);

        if (! $dbi->isWikiPage($page)) {
            // translated version provided?
            if ($lf = FindLocalizedFile($pgsrc . $finder->_pathsep . $epage, 1)) {
                LoadAny($request, $lf);
            } else { // load english version of required action page
                LoadAny($request, FindFile(WIKI_PGSRC . $finder->_pathsep . urlencode($f)));
                $page = $f;
            }
        }
        if (! $dbi->isWikiPage($page)) {
            trigger_error(
                sprintf("Mandatory file %s couldn't be loaded!", $page),
                E_USER_WARNING
            );
        }

       //WARNING  CODENDI CODE : give permissions to the administration pages of the wiki
        $pages = array("AdministrationDePhpWiki", "AdministrationDePhpWiki/Supprimer", "AdministrationDePhpWiki/Remplacer",
           "AdministrationDePhpWiki/Renommer", "PhpWikiAdministration", "PhpWikiAdministration/Replace",
           "PhpWikiAdministration/Remove", "PhpWikiAdministration/Rename");

        if (in_array($page, $pages)) {
            $group_id = $request->getArg('group_id');

            $wikiPage = new WikiPage($group_id, $page);
            $id = $wikiPage->getId();

            $pm = PermissionsManager::instance();
            $pm->addPermission('WIKIPAGE_READ', $id, $GLOBALS['UGROUP_PROJECT_ADMIN']);
            $pm->addPermission('WIKIPAGE_READ', $id, $GLOBALS['UGROUP_WIKI_ADMIN']);
        }
        //END WARNING
    }

    echo "</dl>\n";

    EndLoadDump($request);
}

function LoadPostFile(&$request)
{
    $upload = $request->getUploadedFile('file');

    if (!$upload) {
        $request->finish(_("No uploaded file to upload?")); // FIXME: more concise message
    }

    // Dump http headers.
    StartLoadDump($request, sprintf(_("Uploading %s"), $upload->getName()));
    echo "<dl>\n";

    $fd = $upload->open();
    if (IsZipFile($fd)) {
        LoadZip($request, $fd, false, array(_("RecentChanges")));
    }

    echo "</dl>\n";
    EndLoadDump($request);
}

/**
 $Log: loadsave.php,v $
 Revision 1.139  2005/08/27 18:02:43  rurban
 fix and expand pages

 Revision 1.138  2005/08/27 09:39:10  rurban
 dumphtml when not at admin page: dump the current or given page

 Revision 1.137  2005/01/30 23:14:38  rurban
 simplify page names

 Revision 1.136  2005/01/25 07:07:24  rurban
 remove body tags in html dumps, add css and images to zipdumps, simplify printing

 Revision 1.135  2004/12/26 17:17:25  rurban
 announce dumps - mult.requests to avoid request::finish, e.g. LinkDatabase, PdfOut, ...

 Revision 1.134  2004/12/20 16:05:01  rurban
 gettext msg unification

 Revision 1.133  2004/12/08 12:57:41  rurban
 page-specific timeouts for long multi-page requests

 Revision 1.132  2004/12/08 01:18:33  rurban
 Disallow loading config*.ini files. Detected by Santtu Jarvi.

 Revision 1.131  2004/11/30 17:48:38  rurban
 just comments

 Revision 1.130  2004/11/25 08:28:12  rurban
 dont fatal on missing css or imgfiles and actually print the miss

 Revision 1.129  2004/11/25 08:11:40  rurban
 pass exclude to the get_all_pages backend

 Revision 1.128  2004/11/16 16:16:44  rurban
 enable Overwrite All for upgrade

 Revision 1.127  2004/11/01 10:43:57  rurban
 seperate PassUser methods into seperate dir (memory usage)
 fix WikiUser (old) overlarge data session
 remove wikidb arg from various page class methods, use global ->_dbi instead
 ...

 Revision 1.126  2004/10/16 15:13:39  rurban
 new [Overwrite All] button

 Revision 1.125  2004/10/14 19:19:33  rurban
 loadsave: check if the dumped file will be accessible from outside.
 and some other minor fixes. (cvsclient native not yet ready)

 Revision 1.124  2004/10/04 23:44:28  rurban
 for older or CGI phps

 Revision 1.123  2004/09/25 16:26:54  rurban
 deferr notifies (to be improved)

 Revision 1.122  2004/09/17 14:25:45  rurban
 update comments

 Revision 1.121  2004/09/08 13:38:00  rurban
 improve loadfile stability by using markup=2 as default for undefined markup-style.
 use more refs for huge objects.
 fix debug=static issue in WikiPluginCached

 Revision 1.120  2004/07/08 19:04:42  rurban
 more unittest fixes (file backend, metadata RatingsDb)

 Revision 1.119  2004/07/08 15:23:59  rurban
 less verbose for tests

 Revision 1.118  2004/07/08 13:50:32  rurban
 various unit test fixes: print error backtrace on _DEBUG_TRACE; allusers fix; new PHPWIKI_NOMAIN constant for omitting the mainloop

 Revision 1.117  2004/07/02 09:55:58  rurban
 more stability fixes: new DISABLE_GETIMAGESIZE if your php crashes when loading LinkIcons: failing getimagesize in old phps; blockparser stabilized

 Revision 1.116  2004/07/01 09:05:41  rurban
 support pages and exclude arguments for all 4 dump methods

 Revision 1.115  2004/07/01 08:51:22  rurban
 dumphtml: added exclude, print pagename before processing

 Revision 1.114  2004/06/28 12:51:41  rurban
 improved dumphtml and virgin setup

 Revision 1.113  2004/06/27 10:26:02  rurban
 oci8 patch by Philippe Vanhaesendonck + some ADODB notes+fixes

 Revision 1.112  2004/06/25 14:29:20  rurban
 WikiGroup refactoring:
   global group attached to user, code for not_current user.
   improved helpers for special groups (avoid double invocations)
 new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
 fixed a XHTML validation error on userprefs.tmpl

 Revision 1.111  2004/06/21 16:38:55  rurban
 fixed the StartLoadDump html argument hack.

 Revision 1.110  2004/06/21 16:22:30  rurban
 add DEFAULT_DUMP_DIR and HTML_DUMP_DIR constants, for easier cmdline dumps,
 fixed dumping buttons locally (images/buttons/),
 support pages arg for dumphtml,
 optional directory arg for dumpserial + dumphtml,
 fix a AllPages warning,
 show dump warnings/errors on DEBUG,
 don't warn just ignore on wikilens pagelist columns, if not loaded.
 RateIt pagelist column is called "rating", not "ratingwidget" (Dan?)

 Revision 1.109  2004/06/17 11:31:05  rurban
 jump back to label after dump/upgrade

 Revision 1.108  2004/06/16 12:43:01  rurban
 4.0.6 cannot use this errorhandler (not found)

 Revision 1.107  2004/06/14 11:31:37  rurban
 renamed global $Theme to $WikiTheme (gforge nameclash)
 inherit PageList default options from PageList
   default sortby=pagename
 use options in PageList_Selectable (limit, sortby, ...)
 added action revert, with button at action=diff
 added option regex to WikiAdminSearchReplace

 Revision 1.106  2004/06/13 13:54:25  rurban
 Catch fatals on the four dump calls (as file and zip, as html and mimified)
 FoafViewer: Check against external requirements, instead of fatal.
 Change output for xhtmldumps: using file:// urls to the local fs.
 Catch SOAP fatal by checking for GOOGLE_LICENSE_KEY
 Import GOOGLE_LICENSE_KEY and FORTUNE_DIR from config.ini.

 Revision 1.105  2004/06/08 19:48:16  rurban
 fixed foreign setup: no ugly skipped msg for the GenericPages, load english actionpages if translated not found

 Revision 1.104  2004/06/08 13:51:57  rurban
 some comments only

 Revision 1.103  2004/06/08 10:54:46  rurban
 better acl dump representation, read back acl and owner

 Revision 1.102  2004/06/06 16:58:51  rurban
 added more required ActionPages for foreign languages
 install now english ActionPages if no localized are found. (again)
 fixed default anon user level to be 0, instead of -1
   (wrong "required administrator to view this page"...)

 Revision 1.101  2004/06/04 20:32:53  rurban
 Several locale related improvements suggested by Pierrick Meignen
 LDAP fix by John Cole
 reenable admin check without ENABLE_PAGEPERM in the admin plugins

 Revision 1.100  2004/05/02 21:26:38  rurban
 limit user session data (HomePageHandle and auth_dbi have to invalidated anyway)
   because they will not survive db sessions, if too large.
 extended action=upgrade
 some WikiTranslation button work
 revert WIKIAUTH_UNOBTAINABLE (need it for main.php)
 some temp. session debug statements

 Revision 1.99  2004/05/02 15:10:07  rurban
 new finally reliable way to detect if /index.php is called directly
   and if to include lib/main.php
 new global AllActionPages
 SetupWiki now loads all mandatory pages: HOME_PAGE, action pages, and warns if not.
 WikiTranslation what=buttons for Carsten to create the missing MacOSX buttons
 PageGroupTestOne => subpages
 renamed PhpWikiRss to PhpWikiRecentChanges
 more docs, default configs, ...

 Revision 1.98  2004/04/29 23:25:12  rurban
 re-ordered locale init (as in 1.3.9)
 fixed loadfile with subpages, and merge/restore anyway
   (sf.net bug #844188)

 Revision 1.96  2004/04/19 23:13:03  zorloc
 Connect the rest of PhpWiki to the IniConfig system.  Also the keyword regular expression is not a config setting

 Revision 1.95  2004/04/18 01:11:52  rurban
 more numeric pagename fixes.
 fixed action=upload with merge conflict warnings.
 charset changed from constant to global (dynamic utf-8 switching)

 Revision 1.94  2004/03/14 16:36:37  rurban
 dont load backup files

 Revision 1.93  2004/02/26 03:22:05  rurban
 also copy css and images with XHTML Dump

 Revision 1.92  2004/02/26 02:25:54  rurban
 fix empty and #-anchored links in XHTML Dumps

 Revision 1.91  2004/02/24 17:19:37  rurban
 debugging helpers only

 Revision 1.90  2004/02/24 17:09:24  rurban
 fixed \r\r\n with dumping on windows

 Revision 1.88  2004/02/22 23:20:31  rurban
 fixed DumpHtmlToDir,
 enhanced sortby handling in PageList
   new button_heading th style (enabled),
 added sortby and limit support to the db backends and plugins
   for paging support (<<prev, next>> links on long lists)

 Revision 1.87  2004/01/26 09:17:49  rurban
 * changed stored pref representation as before.
   the array of objects is 1) bigger and 2)
   less portable. If we would import packed pref
   objects and the object definition was changed, PHP would fail.
   This doesn't happen with an simple array of non-default values.
 * use $prefs->retrieve and $prefs->store methods, where retrieve
   understands the interim format of array of objects also.
 * simplified $prefs->get() and fixed $prefs->set()
 * added $user->_userid and class '_WikiUser' portability functions
 * fixed $user object ->_level upgrading, mostly using sessions.
   this fixes yesterdays problems with loosing authorization level.
 * fixed WikiUserNew::checkPass to return the _level
 * fixed WikiUserNew::isSignedIn
 * added explodePageList to class PageList, support sortby arg
 * fixed UserPreferences for WikiUserNew
 * fixed WikiPlugin for empty defaults array
 * UnfoldSubpages: added pagename arg, renamed pages arg,
   removed sort arg, support sortby arg

 Revision 1.86  2003/12/02 16:18:26  carstenklapp
 Minor enhancement: Provide more meaningful filenames for WikiDB zip
 dumps & snapshots.

 Revision 1.85  2003/11/30 18:18:13  carstenklapp
 Minor code optimization: use include_once instead of require_once
 inside functions that might not always called.

 Revision 1.84  2003/11/26 20:47:47  carstenklapp
 Redo bugfix: My last refactoring broke merge-edit & overwrite
 functionality again, should be fixed now. Sorry.

 Revision 1.83  2003/11/20 22:18:54  carstenklapp
 New feature: h1 during merge-edit displays WikiLink to original page.
 Internal changes: Replaced some hackish url-generation code in
 function SavePage (for pgsrc merge-edit) with appropriate Button()
 calls.

 Revision 1.82  2003/11/18 19:48:01  carstenklapp
 Fixed missing gettext _() for button name.

 Revision 1.81  2003/11/18 18:28:35  carstenklapp
 Bugfix: In the Load File function of PhpWikiAdministration: When doing
 a "Merge Edit" or "Restore Anyway", page names containing accented
 letters (such as locale/de/pgsrc/G%E4steBuch) would produce a file not
 found error (Use FilenameForPage funtion to urlencode page names).

 Revision 1.80  2003/03/07 02:46:57  dairiki
 Omit checks for safe_mode before set_time_limit().  Just prefix the
 set_time_limit() calls with @ so that they fail silently if not
 supported.

 Revision 1.79  2003/02/26 01:56:05  dairiki
 Only zip pages with legal pagenames.

 Revision 1.78  2003/02/24 02:05:43  dairiki
 Fix "n bytes written" message when dumping HTML.

 Revision 1.77  2003/02/21 04:12:05  dairiki
 Minor fixes for new cached markup.

 Revision 1.76  2003/02/16 19:47:17  dairiki
 Update WikiDB timestamp when editing or deleting pages.

 Revision 1.75  2003/02/15 03:04:30  dairiki
 Fix for WikiUser constructor API change.

 Revision 1.74  2003/02/15 02:18:04  dairiki
 When default language was English (at least), pgsrc was being
 loaded twice.

 LimitedFileSet: Fix typo/bug. ($include was being ignored.)

 SetupWiki(): Fix bugs in loading of $GenericPages.

 Revision 1.73  2003/01/28 21:09:17  zorloc
 The get_cfg_var() function should only be used when one is
 interested in the value from php.ini or similar. Use ini_get()
 instead to get the effective value of a configuration variable.
 -- Martin Geisler

 Revision 1.72  2003/01/03 22:25:53  carstenklapp
 Cosmetic fix to "Merge Edit" & "Overwrite" buttons. Added "The PhpWiki
 programming team" as author when loading from pgsrc. Source
 reformatting.

 Revision 1.71  2003/01/03 02:48:05  carstenklapp
 function SavePage: Added loadfile options for overwriting or merge &
 compare a loaded pgsrc file with an existing page.

 function LoadAny: Added a general error message when unable to load a
 file instead of defaulting to "Bad file type".

 */

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
