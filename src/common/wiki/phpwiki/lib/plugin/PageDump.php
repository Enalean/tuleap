<?php // -*-php-*-
rcs_id('$Id$');
/**
 * PhpWikiPlugin for PhpWiki developers to generate single page dumps
 * for checking into cvs, or for users or the admin to produce a
 * downloadable page dump of a single page.
 * 
 * This plugin will also be useful to (semi-)automatically sync pages
 * directly between two wikis. First the LoadFile function of
 * PhpWikiAdministration needs to be updated to handle URLs again, and
 * add loading capability from InterWiki addresses.
 *
 * TODO: What about multiple revisions in one file? comments/summary
 * field? quoted-printable?
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
 */

class WikiPlugin_PageDump
extends WikiPlugin
{
    var $MessageId;

    function getName() {
        return _("PageDump");
    }
    function getDescription() {
        return _("View a single page dump online.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
        return array('s'    => false,
                     'page' => '[pagename]',
                     //'encoding' => 'binary', // 'binary', 'quoted-printable'
                     'format' => false, // 'normal', 'forcvs'
                     // display within WikiPage or give a downloadable
                     // raw pgsrc?
                     'download' => false);
    }

    function run($dbi, $argstr, &$request, $basepage) {
        extract($this->getArgs($argstr, $request));
        // allow plugin-form
        if (!empty($s))
            $page = $s;
        if (!$page)
            return '';
        if (! $dbi->isWikiPage($page))
            return fmt("Page %s not found.",
                       WikiLink($page, 'unknown'));

        $p = $dbi->getPage($page);
        include_once("lib/loadsave.php");
        $mailified = MailifyPage($p);

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
        if ($format == 'forcvs')
            $this->fixup_headers_forcvs($mailified);
        else
            $this->fixup_headers($mailified);

        if ($download) {
            $request->discardOutput(); // Hijack the http request from PhpWiki.
            ob_end_clean(); // clean up after hijacking $request
            //ob_end_flush(); //debugging
            Header("Content-disposition: attachment; filename=\""
                   . FilenameForPage($page) . "\"");
            // TODO: Read charset from generated page itself.
            // Inconsequential at the moment, since loadsave.php
            // presently always assumes CHARSET.
            Header("Content-Type: text/plain; name=\""
                   . FilenameForPage($page) . "\"; charset=\"" . $GLOBALS['charset']
                   . "\"");
            $request->checkValidators();
            // let $request provide last modifed & etag
            Header("Content-Id: <" . $this->MessageId . ">");
            // be nice to http keepalive~s
            Header("Content-Length: " . strlen($mailified));

            // Here comes our prepared mime file
            echo $mailified;
            exit; // noreturn! php exits.
            return;
        }
        // We are displaing inline preview in a WikiPage, so wrap the
        // text if it is too long--unless quoted-printable (TODO).
        $mailified = wordwrap($mailified, 70);

        // fixme: what about when not using VIRTUAL_PATH?
        $dlcvs = Button(array('page' => $page,
                              'format'=> 'forcvs',
                              'download'=> true),
                        _("Download for CVS"),
                        $this->getName(), //fixme: $request->getPostUrl??
                        'wikiadmin');
        $dl = Button(array('page' => $page,
                           'download'=> true),
                     _("Download for backup"),
                     $this->getName(), //fixme: $request->getPostUrl??
                     'wikiadmin');

        $h2 = HTML::h2(fmt("Preview: Page dump of %s",
                           WikiLink($page, 'auto')));
        if ($format == 'forcvs') {
            $desc = _("(formatted for PhpWiki developers, not for backing up)");
            $altpreviewbutton = Button(array('page' => $page),
                                       _("Preview as backup format"),
                                       $this->getName(), //fixme: $request->getPostUrl??
                                       'wikiadmin');
        }
        else {
            $desc = _("(formatted for backing up)");
            $altpreviewbutton = Button(array('page' => $page,
                                             'format'=> 'forcvs'),
                                       _("Preview as developer format"),
                                       $this->getName(), //fixme: $request->getPostUrl??
                                       'wikiadmin');
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

        global $Theme;
        if (!$Sep = $Theme->getButtonSeparator())
            $Sep = " ";

        return HTML($h2, HTML::em($desc),
                    HTML::pre($mailified),
                    $altpreviewbutton,
                    HTML::div(array('class' => 'errors'),
                              HTML::strong(_("Warning:")),
                              " ", $warning),
                    $dl, $Sep, $dlcvs
                    );
    }

    // function handle_plugin_args_cruft(&$argstr, &$args) {
    // }

    function generateMessageId($mailified) {
        $array = explode("\n", $mailified);
        // Extract lastmodifed from mailified document for Content-Id
        // and/or Message-Id header, NOT from DB (page could have been
        // edited by someone else since we started).
        $m1 = preg_grep("/^\s+lastmodified\=(.*);/", $array);
        $m1 = array_values($m1); //reset resulting keys
        unset($array);
        $m2 = preg_split("/(^\s+lastmodified\=)|(;)/", $m1[0], 2,
                         PREG_SPLIT_NO_EMPTY);

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
            . "-" . $m2[0] . (string)date("T")
            //. "-". rawurlencode(WIKI_NAME.":" . $request->getURLtoSelf())
            . "-". rawurlencode(WIKI_NAME.":" . $this->pagename)
            . "@". rawurlencode(SERVER_NAME);
    }

    function fixup_headers(&$mailified) {
        $return = explode("\n", $mailified);

        // Leave message intact for backing up, just add Message-Id header before transmitting.
        $item_to_insert = "Message-Id: <" . $this->MessageId .">";
        $insert_into_key_position = 2;
        $returnval_ignored = array_splice($return,
                                          $insert_into_key_position,
                                          0, $item_to_insert);

        $mailified = implode("\n", array_values($return));
    }

    function fixup_headers_forcvs(&$mailified) {
        $array = explode("\n", $mailified);

        // Massage headers to prepare for developer checkin to CVS.
        $item_to_insert = "X-Rcs-Id: \$Id\$";
        $insert_into_key_position = 2;
        $returnval_ignored = array_splice($array,
                                          $insert_into_key_position,
                                          0, $item_to_insert);

        $item_to_insert = "  pgsrc_version=\"2 \$Revision\$\";";
        $insert_into_key_position = 5;
        $returnval_ignored = array_splice($array,
                                          $insert_into_key_position,
                                          0, $item_to_insert);
        /*
            Strip out all this junk:
            author=MeMe;
            version=74;
            lastmodified=1041561552;
            author_id=127.0.0.1;
            hits=146;
        */
        $killme = array("author", "version", "lastmodified",
                        "author_id", "hits");
        // UltraNasty, fixme:
        foreach ($killme as $pattern) {
            $array = preg_replace("/^\s\s$pattern\=.*;/",
                                  /*$replacement =*/"zzzjunk", $array);
        }
        // remove deleted values from array
        for ($i = 0; $i < count($array); $i++ ) {
            if(trim($array[$i]) != "zzzjunk") { //nasty, fixme
            //trigger_error("'$array[$i]'");//debugging
                $return[] =$array[$i];
            }
        }

        $mailified = implode("\n", $return);
    }
};

// $Log$
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
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
