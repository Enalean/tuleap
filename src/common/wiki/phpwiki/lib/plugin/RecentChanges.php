<?php
// -*-php-*-
rcs_id('$Id: RecentChanges.php,v 1.108 2005/04/01 16:09:35 rurban Exp $');
/**
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

class _RecentChanges_Formatter
{
    public $_absurls = false;

    public function __construct($rc_args)
    {
        $this->_args = $rc_args;
        $this->_diffargs = array('action' => 'diff');

        if ($rc_args['show_minor'] || !$rc_args['show_major']) {
            $this->_diffargs['previous'] = 'minor';
        }

        // PageHistoryPlugin doesn't have a 'daylist' arg.
        if (!isset($this->_args['daylist'])) {
            $this->_args['daylist'] = false;
        }
    }

    public function include_versions_in_URLs()
    {
        return (bool) $this->_args['show_all'];
    }

    public function date($rev)
    {
        global $WikiTheme;
        return $WikiTheme->getDay($rev->get('mtime'));
    }

    public function time($rev)
    {
        global $WikiTheme;
        return $WikiTheme->formatTime($rev->get('mtime'));
    }

    public function diffURL($rev)
    {
        $args = $this->_diffargs;
        if ($this->include_versions_in_URLs()) {
            $args['version'] = $rev->getVersion();
        }
        $page = $rev->getPage();
        return WikiURL($page->getName(), $args, $this->_absurls);
    }

    public function historyURL($rev)
    {
        $page = $rev->getPage();
        return WikiURL(
            $page,
            array('action' => _("PageHistory")),
            $this->_absurls
        );
    }

    public function pageURL($rev)
    {
        return WikiURL(
            $this->include_versions_in_URLs() ? $rev : $rev->getPage(),
            '',
            $this->_absurls
        );
    }

    public function authorHasPage($author)
    {
        global $WikiNameRegexp, $request;
        $dbi = $request->getDbh();
        return isWikiWord($author) && $dbi->isWikiPage($author);
    }

    public function authorURL($author)
    {
        return $this->authorHasPage() ? WikiURL($author) : false;
    }


    public function status($rev)
    {
        if ($rev->hasDefaultContents()) {
            return 'deleted';
        }
        $page = $rev->getPage();
        $prev = $page->getRevisionBefore($rev->getVersion());
        if ($prev->hasDefaultContents()) {
            return 'new';
        }
        return 'updated';
    }

    public function importance($rev)
    {
        return $rev->get('is_minor_edit') ? 'minor' : 'major';
    }

    public function summary($rev)
    {
        if (($summary = $rev->get('summary'))) {
            return $summary;
        }

        switch ($this->status($rev)) {
            case 'deleted':
                return _("Deleted");
            case 'new':
                return _("New page");
            default:
                return '';
        }
    }

    public function setValidators($most_recent_rev)
    {
        $rev = $most_recent_rev;
        $validators = array('RecentChanges-top' =>
                            array($rev->getPageName(), $rev->getVersion()),
                            '%mtime' => $rev->get('mtime'));
        global $request;
        $request->appendValidators($validators);
    }
}

class _RecentChanges_HtmlFormatter extends _RecentChanges_Formatter
{
    public function diffLink($rev)
    {
        global $WikiTheme;
        return $WikiTheme->makeButton(_("(diff)"), $this->diffURL($rev), 'wiki-rc-action');
    }

    public function historyLink($rev)
    {
        global $WikiTheme;
        return $WikiTheme->makeButton(_("(hist)"), $this->historyURL($rev), 'wiki-rc-action');
    }

    public function pageLink($rev, $link_text = false)
    {
        return WikiLink($this->include_versions_in_URLs() ? $rev : $rev->getPage(), 'auto', $link_text);
        /*
        $page = $rev->getPage();
        global $WikiTheme;
        if ($this->include_versions_in_URLs()) {
            $version = $rev->getVersion();
            if ($rev->isCurrent())
                $version = false;
            $exists = !$rev->hasDefaultContents();
        }
        else {
            $version = false;
            $cur = $page->getCurrentRevision();
            $exists = !$cur->hasDefaultContents();
        }
        if ($exists)
            return $WikiTheme->linkExistingWikiWord($page->getName(), $link_text, $version);
        else
            return $WikiTheme->linkUnknownWikiWord($page->getName(), $link_text);
        */
    }

    public function authorLink($rev)
    {
        $author = UserHelper::instance()->getDisplayNameFromUserName($rev->get('author'));
        if ($this->authorHasPage($author)) {
            return WikiLink($author);
        } else {
            return $author;
        }
    }

    public function summaryAsHTML($rev)
    {
        if (!($summary = $this->summary($rev))) {
            return '';
        }
        return  HTML::strong(
            array('class' => 'wiki-summary'),
            "[",
            TransformLinks($summary, $rev->get('markup'), $rev->getPageName()),
            "]"
        );
    }

    public function rss_icon()
    {
        global $request, $WikiTheme;

        $rss_url = $request->getURLtoSelf(array('format' => 'rss'));
        return HTML::small(
            array('style' => 'font-weight:normal;vertical-align:middle;'),
            $WikiTheme->makeButton("RSS", $rss_url, 'rssicon')
        );
    }
    public function rss2_icon()
    {
        global $request, $WikiTheme;

        $rss_url = $request->getURLtoSelf(array('format' => 'rss2'));
        return HTML::small(
            array('style' => 'font-weight:normal;vertical-align:middle;'),
            $WikiTheme->makeButton("RSS2", $rss_url, 'rssicon')
        );
    }

    public function pre_description()
    {
        extract($this->_args);
        // FIXME: say something about show_all.
        if ($show_major && $show_minor) {
            $edits = _("edits");
        } elseif ($show_major) {
            $edits = _("major edits");
        } else {
            $edits = _("minor edits");
        }
        if (isset($caption) and $caption == _("Recent Comments")) {
            $edits = _("comments");
        }

        if ($timespan = $days > 0) {
            if (intval($days) != $days) {
                $days = sprintf("%.1f", $days);
            }
        }
        $lmt = abs($limit);
        /**
         * Depending how this text is split up it can be tricky or
         * impossible to translate with good grammar. So the seperate
         * strings for 1 day and %s days are necessary in this case
         * for translating to multiple languages, due to differing
         * overlapping ideal word cutting points.
         *
         * en: day/days "The %d most recent %s [during (the past] day) are listed below."
         * de: 1 Tag    "Die %d jüngste %s [innerhalb (von des letzten] Tages) sind unten aufgelistet."
         * de: %s days  "Die %d jüngste %s [innerhalb (von] %s Tagen) sind unten aufgelistet."
         *
         * en: day/days "The %d most recent %s during [the past] (day) are listed below."
         * fr: 1 jour   "Les %d %s les plus récentes pendant [le dernier (d'une] jour) sont énumérées ci-dessous."
         * fr: %s jours "Les %d %s les plus récentes pendant [les derniers (%s] jours) sont énumérées ci-dessous."
         */
        if ($limit > 0) {
            if ($timespan) {
                if (intval($days) == 1) {
                    $desc = fmt(
                        "The %d most recent %s during the past day are listed below.",
                        $limit,
                        $edits
                    );
                } else {
                    $desc = fmt(
                        "The %d most recent %s during the past %s days are listed below.",
                        $limit,
                        $edits,
                        $days
                    );
                }
            } else {
                $desc = fmt(
                    "The %d most recent %s are listed below.",
                    $limit,
                    $edits
                );
            }
        } elseif ($limit < 0) {  //$limit < 0 means we want oldest pages
            if ($timespan) {
                if (intval($days) == 1) {
                    $desc = fmt(
                        "The %d oldest %s during the past day are listed below.",
                        $lmt,
                        $edits
                    );
                } else {
                    $desc = fmt(
                        "The %d oldest %s during the past %s days are listed below.",
                        $lmt,
                        $edits,
                        $days
                    );
                }
            } else {
                $desc = fmt(
                    "The %d oldest %s are listed below.",
                    $lmt,
                    $edits
                );
            }
        } else {
            if ($timespan) {
                if (intval($days) == 1) {
                    $desc = fmt(
                        "The most recent %s during the past day are listed below.",
                        $edits
                    );
                } else {
                    $desc = fmt(
                        "The most recent %s during the past %s days are listed below.",
                        $edits,
                        $days
                    );
                }
            } else {
                $desc = fmt("All %s are listed below.", $edits);
            }
        }
        return $desc;
    }

    public function description()
    {
        return HTML::p(false, $this->pre_description());
    }


    public function title()
    {
        extract($this->_args);
        return array($show_minor ? _("RecentEdits") : _("RecentChanges"),
                     ' ',
                     $this->rss_icon(), HTML::raw('&nbsp;'), $this->rss2_icon(),
                     $this->sidebar_link());
    }

    public function empty_message()
    {
        if (isset($this->_args['caption']) and $this->_args['caption'] == _("Recent Comments")) {
            return _("No comments found");
        } else {
            return _("No changes found");
        }
    }

    public function sidebar_link()
    {
        extract($this->_args);
        $pagetitle = $show_minor ? _("RecentEdits") : _("RecentChanges");

        global $request;
        $sidebarurl = WikiURL($pagetitle, array('format' => 'sidebar'), 'absurl');

        $addsidebarjsfunc =
            "function addPanel() {\n"
            . "    window.sidebar.addPanel (\"" . sprintf("%s - %s", WIKI_NAME, $pagetitle) . "\",\n"
            . "       \"$sidebarurl\",\"\");\n"
            . "}\n";
        $jsf = JavaScript($addsidebarjsfunc);

        global $WikiTheme;
        $sidebar_button = $WikiTheme->makeButton("sidebar", 'javascript:addPanel();', 'sidebaricon');
        $addsidebarjsclick = asXML(HTML::small(array('style' => 'font-weight:normal;vertical-align:middle;'), $sidebar_button));
        $jsc = JavaScript("if ((typeof window.sidebar == 'object') &&\n"
                                . "    (typeof window.sidebar.addPanel == 'function'))\n"
                                . "   {\n"
                                . "       document.write('$addsidebarjsclick');\n"
                                . "   }\n");
        return HTML(new RawXml("\n"), $jsf, new RawXml("\n"), $jsc);
    }

    public function format($changes)
    {
        include_once('lib/InlineParser.php');

        $html = HTML(HTML::h2(false, $this->title()));
        if (($desc = $this->description())) {
            $html->pushContent($desc);
        }

        if ($this->_args['daylist']) {
            $html->pushContent(new DayButtonBar($this->_args));
        }

        $last_date = '';
        $lines = false;
        $first = true;

        while ($rev = $changes->next()) {
            if (($date = $this->date($rev)) != $last_date) {
                if ($lines) {
                    $html->pushContent($lines);
                }
                $html->pushContent(HTML::h3($date));
                $lines = HTML::ul();
                $last_date = $date;
            }
            // enforce view permission
            if (mayAccessPage('view', $rev->_pagename)) {
                $lines->pushContent($this->format_revision($rev));

                if ($first) {
                    $this->setValidators($rev);
                }
                $first = false;
            }
        }
        if ($lines) {
            $html->pushContent($lines);
        }
        if ($first) {
            $html->pushContent(HTML::p(
                array('class' => 'rc-empty'),
                $this->empty_message()
            ));
        }

        return $html;
    }

    public function format_revision($rev)
    {
        $args = &$this->_args;

        $class = 'rc-' . $this->importance($rev);

        $time = $this->time($rev);
        if (! $rev->get('is_minor_edit')) {
            $time = HTML::strong(array('class' => 'pageinfo-majoredit'), $time);
        }

        $line = HTML::li(array('class' => $class));

        if ($args['difflinks']) {
            $line->pushContent($this->diffLink($rev), ' ');
        }

        if ($args['historylinks']) {
            $line->pushContent($this->historyLink($rev), ' ');
        }

        $line->pushContent(
            $this->pageLink($rev),
            ' ',
            $time,
            ' ',
            $this->summaryAsHTML($rev),
            ' ... ',
            $this->authorLink($rev)
        );
        return $line;
    }
}


