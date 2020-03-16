<?php
// -*-php-*-
rcs_id('$Id: PageListColumns.php,v 1.10 2005/09/30 18:41:39 uckelman Exp $');

/*
 Copyright 2004 Mike Cassano

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

/**
 * wikilens specific Custom pagelist columns
 *
 * Rationale: Certain themes should be able to extend the predefined list
 *  of pagelist types. E.g. certain plugins, like MostPopular might use
 *  info=pagename,hits,rating
 *  which displays the rating column whenever the wikilens theme is active.
 *  Similarly as in certain plugins, like WikiAdminRename or _WikiTranslation
 */

require_once('lib/PageList.php');

/**
 * Column representing the number of backlinks to the page.
 * Perhaps this number should be made a 'field' of a page, in
 * which case this column type would not be necessary.
 * See also info=numbacklinks,numpagelinks at plugin/ListPages.php:_PageList_Column_ListPages_count
 * and info=count at plugin/BackLinks.php:PageList_Column_BackLinks_count
 */
class _PageList_Column_numbacklinks extends _PageList_Column_custom
{
    public function _getValue($page_handle, &$revision_handle)
    {
        $theIter = $page_handle->getBackLinks();
        return $theIter->count();
    }

    public function _getSortableValue($page_handle, &$revision_handle)
    {
        return $this->_getValue($page_handle, $revision_handle);
    }
}

class _PageList_Column_coagreement extends _PageList_Column_custom
{
    public function __construct($params)
    {
        $this->_pagelist = $params[3];
        $this->_PageList_Column($params[0], $params[1], $params[2]);
        $this->_selectedBuddies = $this->_pagelist->getOption('selectedBuddies');
    }

    public function _getValue($page_handle, &$revision_handle)
    {
        global $request;

        $pagename = $page_handle->getName();

        $active_user = $request->getUser();
        $active_userId = $active_user->getId();
        $dbi = $request->getDbh();
        $p = CoAgreement($dbi, $pagename, $this->_selectedBuddies, $active_userId);
        if ($p == 1) {
            $p = "yes";
        } elseif ($p == 0) {
            $p = "unsure";
        } elseif ($p == -1) {
            $p = "no";
        } else {
            $p = "error";
        }
        //FIXME: $WikiTheme->getImageURL()
        return HTML::img(array('src' => "../images/" . $p . ".gif"));
    }
}

class _PageList_Column_minmisery extends _PageList_Column_custom
{
    public function __construct($params)
    {
        $this->_pagelist = $params[3];
        $this->_PageList_Column($params[0], $params[1], $params[2]);
        $this->_selectedBuddies = $this->_pagelist->getOption('selectedBuddies');
    }

    public function _getValue($page_handle, &$revision_handle)
    {
        global $request;

        $pagename = $page_handle->getName();

        $active_user = $request->getUser();
        $active_userId = $active_user->getId();
        $dbi = $request->getDbh();
        $p = MinMisery($dbi, $pagename, $this->_selectedBuddies, $active_userId);
           $imgFix = floor($p * 2) / 2;
        //FIXME: $WikiTheme->getImageURL()
        return HTML::img(array('src' => "../images/" . $imgFix . ".png"));
    }
}

// register custom PageList type
global $WikiTheme;
$WikiTheme->addPageListColumn(array
    (
    'numbacklinks'
    => array('_PageList_Column_numbacklinks','custom:numbacklinks', _("# things"), false),
    'coagreement'
    => array('_PageList_Column_coagreement','custom:coagreement', _("Go?"), 'center'),
    'minmisery'
    => array('_PageList_Column_minmisery','custom:minmisery', _("MinMisery"), 'center'),
));

// $Log: PageListColumns.php,v $
// Revision 1.10  2005/09/30 18:41:39  uckelman
// Fixed more passes-by-reference.
//
// Revision 1.9  2004/12/26 17:08:36  rurban
// php5 fixes: case-sensitivity, no & new
//
// Revision 1.8  2004/11/06 17:13:17  rurban
// init is easier this way: no ->init(), pass params instead
//
// Revision 1.7  2004/11/01 10:43:59  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.6  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.5  2004/07/07 15:01:44  dfrankow
// Allow ratingvalue, ratingwidget, prediction, numbacklinks columns to be sortable
//
// Revision 1.4  2004/06/30 20:12:09  dfrankow
// + Change numbacklinks function to use existing core functions.
//   It's slower, but it'll work.
//
// + Change ratingvalue column to get its specific user as column 5 (index 4).
//
// + ratingwidget column uses WikiPlugin_RateIt's RatingWidgetHtml
//
// Revision 1.3  2004/06/21 17:01:41  rurban
// fix typo and rating method call
//
// Revision 1.2  2004/06/21 16:22:32  rurban
// add DEFAULT_DUMP_DIR and HTML_DUMP_DIR constants, for easier cmdline dumps,
// fixed dumping buttons locally (images/buttons/),
// support pages arg for dumphtml,
// optional directory arg for dumpserial + dumphtml,
// fix a AllPages warning,
// show dump warnings/errors on DEBUG,
// don't warn just ignore on wikilens pagelist columns, if not loaded.
// RateIt pagelist column is called "rating", not "ratingwidget" (Dan?)
//
// Revision 1.1  2004/06/18 14:42:17  rurban
// added wikilens libs (not yet merged good enough, some work for DanFr)
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
