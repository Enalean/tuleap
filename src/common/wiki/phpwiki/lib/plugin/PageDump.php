<?php
// -*-php-*-
rcs_id('$Id: PageDump.php,v 1.18 2004/10/14 19:19:34 rurban Exp $');
/**
 * PhpWikiPlugin for PhpWiki developers to generate single page dumps
 * for checking into cvs, or for users or the admin to produce a
 * downloadable page dump of a single page.
 *
 * This plugin will also be useful to (semi-)automatically sync pages
 * directly between two wikis. First the LoadFile function of
 * PhpWikiAdministration needs to be updated to handle URLs again, and
 * add loading capability from InterWiki addresses.

 * Multiple revisions in one file handled by format=backup
 *
 * TODO: What about comments/summary field? quoted-printable?
 *
 * Usage:
 *  Direct URL access:
 *   http://...phpwiki/PageDump?page=HomePage?format=forcvs
 *   http://...phpwiki/index.php?PageDump&page=HomePage
 *   http://...phpwiki/index.php?PageDump&page=HomePage&download=1
 *  Static:
 *   <?plugin PageDump page=HomePage?>
 *  Dynamic form (put both on the page):
 *   <?plugin PageDump?>
 *   <?plugin-form PageDump?>
 *  Typical usage: as actionbar button
 */

class WikiPlugin_PageDump extends WikiPlugin
{
    public $MessageId;