class _RecentChanges_SideBarFormatter extends _RecentChanges_HtmlFormatter
{
    public function rss_icon()
    {
        //omit rssicon
    }
    public function rss2_icon()
    {
    }
    public function title()
    {
        //title click opens the normal RC or RE page in the main browser frame
        extract($this->_args);
        $titlelink = WikiLink($show_minor ? _("RecentEdits") : _("RecentChanges"));
        $titlelink->setAttr('target', '_content');
        return HTML($this->logo(), $titlelink);
    }
    public function logo()
    {
        //logo click opens the HomePage in the main browser frame
        global $WikiTheme;
        $img = HTML::img(array('src' => $WikiTheme->getImageURL('logo'),
                               'border' => 0,
                               'align' => 'right',
                               'style' => 'height:2.5ex'
                               ));
        $linkurl = WikiLink(HOME_PAGE, false, $img);
        $linkurl->setAttr('target', '_content');
        return $linkurl;
    }

    public function authorLink($rev)
    {
        $author = $rev->get('author');
        if ($this->authorHasPage($author)) {
            $linkurl = WikiLink($author);
            $linkurl->setAttr('target', '_content'); // way to do this using parent::authorLink ??
            return $linkurl;
        } else {
            return $author;
        }
    }

    public function diffLink($rev)
    {
        $linkurl = parent::diffLink($rev);
        $linkurl->setAttr('target', '_content');
        // FIXME: Smelly hack to get smaller diff buttons in sidebar
        $linkurl = new RawXml(str_replace('<img ', '<img style="height:2ex" ', asXML($linkurl)));
        return $linkurl;
    }
    public function historyLink($rev)
    {
        $linkurl = parent::historyLink($rev);
        $linkurl->setAttr('target', '_content');
        // FIXME: Smelly hack to get smaller history buttons in sidebar
        $linkurl = new RawXml(str_replace('<img ', '<img style="height:2ex" ', asXML($linkurl)));
        return $linkurl;
    }
    public function pageLink($rev, $link_text = false)
    {
        $linkurl = parent::pageLink($rev);
        $linkurl->setAttr('target', '_content');
        return $linkurl;
    }
    // Overriding summaryAsHTML, because there is no way yet to
    // return summary as transformed text with
    // links setAttr('target', '_content') in Mozilla sidebar.
    // So for now don't create clickable links inside summary
    // in the sidebar, or else they target the sidebar and not the
    // main content window.
    public function summaryAsHTML($rev)
    {
        if (!($summary = $this->summary($rev))) {
            return '';
        }
        return HTML::strong(
            array('class' => 'wiki-summary'),
            "[",
            /*TransformLinks(*/$summary, /* $rev->get('markup')),*/
            "]"
        );
    }


