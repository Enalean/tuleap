<?php //-*-php-*-
rcs_id('$Id$');

/*
 Copyright 1999, 2000, 2001, 2002 $ThePhpWikiProgrammingTeam

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


require_once("lib/ziplib.php");
require_once("lib/Template.php");

function StartLoadDump(&$request, $title, $html = '')
{
    // FIXME: This is a hack
    $tmpl = Template('html', array('TITLE' => $title,
                                  'HEADER' => $title,
                                  'CONTENT' => '%BODY%'));
    echo ereg_replace('%BODY%.*', '', $tmpl->getExpansion($html));
}

function EndLoadDump(&$request)
{
    // FIXME: This is a hack
    $pagelink = WikiLink($request->getPage());

    PrintXML(HTML::p(HTML::strong(_("Complete."))),
             HTML::p(fmt("Return to %s", $pagelink)));
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
function MailifyPage ($page, $nversions = 1)
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
    $head .= sprintf("Mime-Version: 1.0 (Produced by PhpWiki %s)\r\n",
                     PHPWIKI_VERSION);

    // This should just be entered by hand (or by script?)
    // in the actual pgsrc files, since only they should have
    // RCS ids.
    //$head .= "X-Rcs-Id: \$Id\$\r\n";

    $iter = $page->getAllRevisions();
    $parts = array();
    while ($revision = $iter->next()) {
        $parts[] = MimeifyPageRevision($revision);
        if ($nversions > 0 && count($parts) >= $nversions)
            break;
    }
    if (count($parts) > 1)
        return $head . MimeMultipart($parts);
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
function FilenameForPage ($pagename)
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
function MakeWikiZip (&$request)
{
    if ($request->getArg('include') == 'all') {
        $zipname         = WIKI_NAME . _("FullDump") . date('Ymd-Hi') . '.zip';
        $include_archive = true;
    }
    else {
        $zipname         = WIKI_NAME . _("LatestSnapshot") . date('Ymd-Hi') . '.zip';
        $include_archive = false;
    }



    $zip = new ZipWriter("Created by PhpWiki " . PHPWIKI_VERSION, $zipname);

    $dbi = $request->getDbh();
    $pages = $dbi->getAllPages();
    while ($page = $pages->next()) {
    	if (! $request->getArg('start_debug'))
            @set_time_limit(30); // Reset watchdog

        $current = $page->getCurrentRevision();
        if ($current->getVersion() == 0)
            continue;

        $wpn = new WikiPageName($page->getName());
        if (!$wpn->isValid())
            continue;

        $attrib = array('mtime'    => $current->get('mtime'),
                        'is_ascii' => 1);
        if ($page->get('locked'))
            $attrib['write_protected'] = 1;

        if ($include_archive)
            $content = MailifyPage($page, 0);
        else
            $content = MailifyPage($page);

        $zip->addRegularFile( FilenameForPage($page->getName()),
                              $content, $attrib);
    }
    $zip->finish();
}

function DumpToDir (&$request)
{
    $directory = $request->getArg('directory');
    if (empty($directory))
        $request->finish(_("You must specify a directory to dump to"));

    // see if we can access the directory the user wants us to use
    if (! file_exists($directory)) {
        if (! mkdir($directory, 0755))
            $request->finish(fmt("Cannot create directory '%s'", $directory));
        else
            $html = HTML::p(fmt("Created directory '%s' for the page dump...",
                                $directory));
    } else {
        $html = HTML::p(fmt("Using directory '%s'", $directory));
    }

    StartLoadDump($request, _("Dumping Pages"), $html);

    $dbi = $request->getDbh();
    $pages = $dbi->getAllPages();

    while ($page = $pages->next()) {
    	if (! $request->getArg('start_debug'))
          @set_time_limit(30); // Reset watchdog.

        $filename = FilenameForPage($page->getName());

        $msg = HTML(HTML::br(), $page->getName(), ' ... ');

        if($page->getName() != $filename) {
            $msg->pushContent(HTML::small(fmt("saved as %s", $filename)),
                              " ... ");
        }

        if ($request->getArg('include') == 'all')
            $data = MailifyPage($page, 0);
        else
            $data = MailifyPage($page);

        if ( !($fd = fopen("$directory/$filename", "wb")) ) {
            $msg->pushContent(HTML::strong(fmt("couldn't open file '%s' for writing",
                                               "$directory/$filename")));
            $request->finish($msg);
        }

        $num = fwrite($fd, $data, strlen($data));
        $msg->pushContent(HTML::small(fmt("%s bytes written", $num)));
        PrintXML($msg);

        flush();
        assert($num == strlen($data));
        fclose($fd);
    }

    EndLoadDump($request);
}


function DumpHtmlToDir (&$request)
{
    $directory = $request->getArg('directory');
    if (empty($directory))
        $request->finish(_("You must specify a directory to dump to"));

    // see if we can access the directory the user wants us to use
    if (! file_exists($directory)) {
        if (! mkdir($directory, 0755))
            $request->finish(fmt("Cannot create directory '%s'", $directory));
        else
            $html = HTML::p(fmt("Created directory '%s' for the page dump...",
                                $directory));
    } else {
        $html = HTML::p(fmt("Using directory '%s'", $directory));
    }

    StartLoadDump($request, _("Dumping Pages"), $html);
    $thispage = $request->getArg('pagename'); // for "Return to ..."

    $dbi = $request->getDbh();
    $pages = $dbi->getAllPages();

    global $Theme;
    if (defined('HTML_DUMP_SUFFIX'))
        $Theme->HTML_DUMP_SUFFIX = HTML_DUMP_SUFFIX;
    $Theme->DUMP_MODE = 'HTML';

    while ($page = $pages->next()) {
    	if (! $request->getArg('start_debug'))
          @set_time_limit(30); // Reset watchdog.

        $pagename = $page->getName();
        $request->setArg('pagename',$pagename); // Template::_basepage fix
        $filename = FilenameForPage($pagename) . $Theme->HTML_DUMP_SUFFIX;

        $msg = HTML(HTML::br(), $pagename, ' ... ');

        if($page->getName() != $filename) {
            $msg->pushContent(HTML::small(fmt("saved as %s", $filename)),
                              " ... ");
        }

        $revision = $page->getCurrentRevision();
        $transformedContent = $revision->getTransformedContent();
        $template = new Template('browse', $request,
                                 array('revision' => $revision,
                                       'CONTENT' => $transformedContent));

        $data = GeneratePageasXML($template, $pagename);

        if ( !($fd = fopen("$directory/$filename", "wb")) ) {
            $msg->pushContent(HTML::strong(fmt("couldn't open file '%s' for writing",
                                               "$directory/$filename")));
            $request->finish($msg);
        }

        $num = fwrite($fd, $data, strlen($data));
        $msg->pushContent(HTML::small(fmt("%s bytes written", $num), "\n"));
        PrintXML($msg);

        flush();
        assert($num == strlen($data));
        fclose($fd);
    }

    if (is_array($Theme->dumped_images)) {
        @mkdir("$directory/images");
        foreach ($Theme->dumped_images as $img_file) {
            if (($from = $Theme->_findFile($img_file)) and basename($from)) {
                $target = "$directory/images/".basename($img_file);
                if (copy($Theme->_path . $from, $target)) {
                    $msg = HTML(HTML::br(), HTML($from), HTML::small(fmt("... copied to %s", $target)));
                    PrintXML($msg);
                }
            } else {
                $msg = HTML(HTML::br(), HTML($from), HTML::small(fmt("... not found", $target)));
                PrintXML($msg);
            }
        }
    }
    if (is_array($Theme->dumped_css)) {
      foreach ($Theme->dumped_css as $css_file) {
          if (($from = $Theme->_findFile(basename($css_file))) and basename($from)) {
              $target = "$directory/" . basename($css_file);
              if (copy($Theme->_path . $from, $target)) {
                  $msg = HTML(HTML::br(), HTML($from), HTML::small(fmt("... copied to %s", $target)));
                  PrintXML($msg);
              }
          } else {
              $msg = HTML(HTML::br(), HTML($from), HTML::small(fmt("... not found", $target)));
              PrintXML($msg);
          }
      }
    }
    $Theme->HTML_DUMP_SUFFIX = '';
    $Theme->DUMP_MODE = false;

    $request->setArg('pagename',$thispage); // Template::_basepage fix
    EndLoadDump($request);
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
function MakeWikiZipHtml (&$request)
{
    $zipname = "wikihtml.zip";
    $zip = new ZipWriter("Created by PhpWiki " . PHPWIKI_VERSION, $zipname);
    $dbi = $request->getDbh();
    $pages = $dbi->getAllPages();

    global $Theme;
    if (defined('HTML_DUMP_SUFFIX'))
        $Theme->HTML_DUMP_SUFFIX = HTML_DUMP_SUFFIX;

    while ($page = $pages->next()) {
    	if (! $request->getArg('start_debug'))
            @set_time_limit(30); // Reset watchdog.

        $current = $page->getCurrentRevision();
        if ($current->getVersion() == 0)
            continue;

        $attrib = array('mtime'    => $current->get('mtime'),
                        'is_ascii' => 1);
        if ($page->get('locked'))
            $attrib['write_protected'] = 1;

        $pagename = $page->getName();
        $request->setArg('pagename',$pagename); // Template::_basepage fix
        $filename = FilenameForPage($pagename) . $Theme->HTML_DUMP_SUFFIX;
        $revision = $page->getCurrentRevision();

        $transformedContent = $revision->getTransformedContent();

        $template = new Template('browse', $request,
                                 array('revision' => $revision,
                                       'CONTENT' => $transformedContent));

        $data = GeneratePageasXML($template, $pagename);

        $zip->addRegularFile( $filename, $data, $attrib);
    }
    // FIXME: Deal with images here.
    $zip->finish();
    $Theme->$HTML_DUMP_SUFFIX = '';
}


////////////////////////////////////////////////////////////////
//
//  Functions for restoring.
//
////////////////////////////////////////////////////////////////

function SavePage (&$request, $pageinfo, $source, $filename)
{
    $pagedata    = $pageinfo['pagedata'];    // Page level meta-data.
    $versiondata = $pageinfo['versiondata']; // Revision level meta-data.

    if (empty($pageinfo['pagename'])) {
        PrintXML(HTML::dt(HTML::strong(_("Empty pagename!"))));
        return;
    }

    if (empty($versiondata['author_id']))
        $versiondata['author_id'] = $versiondata['author'];

    $pagename = $pageinfo['pagename'];
    $content  = $pageinfo['content'];

    if ($pagename ==_("InterWikiMap"))
        $content = _tryinsertInterWikiMap($content);

    $dbi = $request->getDbh();
    $page = $dbi->getPage($pagename);

    $current = $page->getCurrentRevision();
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
    }
    else if ($request->getArg('overwrite')) {
        $overwrite = true;
    }

    if ( (! $current->hasDefaultContents())
         && ($current->getPackedContent() != $content)
         && ($merging == true) ) {
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
        if (!empty($value))
            $page->set($key, $value);
    }

    $mesg = HTML::dd();
    $skip = false;
    if ($source)
        $mesg->pushContent(' ', fmt("from %s", $source));


    $current = $page->getCurrentRevision();
    if ($current->getVersion() == 0) {
        $mesg->pushContent(' ', _("new page"));
        $isnew = true;
    }
    else {
        if ( (! $current->hasDefaultContents())
             && ($current->getPackedContent() != $content) ) {
            if ($overwrite) {
                $mesg->pushContent(' ',
                                   fmt("has edit conflicts - overwriting anyway"));
                $skip = false;
                if (substr_count($source, 'pgsrc')) {
                    $versiondata['author'] = _("The PhpWiki programming team");
                    // but leave authorid as userid who loaded the file
                }
            }
            else {
                $mesg->pushContent(' ', fmt("has edit conflicts - skipped"));
                $needs_merge = true; // hackish
                $skip = true;
            }
        }
        else if ($current->getPackedContent() == $content
                 && $current->get('author') == $versiondata['author']) {
            $mesg->pushContent(' ',
                               fmt("is identical to current version %d - skipped",
                                   $current->getVersion()));
            $skip = true;
        }
        $isnew = false;
    }

    if (! $skip) {
        $new = $page->save($content, WIKIDB_FORCE_CREATE, $versiondata);
        $dbi->touch();
        $mesg->pushContent(' ', fmt("- saved to database as version %d",
                                    $new->getVersion()));
    }
    if ($needs_merge) {
        $f = $source;
        // hackish, $source contains needed path+filename
        $f = str_replace(sprintf(_("MIME file %s"), ''), '', $f);
        $f = str_replace(sprintf(_("Serialized file %s"), ''), '', $f);
        $f = str_replace(sprintf(_("plain file %s"), ''), '', $f);
        //check if uploaded file? they pass just the content, but the file is gone
        if (@stat($f)) {
            global $Theme;
            $meb = Button(array('action' => 'loadfile',
                                'merge'=> true,
                                'source'=> $f),
                          _("Merge Edit"),
                          _("PhpWikiAdministration"),
                          'wikiadmin');
            $owb = Button(array('action' => 'loadfile',
                                'overwrite'=> true,
                                'source'=> $f),
                          _("Restore Anyway"),
                          _("PhpWikiAdministration"),
                          'wikiunsafe');
            $mesg->pushContent(' ', $meb, " ", $owb);
        } else {
            $mesg->pushContent(HTML::em(_(" Sorry, cannot merge uploaded files.")));
        }
    }

    if ($skip)
        PrintXML(HTML::dt(HTML::em(WikiLink($pagename))), $mesg);
    else
        PrintXML(HTML::dt(WikiLink($pagename)), $mesg);
    flush();
}

function _tryinsertInterWikiMap($content) {
    $goback = false;
    if (strpos($content, "<verbatim>")) {
        //$error_html = " The newly loaded pgsrc already contains a verbatim block.";
        $goback = true;
    }
    if (!$goback && !defined('INTERWIKI_MAP_FILE')) {
        $error_html = sprintf(" "._("%s: not defined"), "INTERWIKI_MAP_FILE");
        $goback = true;
    }
    $mapfile = FindFile(INTERWIKI_MAP_FILE,1);
    if (!$goback && !file_exists($mapfile)) {
        $error_html = sprintf(" "._("%s: file not found"), INTERWIKI_MAP_FILE);
        $goback = true;
    }

    if (!empty($error_html))
        trigger_error(_("Default InterWiki map file not loaded.")
                      . $error_html, E_USER_NOTICE);
    if ($goback)
        return $content;

    // if loading from virgin setup do echo, otherwise trigger_error E_USER_NOTICE
    echo sprintf(_("Loading InterWikiMap from external file %s."), $mapfile),"<br />";

    $fd = fopen ($mapfile, "rb");
    $data = fread ($fd, filesize($mapfile));
    fclose ($fd);
    $content = $content . "\n<verbatim>\n$data</verbatim>\n";
    return $content;
}

function ParseSerializedPage($text, $default_pagename, $user)
{
    if (!preg_match('/^a:\d+:{[si]:\d+/', $text))
        return false;

    $pagehash = unserialize($text);

    // Split up pagehash into four parts:
    //   pagename
    //   content
    //   page-level meta-data
    //   revision-level meta-data

    if (!defined('FLAG_PAGE_LOCKED'))
        define('FLAG_PAGE_LOCKED', 1);
    $pageinfo = array('pagedata'    => array(),
                      'versiondata' => array());

    $pagedata = &$pageinfo['pagedata'];
    $versiondata = &$pageinfo['versiondata'];

    // Fill in defaults.
    if (empty($pagehash['pagename']))
        $pagehash['pagename'] = $default_pagename;
    if (empty($pagehash['author'])) {
        $pagehash['author'] = $user->getId();
    }

    foreach ($pagehash as $key => $value) {
        switch($key) {
            case 'pagename':
            case 'version':
            case 'hits':
                $pageinfo[$key] = $value;
                break;
            case 'content':
                $pageinfo[$key] = join("\n", $value);
                break;
            case 'flags':
                if (($value & FLAG_PAGE_LOCKED) != 0)
                    $pagedata['locked'] = 'yes';
                break;
            case 'created':
                $pagedata[$key] = $value;
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

function SortByPageVersion ($a, $b) {
    return $a['version'] - $b['version'];
}

function LoadFile (&$request, $filename, $text = false, $mtime = false)
{
    if (!is_string($text)) {
        // Read the file.
        $stat  = stat($filename);
        $mtime = $stat[9];
        $text  = implode("", file($filename));
    }

    if (! $request->getArg('start_debug'))
        @set_time_limit(30); // Reset watchdog.

    // FIXME: basename("filewithnoslashes") seems to return garbage sometimes.
    $basename = basename("/dummy/" . $filename);

    if (!$mtime)
        $mtime = time();    // Last resort.

    $default_pagename = rawurldecode($basename);

    if ( ($parts = ParseMimeifiedPages($text)) ) {
        usort($parts, 'SortByPageVersion');
        foreach ($parts as $pageinfo)
            SavePage($request, $pageinfo, sprintf(_("MIME file %s"),
                                                  $filename), $basename);
    }
    else if ( ($pageinfo = ParseSerializedPage($text, $default_pagename,
                                               $request->getUser())) ) {
        SavePage($request, $pageinfo, sprintf(_("Serialized file %s"),
                                              $filename), $basename);
    }
    else {
        $user = $request->getUser();

        // Assume plain text file.
        $pageinfo = array('pagename' => $default_pagename,
                          'pagedata' => array(),
                          'versiondata'
                          => array('author' => $user->getId()),
                          'content'  => preg_replace('/[ \t\r]*\n/', "\n",
                                                     chop($text))
                          );
        SavePage($request, $pageinfo, sprintf(_("plain file %s"), $filename),
                 $basename);
    }
}

function LoadZip (&$request, $zipfile, $files = false, $exclude = false) {
    $zip = new ZipReader($zipfile);
    while (list ($fn, $data, $attrib) = $zip->readFile()) {
        // FIXME: basename("filewithnoslashes") seems to return
        // garbage sometimes.
        $fn = basename("/dummy/" . $fn);
        if ( ($files && !in_array($fn, $files))
             || ($exclude && in_array($fn, $exclude)) ) {
            PrintXML(HTML::dt(WikiLink($fn)),
                     HTML::dd(_("Skipping")));
            continue;
        }

        LoadFile($request, $fn, $data, $attrib['mtime']);
    }
}

function LoadDir (&$request, $dirname, $files = false, $exclude = false) {
    $fileset = new LimitedFileSet($dirname, $files, $exclude);

    if (($skiplist = $fileset->getSkippedFiles())) {
        PrintXML(HTML::dt(HTML::strong(_("Skipping"))));
        $list = HTML::ul();
        foreach ($skiplist as $file)
            $list->pushContent(HTML::li(WikiLink($file)));
        PrintXML(HTML::dd($list));
    }

    // Defer HomePage loading until the end. If anything goes wrong
    // the pages can still be loaded again.
    $files = $fileset->getFiles();
    if (in_array(HOME_PAGE, $files)) {
        $files = array_diff($files, array(HOME_PAGE));
        $files[] = HOME_PAGE;
    }
    foreach ($files as $file) {
        if (substr($file,-1,1) != '~') // refuse to load backup files
            LoadFile($request, "$dirname/$file");
    }
}

class LimitedFileSet extends FileSet {
    function LimitedFileSet($dirname, $_include, $exclude) {
        $this->_includefiles = $_include;
        $this->_exclude = $exclude;
        $this->_skiplist = array();
        parent::FileSet($dirname);
    }

    function _filenameSelector($fn) {
        $incl = &$this->_includefiles;
        $excl = &$this->_exclude;

        if ( ($incl && !in_array($fn, $incl))
             || ($excl && in_array($fn, $excl)) ) {
            $this->_skiplist[] = $fn;
            return false;
        } else {
            return true;
        }
    }

    function getSkippedFiles () {
        return $this->_skiplist;
    }
}


function IsZipFile ($filename_or_fd)
{
    // See if it looks like zip file
    if (is_string($filename_or_fd))
    {
        $fd    = fopen($filename_or_fd, "rb");
        $magic = fread($fd, 4);
        fclose($fd);
    }
    else
    {
        $fpos  = ftell($filename_or_fd);
        $magic = fread($filename_or_fd, 4);
        fseek($filename_or_fd, $fpos);
    }

    return $magic == ZIP_LOCHEAD_MAGIC || $magic == ZIP_CENTHEAD_MAGIC;
}


function LoadAny (&$request, $file_or_dir, $files = false, $exclude = false)
{
    // Try urlencoded filename for accented characters.
    if (!file_exists($file_or_dir)) {
        // Make sure there are slashes first to avoid confusing phps
        // with broken dirname or basename functions.
        // FIXME: windows uses \ and :
        if (is_integer(strpos($file_or_dir, "/"))) {
            $file_or_dir = FindFile($file_or_dir);
            // Panic
            if (!file_exists($file_or_dir))
                $file_or_dir = dirname($file_or_dir) . "/"
                    . urlencode(basename($file_or_dir));
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
        if ($type == 010)
            $type = 'file';
        elseif ($type == 004)
            $type = 'dir';
    }

    if (! $type) {
        $request->finish(fmt("Unable to load: %s", $file_or_dir));
    }
    else if ($type == 'dir') {
        LoadDir($request, $file_or_dir, $files, $exclude);
    }
    else if ($type != 'file' && !preg_match('/^(http|ftp):/', $file_or_dir))
    {
        $request->finish(fmt("Bad file type: %s", $type));
    }
    else if (IsZipFile($file_or_dir)) {
        LoadZip($request, $file_or_dir, $files, $exclude);
    }
    else /* if (!$files || in_array(basename($file_or_dir), $files)) */
    {
        LoadFile($request, $file_or_dir);
    }
}