    public function getName()
    {
        return _("PageDump");
    }
    public function getDescription()
    {
        return _("View a single page dump online.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.18 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('s'    => false,
                     'page' => '[pagename]',
                     //'encoding' => 'binary', // 'binary', 'quoted-printable'
                     'format' => false, // 'normal', 'forcvs', 'backup'
                     // display within WikiPage or give a downloadable
                     // raw pgsrc?
                     'download' => false);
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));
        // allow plugin-form
        if (!empty($s)) {
            $page = $s;
        }
        if (!$page) {
            return '';
        }
        if (! $dbi->isWikiPage($page)) {
            return fmt(
                "Page %s not found.",
                WikiLink($page, 'unknown')
            );
        }

        $p = $dbi->getPage($page);
        include_once("lib/loadsave.php");
        $mailified = MailifyPage($p, ($format == 'backup') ? 99 : 1);

        // fixup_headers massages the page dump headers depending on
        // the 'format' argument, 'normal'(default) or 'forcvs'.
        //
        // Normal: Don't add X-Rcs-Id, add unique Message-Id, don't
        // strip any fields from Content-Type.
        //
        // ForCVS: Add empty X-Rcs-Id, strip attributes from
        // Content-Type field: "author", "version", "lastmodified",
        // "author_id", "hits".

        $this->pagename = $page;
        $this->generateMessageId($mailified);
        if ($format == 'forcvs') {
            $this->fixup_headers_forcvs($mailified);
        } else { // backup or normal
            $this->fixup_headers($mailified);
        }

        if ($download) {
            // TODO: we need a way to hook into the generated headers, to override
            // Content-Type, Set-Cookie, Cache-control, ...
            $request->discardOutput(); // Hijack the http request from PhpWiki.
            ob_end_clean(); // clean up after hijacking $request
            //ob_end_flush(); //debugging
            Header("Content-disposition: attachment; filename=\""
                   . FilenameForPage($page) . "\"");
            // Read charset from generated page itself.
            // Inconsequential at the moment, since loadsave.php
            // always generates headers
            $charset = $p->get('charset');
            if (!$charset) {
                $charset = $GLOBALS['charset'];
            }
            // We generate 3 Content-Type headers! first in loadsave,
            // then here and the mimified string $mailified also has it!
            Header("Content-Type: text/plain; name=\""
                   . FilenameForPage($page) . "\"; charset=\"" . $charset
                   . "\"");
            $request->checkValidators();
            // let $request provide last modifed & etag
            Header("Content-Id: <" . $this->MessageId . ">");
            // be nice to http keepalive~s
            // FIXME: he length is wrong BTW. must strip the header.
            Header("Content-Length: " . strlen($mailified));

            // Here comes our prepared mime file
            echo $mailified;
            exit; // noreturn! php exits.
            return;
        }
        // We are displaing inline preview in a WikiPage, so wrap the
        // text if it is too long--unless quoted-printable (TODO).
        $mailified = safe_wordwrap($mailified, 70);

        $dlcvs = Button(
            array(//'page' => $page,
                              'action' => $this->getName(),
                              'format' => 'forcvs',
                              'download' => true),
            _("Download for CVS"),
            $page
        );
        $dl = Button(
            array(//'page' => $page,
                           'action' => $this->getName(),
                           'download' => true),
            _("Download for backup"),
            $page
        );
        $dlall = Button(
            array(//'page' => $page,
                           'action' => $this->getName(),
                           'format' => 'backup',
                           'download' => true),
            _("Download all revisions for backup"),
            $page
        );

        $h2 = HTML::h2(fmt(
            "Preview: Page dump of %s",
            WikiLink($page, 'auto')
        ));
        global $WikiTheme;
        if (!$Sep = $WikiTheme->getButtonSeparator()) {
            $Sep = " ";
        }

        if ($format == 'forcvs') {
            $desc = _("(formatted for PhpWiki developers as pgsrc template, not for backing up)");
            $altpreviewbuttons = HTML(
                Button(
                    array('action' => $this->getName()),
                    _("Preview as normal format"),
                    $page
                ),
                $Sep,
                Button(
                    array(
                                                   'action' => $this->getName(),
                                                   'format' => 'backup'),
                    _("Preview as backup format"),
                    $page
                )
            );
        } elseif ($format == 'backup') {
            $desc = _("(formatted for backing up: all revisions)"); // all revisions
            $altpreviewbuttons = HTML(
                Button(
                    array('action' => $this->getName(),
                                                   'format' => 'forcvs'),
                    _("Preview as developer format"),
                    $page
                ),
                $Sep,
                Button(
                    array(
                                                   'action' => $this->getName(),
                                                   'format' => ''),
                    _("Preview as normal format"),
                    $page
                )
            );
        } else {
            $desc = _("(normal formatting: latest revision only)");
            $altpreviewbuttons = HTML(
                Button(
                    array('action' => $this->getName(),
                                                   'format' => 'forcvs'),
                    _("Preview as developer format"),
                    $page
                ),
                $Sep,
                Button(
                    array(
                                                   'action' => $this->getName(),
                                                   'format' => 'backup'),
                    _("Preview as backup format"),
                    $page
                )
            );
        }
        $warning = HTML(
            _("Please use one of the downloadable versions rather than copying and pasting from the above preview.")
            . " " .
            _("The wordwrap of the preview doesn't take nested markup or list indentation into consideration!")
            . " ",
            HTML::em(
                _("PhpWiki developers should manually inspect the downloaded file for nested markup before rewrapping with emacs and checking into CVS.")
            )
        );

        return HTML(
            $h2,
            HTML::em($desc),
            HTML::pre($mailified),
            $altpreviewbuttons,
            HTML::div(
                array('class' => 'errors'),
                HTML::strong(_("Warning:")),
                " ",
                $warning
            ),
            $dl,
            $Sep,
            $dlall,
            $Sep,
            $dlcvs
        );
    }

    // function handle_plugin_args_cruft(&$argstr, &$args) {
    // }

    public function generateMessageId($mailified)
    {
        $array = explode("\n", $mailified);
        // Extract lastmodifed from mailified document for Content-Id
        // and/or Message-Id header, NOT from DB (page could have been
        // edited by someone else since we started).
        $m1 = preg_grep("/^\s+lastmodified\=(.*);/", $array);
        $m1 = array_values($m1); //reset resulting keys
        unset($array);
        $m2 = preg_split(
            "/(^\s+lastmodified\=)|(;)/",
            $m1[0],
            2,
            PREG_SPLIT_NO_EMPTY
        );

        // insert message id into actual message when appropriate, NOT
        // into http header should be part of fixup_headers, in the
        // format:
        // <abbrphpwikiversion.mtimeepochTZ%InterWikiLinktothispage@hostname>
        // Hopefully this provides a unique enough identifier without
        // using md5. Even though this particular wiki may not
        // actually be part of InterWiki, including this info provides
        // the wiki name and name of the page which is being
        // represented as a text message.
        $this->MessageId = implode('', explode('.', PHPWIKI_VERSION))
            . "-" . $m2[0] . date("O")
            //. "-". rawurlencode(WIKI_NAME.":" . $request->getURLtoSelf())
            . "-" . rawurlencode(WIKI_NAME . ":" . $this->pagename)
            . "@" . rawurlencode(SERVER_NAME);
    }

    public function fixup_headers(&$mailified)
    {
        $return = explode("\n", $mailified);

        // Leave message intact for backing up, just add Message-Id header before transmitting.
        $item_to_insert = "Message-Id: <" . $this->MessageId . ">";
        $insert_into_key_position = 2;
        $returnval_ignored = array_splice(
            $return,
            $insert_into_key_position,
            0,
            $item_to_insert
        );

        $mailified = implode("\n", array_values($return));
    }

    public function fixup_headers_forcvs(&$mailified)
    {
        $array = explode("\n", $mailified);

        // Massage headers to prepare for developer checkin to CVS.
        $item_to_insert = "X-Rcs-Id: \$Id\$";
        $insert_into_key_position = 2;
        $returnval_ignored = array_splice(
            $array,
            $insert_into_key_position,
            0,
            $item_to_insert
        );

        $item_to_insert = "  pgsrc_version=\"2 \$Revision\$\";";
        $insert_into_key_position = 5;
        $returnval_ignored = array_splice(
            $array,
            $insert_into_key_position,
            0,
            $item_to_insert
        );
        /*
            Strip out all this junk:
            author=MeMe;
            version=74;
            lastmodified=1041561552;
            author_id=127.0.0.1;
            hits=146;
        */
        $killme = array("author", "version", "lastmodified",
                        "author_id", "hits", "owner", "acl");
        // UltraNasty, fixme:
        foreach ($killme as $pattern) {
            $array = preg_replace(
                "/^\s\s$pattern\=.*;/",
                /*$replacement =*/"zzzjunk",
                $array
            );
        }
        // remove deleted values from array
        for ($i = 0; $i < count($array); $i++) {
            if (trim($array[$i]) != "zzzjunk") { //nasty, fixme
            //trigger_error("'$array[$i]'");//debugging
                $return[] = $array[$i];
            }
        }

        $mailified = implode("\n", $return);
    }
}