    public function format($changes)
    {
        $this->_args['daylist'] = false; //don't show day buttons in Mozilla sidebar
        $html = _RecentChanges_HtmlFormatter::format($changes);
        $html = HTML::div(array('class' => 'wikitext'), $html);
        global $request;
        $request->discardOutput();

        printf("<?xml version=\"1.0\" encoding=\"%s\"?>\n", $GLOBALS['charset']);
        printf('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"');
        printf('  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');
        printf('<html xmlns="http://www.w3.org/1999/xhtml">');

        printf("<head>\n");
        extract($this->_args);
        $title = WIKI_NAME . $show_minor ? _("RecentEdits") : _("RecentChanges");
        printf("<title>" . $title . "</title>\n");
        global $WikiTheme;
        $css = $WikiTheme->getCSS();
        $css->PrintXML();
        printf("</head>\n");

        printf("<body class=\"sidebar\">\n");
        $html->PrintXML();
        echo '<a href="http://www.feedvalidator.org/check.cgi?url=http://phpwiki.org/RecentChanges?format=rss"><img src="themes/default/buttons/valid-rss.png" alt="[Valid RSS]" title="Validate the RSS feed" width="44" height="15" /></a>';
        printf("\n</body>\n");
        printf("</html>\n");

        $request->finish(); // cut rest of page processing short
    }
}

