<?php
// -*-php-*-
rcs_id('$Id: RelatedChanges.php,v 1.5 2005/01/25 03:50:54 uckelman Exp $');

/**
 * List of changes on all pages which are linked to from this page.
 * This is good usage for an action button, similar to LikePages.
 *
 * DONE: days links requires action=RelatedChanges arg
 */

require_once("lib/plugin/RecentChanges.php");

class _RelatedChanges_HtmlFormatter extends _RecentChanges_HtmlFormatter
{
    public function description()
    {
        return HTML::p(
            false,
            $this->pre_description(),
            fmt(" (to pages linked from \"%s\")", $this->_args['page'])
        );
    }
}


class WikiPlugin_RelatedChanges extends WikiPlugin_RecentChanges
{
    public function getName()
    {
        return _("RecentEdits");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.5 $"
        );
    }

    public function getDefaultArguments()
    {
        $args = WikiPlugin_RecentChanges::getDefaultArguments();
        $args['page'] = '[pagename]';
        $args['show_minor'] = true;
        $args['show_all'] = true;
        $args['caption'] = _("Related Changes");
        return $args;
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

        // sort out pages not linked from our page
        $changes = new RelatedChangesRevisionIterator($changes, $dbi, $args['page']);
        return $changes;
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
        $args['show_deleted'] = false;
        $args['show_all'] = false;
        $args['days'] = 90;
        return $this->makeBox(
            WikiLink(_("RelatedChanges"), '', _("Related Changes")),
            $this->format($this->getChanges($request->_dbi, $args), $args)
        );
    }

    public function format($changes, $args)
    {
        global $WikiTheme;
        $format = $args['format'];

        $fmt_class = $WikiTheme->getFormatter('RelatedChanges', $format);
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
                $fmt_class = '_RelatedChanges_HtmlFormatter';
            }
        }

        $fmt = new $fmt_class($args);
        return $fmt->format($changes);
    }
}

/**
 * list of pages which are linked from the current page.
 * i.e. sort out all non-linked pages.
 */
class RelatedChangesRevisionIterator extends WikiDB_PageRevisionIterator
{
    public function __construct($revisions, &$dbi, $pagename)
    {
        $this->_revisions = $revisions;
        $this->_wikidb = $dbi;
        $page = $dbi->getPage($pagename);
        $links = $page->getLinks();
        $this->_links = array();
        while ($linked_page = $links->next()) {
            $this->_links[$linked_page->_pagename] = 1;
        }
        $links->free();
    }

    public function next()
    {
        while (($rev = $this->_revisions->next())) {
            if (isset($this->_links[$rev->_pagename])) {
                return $rev;
            }
        }
        $this->free();
        return false;
    }
}

// $Log: RelatedChanges.php,v $
// Revision 1.5  2005/01/25 03:50:54  uckelman
// pre_description is a member function, so call with $this->.
//
// Revision 1.4  2005/01/24 23:15:27  uckelman
// The extra description for RelatedChanges was appearing in RecentChanges
// and PageHistory due to a bad test in _RecentChanges_HtmlFormatter. Fixed.
//
// Revision 1.3  2004/06/03 18:58:27  rurban
// days links requires action=RelatedChanges arg
//
// Revision 1.2  2004/05/08 14:06:13  rurban
// new support for inlined image attributes: [image.jpg size=50x30 align=right]
// minor stability and portability fixes
//
// Revision 1.1  2004/04/21 04:29:10  rurban
// Two convenient RecentChanges extensions
//   RelatedChanges (only links from current page)
//   RecentEdits (just change the default args)
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