// $Log: PageDump.php,v $
// Revision 1.18  2004/10/14 19:19:34  rurban
// loadsave: check if the dumped file will be accessible from outside.
// and some other minor fixes. (cvsclient native not yet ready)
//
// Revision 1.17  2004/09/16 07:49:01  rurban
// use the page charset instead if the global one on download
//   (need to clarify header order, since we print the same header type 3 times!)
// wordwrap workaround (security concern)
//
// Revision 1.16  2004/07/01 06:31:23  rurban
// doc upcase only
//
// Revision 1.15  2004/06/29 10:09:06  rurban
// better desc
//
// Revision 1.14  2004/06/29 10:07:40  rurban
// added dump of all revisions by format=backup (screen and download)
//
// Revision 1.13  2004/06/17 10:39:18  rurban
// fix reverse translation of possible actionpage
//
// Revision 1.12  2004/06/16 13:32:43  rurban
// fix urlencoding of pagename in PageDump buttons
//
// Revision 1.11  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.10  2004/06/07 22:28:05  rurban
// add acl field to mimified dump
//
// Revision 1.9  2004/06/07 19:50:41  rurban
// add owner field to mimified dump
//
// Revision 1.8  2004/05/25 12:43:29  rurban
// ViewSource link, better actionpage usage
//
// Revision 1.7  2004/05/04 17:21:06  rurban
// revert previous patch
//
// Revision 1.6  2004/05/03 20:44:55  rurban
// fixed gettext strings
// new SqlResult plugin
// _WikiTranslation: fixed init_locale
//
// Revision 1.5  2004/05/03 17:42:44  rurban
// fix cvs tags: "$tag$" => "$tag: $"
//
// Revision 1.4  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//
// Revision 1.3  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.2  2003/12/12 01:08:30  carstenklapp
// QuickFix for invalid Message-Id header format.
//
// Revision 1.1  2003/12/12 00:52:55  carstenklapp
// New feature: Plugin to download page dumps of individual pages. In the
// future this could be used as a rudimentary way to sync pages between
// wikis.
// Internal changes: enhanced and renamed from the experimental
// _MailifyPage plugin.
//
// Revision 1.3  2003/11/16 00:11:25  carstenklapp
// Fixed previous Log comment interfering with PHP (sorry).
// Improved error handling.
//
// Revision 1.2  2003/11/15 23:37:51  carstenklapp
// Enhanced plugin to allow invocation with \<\?plugin-form PageDump\?\>.
//
// Revision 1.1  2003/02/20 18:03:04  carstenklapp
// New experimental WikiPlugin for internal use only by PhpWiki developers.
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