class _RecentChanges_BoxFormatter extends _RecentChanges_HtmlFormatter
{
    public function rss_icon()
    {
    }
    public function rss2_icon()
    {
    }
    public function title()
    {
    }
    public function authorLink($rev)
    {
    }
    public function diffLink($rev)
    {
    }
    public function historyLink($rev)
    {
    }
    public function summaryAsHTML($rev)
    {
    }
    public function description()
    {
    }
    public function format($changes)
    {
        include_once('lib/InlineParser.php');
        $last_date = '';
        $first = true;
        $html = HTML();
        $counter = 1;
        $sp = HTML::Raw("\n&nbsp;&middot;&nbsp;");
        while ($rev = $changes->next()) {
            // enforce view permission
            if (mayAccessPage('view', $rev->_pagename)) {
                if ($link = $this->pageLink($rev)) { // some entries may be empty
                                       // (/Blog/.. interim pages)
                    $html->pushContent($sp, $link, HTML::br());
                }
                if ($first) {
                    $this->setValidators($rev);
                }
                $first = false;
            }
        }
        if ($first) {
            $html->pushContent(HTML::p(
                array('class' => 'rc-empty'),
                $this->empty_message()
            ));
        }
        return $html;
    }
}

class _RecentChanges_RssFormatter extends _RecentChanges_Formatter
{
    public $_absurls = true;

    public function time($rev)
    {
        return Iso8601DateTime($rev->get('mtime'));
    }

    public function pageURI($rev)
    {
        return WikiURL($rev, '', 'absurl');
    }

    public function format($changes)
    {
        include_once('lib/RssWriter.php');
        $rss = new RssWriter();

        $rss->channel($this->channel_properties());

        if (($props = $this->image_properties())) {
            $rss->image($props);
        }
        if (($props = $this->textinput_properties())) {
            $rss->textinput($props);
        }

        $first = true;
        while ($rev = $changes->next()) {
            // enforce view permission
            if (mayAccessPage('view', $rev->_pagename)) {
                $rss->addItem(
                    $this->item_properties($rev),
                    $this->pageURI($rev)
                );
                if ($first) {
                    $this->setValidators($rev);
                }
                $first = false;
            }
        }

        global $request;
        $request->discardOutput();
        $rss->finish();
        printf("\n<!-- Generated by PhpWiki-%s:\n%s-->\n", PHPWIKI_VERSION, $GLOBALS['RCS_IDS']);

        // Flush errors in comment, otherwise it's invalid XML.
        global $ErrorManager;
        if (($errors = $ErrorManager->getPostponedErrorsAsHTML())) {
            printf("\n<!-- PHP Warnings:\n%s-->\n", AsXML($errors));
        }

        $request->finish();     // NORETURN!!!!
    }