function LoadFileOrDir (&$request)
{
    $source = $request->getArg('source');
    $finder = new FileFinder;
    $source = $finder->slashifyPath($source);
    $page = rawurldecode(basename($source));
    StartLoadDump($request, fmt("Loading '%s'", 
    	HTML(dirname($source),
             dirname($source) ? "/" : "",
             WikiLink($page,'auto'))));
    echo "<dl>\n";
    LoadAny($request, $source);
    echo "</dl>\n";
    EndLoadDump($request);
}

function SetupWiki (&$request)
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
    if (ENABLE_USER_NEW)
        $request->_user = new _BogoUser(_("The PhpWiki programming team"));

    else
        $request->_user = new WikiUser($request, _("The PhpWiki programming team"),
                                       WIKIAUTH_BOGO);

    StartLoadDump($request, _("Loading up virgin wiki"));
    echo "<dl>\n";
    $pgsrc = FindLocalizedFile(WIKI_PGSRC);
    $default_pgsrc = FindFile(DEFAULT_WIKI_PGSRC);

    $request->setArg('overwrite',true);
    if ($default_pgsrc != $pgsrc)
        LoadAny($request, $default_pgsrc, $GenericPages);
    $request->setArg('overwrite',false);
    LoadAny($request, $pgsrc);

    // Ensure that all mandatory pages are loaded
    $finder = new FileFinder;
    foreach (array_merge(explode(':',constant('HOME_PAGE')
                                 .':OldTextFormattingRules:TextFormattingRules'),
                         $GLOBALS['AllActionPages']) as $f) {
        $page = gettext($f);
        if (! $request->_dbi->isWikiPage($page) ) {
            // translated version provided?
            if ($f = FindLocalizedFile($pgsrc . $finder->_pathsep . $page, 1))
                LoadAny($request, $f);
            /*
            else {
                LoadAny($request, FindFile(WIKI_PGSRC . $finder->_pathsep . $f));
                $page = basename($f);
            }
            */
        }
        if (!$request->_dbi->isWikiPage($page)) {
            trigger_error(sprintf("Mandatory file %s couldn't be loaded!",$page),E_USER_WARNING);
        }
    }

    echo "</dl>\n";
    EndLoadDump($request);
}

function LoadPostFile (&$request)
{
    $upload = $request->getUploadedFile('file');

    if (!$upload)
        $request->finish(_("No uploaded file to upload?")); // FIXME: more concise message


    // Dump http headers.
    StartLoadDump($request, sprintf(_("Uploading %s"), $upload->getName()));
    echo "<dl>\n";

    $fd = $upload->open();
    if (IsZipFile($fd))
        LoadZip($request, $fd, false, array(_("RecentChanges")));
    else
        LoadFile($request, $upload->getName(), $upload->getContents());

    echo "</dl>\n";
    EndLoadDump($request);
}

/**
 $Log$
 Revision 1.2  2005/05/12 10:08:04  guerin
 2nd major commit for Wiki integration:
 - use localisation from phpWiki
 - changed wiki creation process
 - fixed a few other minor issues.

 Revision 1.1  2005/04/12 13:33:28  guerin
 First commit for wiki integration.
 Added Manuel's code as of revision 13 on Partners.
 Very little modification at the moment:
 - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
 - simplified require syntax
 - removed ST-specific code (for test phase)

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
?>
