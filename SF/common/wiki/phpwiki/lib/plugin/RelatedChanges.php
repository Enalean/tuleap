<?php // -*-php-*-
rcs_id('$Id$');

/**
 * List of changes on all pages which are linked to from this page.
 * This is good usage for an action button, similar to LikePages.
 */

require_once("lib/plugin/RecentChanges.php");

class WikiPlugin_RelatedChanges
extends WikiPlugin_RecentChanges
{
    function getName () {
        return _("RecentEdits");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
    	//php-4.0.4pl1 breaks at the parent:: line even if the 
    	// code doesn't reach this line
        //if (!check_php_version(4,0,6))
        $args = WikiPlugin_RecentChanges::getDefaultArguments();
        //else $args = parent::getDefaultArguments();
        $args['page'] = '[pagename]';
        $args['show_minor'] = true;
        $args['show_all'] = true;
        $args['caption'] = _("Related Changes");
        return $args;
    }

    function getChanges ($dbi, $args) {
        $changes = $dbi->mostRecent($this->getMostRecentParams($args));

        $show_deleted = $args['show_deleted'];
        if ($show_deleted == 'sometimes')
            $show_deleted = $args['show_minor'];
        if (!$show_deleted)
            $changes = new NonDeletedRevisionIterator($changes, !$args['show_all']);

        // sort out pages not linked from our page
        $changes = new RelatedChangesRevisionIterator($changes, $dbi, $args['page']);
        return $changes;
    }

    // box is used to display a fixed-width, narrow version with common header.
    // just a numbered list of limit pagenames, without date.
    function box($args = false, $request = false, $basepage = false) {
        if (!$request) $request =& $GLOBALS['request'];
        if (!isset($args['limit'])) $args['limit'] = 15;
        $args['format'] = 'box';
        $args['show_minor'] = false;
        $args['show_major'] = true;
        $args['show_deleted'] = false;
        $args['show_all'] = false;
        $args['days'] = 90;
        return $this->makeBox(WikiLink(_("RelatedChanges"),'',_("Related Changes")),
                              $this->format($this->getChanges($request->_dbi, $args), $args));
    }
}

/**
 * list of pages which are linked from the current page.
 * i.e. sort out all non-linked pages.
 */
class RelatedChangesRevisionIterator extends WikiDB_PageRevisionIterator
{
    function RelatedChangesRevisionIterator ($revisions, &$dbi, $pagename) {
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

    function next () {
        while (($rev = $this->_revisions->next())) {
            if (isset($this->_links[$rev->_pagename]))
                return $rev;
        }
        $this->free();
        return false;
    }
}

// $Log$
// Revision 1.1  2005/04/12 13:33:33  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.2  2004/05/08 14:06:13  rurban
// new support for inlined image attributes: [image.jpg size=50x30 align=right]
// minor stability and portability fixes
//
// Revision 1.1  2004/04/21 04:29:10  rurban
// Two convenient RecentChanges extensions
//   RelatedChanges (only links from current page)
//   RecentEdits (just change the default args)
//

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>