    public function image_properties()
    {
        global $WikiTheme;

        $img_url = AbsoluteURL($WikiTheme->getImageURL('logo'));
        if (!$img_url) {
            return false;
        }

        return array('title' => WIKI_NAME,
                     'link' => WikiURL(HOME_PAGE, false, 'absurl'),
                     'url' => $img_url);
    }

    public function textinput_properties()
    {
        return array('title' => _("Search"),
                     'description' => _("Title Search"),
                     'name' => 's',
                     'link' => WikiURL(_("TitleSearch"), false, 'absurl'));
    }

    public function channel_properties()
    {
        global $request;

        $rc_url = WikiURL($request->getArg('pagename'), false, 'absurl');
        return array('title' => WIKI_NAME,
                     'link' => $rc_url,
                     'description' => _("RecentChanges"),
                     'dc:date' => Iso8601DateTime(time()),
                     'dc:language' => $GLOBALS['LANG']);

        /* FIXME: other things one might like in <channel>:
         * sy:updateFrequency
         * sy:updatePeriod
         * sy:updateBase
         * dc:subject
         * dc:publisher
         * dc:language
         * dc:rights
         * rss091:language
         * rss091:managingEditor
         * rss091:webmaster
         * rss091:lastBuildDate
         * rss091:copyright
         */
    }

    public function item_properties($rev)
    {
        $page = $rev->getPage();
        $pagename = $page->getName();

        return array( 'title'           => SplitPagename($pagename),
                      'description'     => $this->summary($rev),
                      'link'            => $this->pageURL($rev),
                      'dc:date'         => $this->time($rev),
                      'dc:contributor'  => $rev->get('author'),
                      'wiki:version'    => $rev->getVersion(),
                      'wiki:importance' => $this->importance($rev),
                      'wiki:status'     => $this->status($rev),
                      'wiki:diff'       => $this->diffURL($rev),
                      'wiki:history'    => $this->historyURL($rev)
                      );
    }
}

/** explicit application/rss+xml Content-Type,
 * simplified xml structure (no namespace),
 * support for xml-rpc cloud registerProcedure (not yet)
 */
class _RecentChanges_Rss2Formatter extends _RecentChanges_RssFormatter
{

    public function format($changes)
    {
        include_once('lib/RssWriter2.php');
        $rss = new RssWriter2();

        $rss->channel($this->channel_properties());
        if (($props = $this->cloud_properties())) {
            $rss->cloud($props);
        }
        if (($props = $this->image_properties())) {
            $rss->image($props);
        }
        if (($props = $this->textinput_properties())) {
            $rss->textinput($props);
        }
        $first = true;
        while ($rev = $changes->next()) {
            // enforce view permission
            if (mayAccessPage('view', $rev->_pagename)) {
                $rss->addItem(
                    $this->item_properties($rev),
                    $this->pageURI($rev)
                );
                if ($first) {
                    $this->setValidators($rev);
                }
                $first = false;
            }
        }

        global $request;
        $request->discardOutput();
        $rss->finish();
        printf("\n<!-- Generated by PhpWiki-%s:\n%s-->\n", PHPWIKI_VERSION, $GLOBALS['RCS_IDS']);
        // Flush errors in comment, otherwise it's invalid XML.
        global $ErrorManager;
        if (($errors = $ErrorManager->getPostponedErrorsAsHTML())) {
            printf("\n<!-- PHP Warnings:\n%s-->\n", AsXML($errors));
        }

        $request->finish();     // NORETURN!!!!
    }

    public function channel_properties()
    {
        $chann_10 = parent::channel_properties();
        return array_merge(
            $chann_10,
            array('generator' => 'PhpWiki-' . PHPWIKI_VERSION,
                                 //<pubDate>Tue, 10 Jun 2003 04:00:00 GMT</pubDate>
                                 //<lastBuildDate>Tue, 10 Jun 2003 09:41:01 GMT</lastBuildDate>
                                 //<docs>http://blogs.law.harvard.edu/tech/rss</docs>
                                 'copyright' => COPYRIGHTPAGE_URL
            )
        );
    }

    public function cloud_properties()
    {
        return false;
    }
}

class NonDeletedRevisionIterator extends WikiDB_PageRevisionIterator
{
    /** Constructor
     *
     * @param $revisions object a WikiDB_PageRevisionIterator.
     */
    public function __construct($revisions, $check_current_revision = true)
    {
        $this->_revisions = $revisions;
        $this->_check_current_revision = $check_current_revision;
    }

