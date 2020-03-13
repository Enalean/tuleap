<?php
// -*-php-*-
rcs_id('$Id: WikiForum.php,v 1.3 2004/06/14 11:31:39 rurban Exp $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

 This file is (not yet) part of PhpWiki.

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
 * This plugin handles a threaded list of comments/news associated with a
 * particular page (one page per topic) and provides an input form for
 * adding a new message.
 *
 *   <?plugin WikiForum ?>
 *
 * To provide information for the MainForum page (CategoryForum)
 * summary output mode is possible.
 *
 *   <?plugin WikiForum page=SubTopic1 mode=summary info=title,numposts,ctime,author ?>
 *   <?plugin WikiForum page=SubTopic2 mode=summary info=title,numposts,ctime,author ?>
 *
 * TODO: For admin user, put checkboxes beside comments to allow for bulk removal.
 * threaded identation for level of reply
 *   (probably no date, just index as pagetitle)
 * reply link from within message (?mode=add)
 * layout
 * pagetype: header: link to parent, no redirects,
 *
 * @author: Reini Urban
 */

include_once("lib/plugin/WikiBlog.php");

class WikiPlugin_WikiForum extends WikiPlugin_WikiBlog
{
    public function getName()
    {
        return _("WikiForum");
    }

    public function getDescription()
    {
        return _("Handles threaded topics with comments/news and provide a input form");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.3 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('pagename'   => '[pagename]',
                     'order'      => 'normal',   // oldest first
                     'mode'       => 'show,add', // 'summary',
                     'info'       => '',
                     'noheader'   => false
                    );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        if (!$args['pagename']) {
            return $this->error(_("No pagename specified"));
        }

        // Get our form args.
        $forum = $request->getArg('forum');
        $request->setArg('forum', false);

        if ($request->isPost() and !empty($forum['add'])) {
            return $this->add($request, $forum, 'wikiforum');
        }

        // Now we display previous comments and/or provide entry box
        // for new comments
        $html = HTML();
        foreach (explode(',', $args['mode']) as $show) {
            if (!empty($seen[$show])) {
                continue;
            }
            $seen[$show] = 1;

            switch ($show) {
                case 'summary': // main page: list of all titles
                    $html->pushContent($this->showTopics($request, $args));
                    break;
                case 'show':    // list of all contents
                    $html->pushContent($this->showAll($request, $args, 'wikiforum'));
                    break;
                case 'add':     // add to or create a new thread
                    $html->pushContent($this->showForm($request, $args, 'forumadd'));
                    break;
                default:
                    return $this->error(sprintf("Bad mode ('%s')", $show));
            }
        }
        // FIXME: on empty showTopics() and mode!=add and mode!=summary provide a showForm() here.
        return $html;
    }

    // Table of titles(subpages) without content
    // TODO: use $args['info']
    public function showTopics($request, $args)
    {
        global $WikiTheme;

        $dbi = $request->getDbh();
        $topics = $this->findBlogs($dbi, $args['pagename'], 'wikiforum');
        $html = HTML::table(array('border' => 0));
        $row = HTML::tr(
            HTML::th('title'),
            HTML::th('last post'),
            HTML::th('author')
        );
        $html->pushContent($row);
        foreach ($topics as $rev) {
            //TODO: get numposts, number of replies
            $meta = $rev->get('wikiforum');
            // format as list, not as wikiforum content
            $page = new WikiPageName($rev, $args['pagename']);
            $row = HTML::tr(
                HTML::td(WikiLink($page, 'if_known', $rev->get('summary'))),
                HTML::td($WikiTheme->formatDateTime($meta['ctime'])),
                HTML::td(WikiLink($meta['creator'], 'if_known'))
            );
            $html->pushContent($row);
        }
        return $html;
    }
}

// $Log: WikiForum.php,v $
// Revision 1.3  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.2  2004/04/19 18:27:46  rurban
// Prevent from some PHP5 warnings (ref args, no :: object init)
//   php5 runs now through, just one wrong XmlElement object init missing
// Removed unneccesary UpgradeUser lines
// Changed WikiLink to omit version if current (RecentChanges)
//
// Revision 1.1  2004/04/18 05:43:12  rurban
// .
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
