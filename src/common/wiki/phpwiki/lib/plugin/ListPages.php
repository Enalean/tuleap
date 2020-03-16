<?php
// -*-php-*-
rcs_id('$Id: ListPages.php,v 1.10 2005/09/27 17:34:19 rurban Exp $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

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

require_once('lib/PageList.php');

/**
 * ListPages - List pages that are explicitly given as the pages argument.
 *
 * Mainly used to see some ratings and recommendations.
 * But also possible to list some Categories or Users, or as generic
 * frontend for plugin-list page lists.
 *
 * @author: Dan Frankowski
 */
class WikiPlugin_ListPages extends WikiPlugin
{
    public function getName()
    {
        return _("ListPages");
    }

    public function getDescription()
    {
        return _("List pages that are explicitly given as the pages argument.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.10 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array('pages'    => false,
                   //'exclude'  => false,
                   'info'     => 'pagename',
                   'dimension' => 0,
            )
        );
    }

    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // additional info args:
    //   top3recs      : recommendations
    //   numbacklinks  : number of backlinks (links to the given page)
    //   numpagelinks  : number of forward links (links at the given page)

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);
        // If the ratings table does not exist, or on dba it will break otherwise.
        // Check if Theme isa 'wikilens'
        if ($info == 'pagename' and isa($GLOBALS['WikiTheme'], 'wikilens')) {
            $info .= ",top3recs";
        }
        if ($info) {
            $info = preg_split('/,/D', $info);
        } else {
            $info = array();
        }

        if (in_array('top3recs', $info)) {
            require_once('lib/wikilens/Buddy.php');
            require_once('lib/wikilens/PageListColumns.php');

            $active_user   = $request->getUser();
            $active_userid = $active_user->_userid;

            // if userids is null or empty, fill it with just the active user
            if (!isset($userids) || !is_array($userids) || !count($userids)) {
                // TKL: moved getBuddies call inside if statement because it was
                // causing the userids[] parameter to be ignored
                if (is_string($active_userid)
                and strlen($active_userid)
                and $active_user->isSignedIn()) {
                    $userids = getBuddies($active_userid, $dbi);
                } else {
                    $userids = array();
                    // XXX: this wipes out the category caption...
                    $caption = _("You must be logged in to view ratings.");
                }
            }

            // find out which users we should show ratings for
            $options = array('dimension' => $dimension,
                             'users' => array());
            $args = array_merge($options, $args);
        }
        if (empty($pages) and $pages != '0') {
            return '';
        }

        if (in_array('numbacklinks', $info)) {
            $args['types']['numbacklinks'] = new _PageList_Column_ListPages_count('numbacklinks', _("#"), true);
        }
        if (in_array('numpagelinks', $info)) {
            $args['types']['numpagelinks'] = new _PageList_Column_ListPages_count('numpagelinks', _("#"));
        }

        $pagelist = new PageList($info, $exclude, $args);
        $pages_array = is_string($pages) ? explodePageList($pages) : (is_array($pages) ? $pages : array());
        $pagelist->addPageList($pages_array);
        return $pagelist;
    }
}

// how many back-/forwardlinks for this page
class _PageList_Column_ListPages_count extends _PageList_Column
{
    public function __construct($field, $display, $backwards = false)
    {
        $this->_direction = $backwards;
        return parent::__construct($field, $display, 'center');
    }
    public function _getValue($page, &$revision_handle)
    {
        $iter = $page->getLinks($this->_direction);
        $count = $iter->count();
        return $count;
    }
}

// $Log: ListPages.php,v $
// Revision 1.10  2005/09/27 17:34:19  rurban
// fix ListPages for non-SQL backends. Add top3recs as default only if ratings are available
//
// Revision 1.9  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.8  2004/10/14 19:19:34  rurban
// loadsave: check if the dumped file will be accessible from outside.
// and some other minor fixes. (cvsclient native not yet ready)
//
// Revision 1.7  2004/09/25 16:33:52  rurban
// add support for all PageList options
//
// Revision 1.6  2004/09/14 10:33:39  rurban
// simplify exclude, add numbacklinks+numpagelinks
//
// Revision 1.5  2004/09/06 08:37:31  rurban
// plugin-list support for pages and exclude args
//
// Revision 1.4  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.3  2004/06/28 18:58:18  rurban
// fixed another pass-by-reference
//
// Revision 1.2  2004/06/18 14:42:17  rurban
// added wikilens libs (not yet merged good enough, some work for DanFr)
//
// Revision 1.1  2004/06/08 13:49:43  rurban
// List pages that are explicitly given as the pages argument, by DanFr
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