    public function next()
    {
        while (($rev = $this->_revisions->next())) {
            if ($this->_check_current_revision) {
                $page = $rev->getPage();
                $check_rev = $page->getCurrentRevision();
            } else {
                $check_rev = $rev;
            }
            if (! $check_rev->hasDefaultContents()) {
                return $rev;
            }
        }
        $this->free();
        return false;
    }
}

class WikiPlugin_RecentChanges extends WikiPlugin
{
    public function getName()
    {
        return _("RecentChanges");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.108 $"
        );
    }

    public function managesValidators()
    {
        // Note that this is a bit of a fig.
        // We set validators based on the most recently changed page,
        // but this fails when the most-recent page is deleted.
        // (Consider that the Last-Modified time will decrease
        // when this happens.)

        // We might be better off, leaving this as false (and junking
        // the validator logic above) and just falling back to the
        // default behavior (handled by WikiPlugin) of just using
        // the WikiDB global timestamp as the mtime.

        // Nevertheless, for now, I leave this here, mostly as an
        // example for how to use appendValidators() and managesValidators().

        return true;
    }

    public function getDefaultArguments()
    {
        return array('days'         => 2,
                     'show_minor'   => false,
                     'show_major'   => true,
                     'show_all'     => false,
                     'show_deleted' => 'sometimes',
                     'limit'        => false,
                     'format'       => false,
                     'daylist'      => false,
                     'difflinks'    => true,
                     'historylinks' => false,
                     'caption'      => ''
                     );
    }

    public function getArgs($argstr, $request, $defaults = false)
    {
        if (!$defaults) {
            $defaults = $this->getDefaultArguments();
        }
        $args = WikiPlugin::getArgs($argstr, $request, $defaults);

        $action = $request->getArg('action');
        if ($action != 'browse' && ! $request->isActionPage($action)) {
            $args['format'] = false; // default -> HTML
        }

        if ($args['format'] == 'rss' && empty($args['limit'])) {
            $args['limit'] = 15; // Fix default value for RSS.
        }
        if ($args['format'] == 'rss2' && empty($args['limit'])) {
            $args['limit'] = 15; // Fix default value for RSS2.
        }

        if ($args['format'] == 'sidebar' && empty($args['limit'])) {
            $args['limit'] = 10; // Fix default value for sidebar.
        }

        return $args;
    }

    public function getMostRecentParams($args)
    {
        extract($args);

        $params = array('include_minor_revisions' => $show_minor,
                        'exclude_major_revisions' => !$show_major,
                        'include_all_revisions' => !empty($show_all));
        if ($limit != 0) {
            $params['limit'] = $limit;
        }

        if ($days > 0.0) {
            $params['since'] = time() - 24 * 3600 * $days;
        } elseif ($days < 0.0) {
            $params['since'] = 24 * 3600 * $days - time();
        }

        return $params;
    }

    public function getChanges($dbi, $args)
    {
        $changes = $dbi->mostRecent($this->getMostRecentParams($args));

        $show_deleted = $args['show_deleted'];
        if ($show_deleted == 'sometimes') {
            $show_deleted = $args['show_minor'];
        }

        if (!$show_deleted) {
            $changes = new NonDeletedRevisionIterator($changes, !$args['show_all']);
        }

        return $changes;
    }

    public function format($changes, $args)
    {
        global $WikiTheme;
        $format = $args['format'];

        $fmt_class = $WikiTheme->getFormatter('RecentChanges', $format);
        if (!$fmt_class) {
            if ($format == 'rss') {
                $fmt_class = '_RecentChanges_RssFormatter';
            } elseif ($format == 'rss2') {
                $fmt_class = '_RecentChanges_Rss2Formatter';
            } elseif ($format == 'rss091') {
                include_once "lib/RSSWriter091.php";
                $fmt_class = '_RecentChanges_RssFormatter091';
            } elseif ($format == 'sidebar') {
                $fmt_class = '_RecentChanges_SideBarFormatter';
            } elseif ($format == 'box') {
                $fmt_class = '_RecentChanges_BoxFormatter';
            } else {
                $fmt_class = '_RecentChanges_HtmlFormatter';
            }
        }

        $fmt = new $fmt_class($args);
        return $fmt->format($changes);
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);

        // HACKish: fix for SF bug #622784  (1000 years of RecentChanges ought
        // to be enough for anyone.)
        $args['days'] = min($args['days'], 365000);

        // Hack alert: format() is a NORETURN for rss formatters.
        return $this->format($this->getChanges($dbi, $args), $args);
    }

    // box is used to display a fixed-width, narrow version with common header.
    // just a numbered list of limit pagenames, without date.
    public function box($args = false, $request = false, $basepage = false)
    {
        if (!$request) {
            $request = $GLOBALS['request'];
        }
        if (!isset($args['limit'])) {
            $args['limit'] = 15;
        }
        $args['format'] = 'box';
        $args['show_minor'] = false;
        $args['show_major'] = true;
        $args['show_deleted'] = 'sometimes';
        $args['show_all'] = false;
        $args['days'] = 90;
        return $this->makeBox(
            WikiLink($this->getName(), '', SplitPagename($this->getName())),
            $this->format($this->getChanges($request->_dbi, $args), $args)
        );
    }
}


class DayButtonBar extends HtmlElement
{

    public function __construct($plugin_args)
    {
        parent::__construct('p', array('class' => 'wiki-rc-action'));

        // Display days selection buttons
        extract($plugin_args);

        // Custom caption
        if (! $caption) {
            if ($show_minor) {
                $caption = _("Show minor edits for:");
            } elseif ($show_all) {
                $caption = _("Show all changes for:");
            } else {
                $caption = _("Show changes for:");
            }
        }

        $this->pushContent($caption, ' ');

        global $WikiTheme;
        $sep = $WikiTheme->getButtonSeparator();

        $n = 0;
        foreach (explode(",", $daylist) as $days) {
            if ($n++) {
                $this->pushContent($sep);
            }
            $this->pushContent($this->_makeDayButton($days));
        }
    }

    public function _makeDayButton($days)
    {
        global $WikiTheme, $request;

        if ($days == 1) {
            $label = _("1 day");
        } elseif ($days < 1) {
            $label = "..."; //alldays
        } else {
            $label = sprintf(_("%s days"), abs($days));
        }

        $url = $request->getURLtoSelf(array('action' => $request->getArg('action'), 'days' => $days));

        return $WikiTheme->makeButton($label, $url, 'wiki-rc-action');
    }
}

// $Log: RecentChanges.php,v $
// Revision 1.108  2005/04/01 16:09:35  rurban
// fix defaults in RecentChanges plugins: e.g. invalid pagenames for PageHistory
//
// Revision 1.107  2005/02/04 13:45:28  rurban
// improve box layout a bit
//
// Revision 1.106  2005/02/02 19:39:10  rurban
// honor show_all=false
//
// Revision 1.105  2005/01/25 03:50:54  uckelman
// pre_description is a member function, so call with $this->.
//
// Revision 1.104  2005/01/24 23:15:16  uckelman
// The extra description for RelatedChanges was appearing in RecentChanges
// and PageHistory due to a bad test in _RecentChanges_HtmlFormatter. Fixed.
//
// Revision 1.103  2004/12/15 17:45:09  rurban
// fix box method
//
// Revision 1.102  2004/12/06 19:29:24  rurban
// simplify RSS: add RSS2 link (rss tag only, new content-type)
//
// Revision 1.101  2004/11/10 19:32:24  rurban
// * optimize increaseHitCount, esp. for mysql.
// * prepend dirs to the include_path (phpwiki_dir for faster searches)
// * Pear_DB version logic (awful but needed)
// * fix broken ADODB quote
// * _extract_page_data simplification
//
// Revision 1.100  2004/06/28 16:35:12  rurban
// prevent from shell commands
//
// Revision 1.99  2004/06/20 14:42:54  rurban
// various php5 fixes (still broken at blockparser)
//
// Revision 1.98  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.97  2004/06/03 18:58:27  rurban
// days links requires action=RelatedChanges arg
//
// Revision 1.96  2004/05/18 16:23:40  rurban
// rename split_pagename to SplitPagename
//
// Revision 1.95  2004/05/16 22:07:35  rurban
// check more config-default and predefined constants
// various PagePerm fixes:
//   fix default PagePerms, esp. edit and view for Bogo and Password users
//   implemented Creator and Owner
//   BOGOUSERS renamed to BOGOUSER
// fixed syntax errors in signin.tmpl
//
// Revision 1.94  2004/05/14 20:55:03  rurban
// simplified RecentComments
//
// Revision 1.93  2004/05/14 17:33:07  rurban
// new plugin RecentChanges
//
// Revision 1.92  2004/04/21 04:29:10  rurban
// Two convenient RecentChanges extensions
//   RelatedChanges (only links from current page)
//   RecentEdits (just change the default args)
//
// Revision 1.91  2004/04/19 18:27:46  rurban
// Prevent from some PHP5 warnings (ref args, no :: object init)
//   php5 runs now through, just one wrong XmlElement object init missing
// Removed unneccesary UpgradeUser lines
// Changed WikiLink to omit version if current (RecentChanges)
//
// Revision 1.90  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//
// Revision 1.89  2004/04/10 02:30:49  rurban
// Fixed gettext problem with VIRTUAL_PATH scripts (Windows only probably)
// Fixed "cannot setlocale..." (sf.net problem)
//
// Revision 1.88  2004/04/01 15:57:10  rurban
// simplified Sidebar theme: table, not absolute css positioning
// added the new box methods.
// remaining problems: large left margin, how to override _autosplitWikiWords in Template only
//
// Revision 1.87  2004/03/30 02:14:03  rurban
// fixed yet another Prefs bug
// added generic PearDb_iter
// $request->appendValidators no so strict as before
// added some box plugin methods
// PageList commalist for condensed output
//
// Revision 1.86  2004/03/12 13:31:43  rurban
// enforce PagePermissions, errormsg if not Admin
//
// Revision 1.85  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.84  2004/02/15 22:29:42  rurban
// revert premature performance fix
//
// Revision 1.83  2004/02/15 21:34:37  rurban
// PageList enhanced and improved.
// fixed new WikiAdmin... plugins
// editpage, Theme with exp. htmlarea framework
//   (htmlarea yet committed, this is really questionable)
// WikiUser... code with better session handling for prefs
// enhanced UserPreferences (again)
// RecentChanges for show_deleted: how should pages be deleted then?
//
// Revision 1.82  2004/01/25 03:58:43  rurban
// use stdlib:isWikiWord()
//
// Revision 1.81  2003/11/28 21:06:31  carstenklapp
// Enhancement: Mozilla RecentChanges sidebar now defaults to 10 changes
// instead of 1. Make diff buttons smaller with css. Added description
// line back in at the top.
//
// Revision 1.80  2003/11/27 15:17:01  carstenklapp
// Theme & appearance tweaks: Converted Mozilla sidebar link into a Theme
// button, to allow an image button for it to be added to Themes. Output
// RSS button in small text size when theme has no button image.
//
// Revision 1.79  2003/04/29 14:34:20  dairiki
// Bug fix: "add sidebar" link didn't work when USE_PATH_INFO was false.
//
// Revision 1.78  2003/03/04 01:55:05  dairiki
// Fix to ensure absolute URL for logo in RSS recent changes.
//
// Revision 1.77  2003/02/27 23:23:38  dairiki
// Fix my breakage of CSS and sidebar RecentChanges output.
//
// Revision 1.76  2003/02/27 22:48:44  dairiki
// Fixes invalid HTML generated by PageHistory plugin.
//
// (<noscript> is block-level and not allowed within <p>.)
//
// Revision 1.75  2003/02/22 21:39:05  dairiki
// Hackish fix for SF bug #622784.
//
// (The root of the problem is clearly a PHP bug.)
//
// Revision 1.74  2003/02/21 22:52:21  dairiki
// Make sure to interpret relative links (like [/Subpage]) in summary
// relative to correct basepage.
//
// Revision 1.73  2003/02/21 04:12:06  dairiki
// Minor fixes for new cached markup.
//
// Revision 1.72  2003/02/17 02:19:01  dairiki
// Fix so that PageHistory will work when the current revision
// of a page has been "deleted".
//
// Revision 1.71  2003/02/16 20:04:48  dairiki
// Refactor the HTTP validator generation/checking code.
//
// This also fixes a number of bugs with yesterdays validator mods.
//
// Revision 1.70  2003/02/16 05:09:43  dairiki
// Starting to fix handling of the HTTP validator headers, Last-Modified,
// and ETag.
//
// Last-Modified was being set incorrectly (but only when DEBUG was not
// defined!)  Setting a Last-Modified without setting an appropriate
// Expires: and/or Cache-Control: header results in browsers caching
// the page unconditionally (for a certain period of time).
// This is generally bad, since it means people don't see updated
// page contents right away --- this is particularly confusing to
// the people who are editing pages since their edits don't show up
// next time they browse the page.
//
// Now, we don't allow caching of pages without revalidation
// (via the If-Modified-Since and/or If-None-Match request headers.)
// (You can allow caching by defining CACHE_CONTROL_MAX_AGE to an
// appropriate value in index.php, but I advise against it.)
//
// Problems:
//
//   o Even when request is aborted due to the content not being
//     modified, we currently still do almost all the work involved
//     in producing the page.  So the only real savings from all
//     this logic is in network bandwidth.
//
//   o Plugins which produce "dynamic" output need to be inspected
//     and made to call $request->addToETag() and
//     $request->setModificationTime() appropriately, otherwise the
//     page can change without the change being detected.
//     This leads to stale pages in cache again...
//
// Revision 1.69  2003/01/18 22:01:43  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
