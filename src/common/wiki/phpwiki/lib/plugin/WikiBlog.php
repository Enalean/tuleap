<?php
// -*-php-*-
rcs_id('$Id: WikiBlog.php,v 1.23 2005/10/29 09:06:37 rurban Exp $');
/*
 Copyright 2002, 2003 $ThePhpWikiProgrammingTeam

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
 * @author: MichaelVanDam, major refactor by JeffDairiki
 */

require_once('lib/TextSearchQuery.php');

/**
 * This plugin shows 'blogs' (comments/news) associated with a
 * particular page and provides an input form for adding a new blog.
 *
 * Now it is also the base class for all attachable pagetypes:
 *    wikiblog, comment and wikiforum
 *
 * HINTS/COMMENTS:
 *
 * To have comments show up on a separate page:
 * On TopPage, use
 *   <?plugin WikiBlog mode=add?>
 * Create TopPage/Comments with:
 *   <?plugin WikiBlog page=TopPage mode=show?>
 *
 * TODO:
 *
 * It also works as an action-page if you create a page called 'WikiBlog'
 * containing this plugin.  This allows adding comments to any page
 * by linking "PageName?action=WikiBlog".  Maybe a nice feature in
 * lib/display.php would be to automatically check if there are
 * blogs for the given page, then provide a link to them somewhere on
 * the page.  Or maybe this just creates a huge mess...
 *
 * Maybe it would be a good idea to ENABLE blogging of only certain
 * pages by setting metadata or something...?  If a page is non-bloggable
 * the plugin is ignored (perhaps with a warning message).
 *
 * Should blogs be by default filtered out of RecentChanges et al???
 *
 * Think of better name for this module: Blog? WikiLog? WebLog? WikiDot?
 *
 * Have other 'styles' for the plugin?... e.g. 'quiet'.  Display only
 * 'This page has 23 associated comments. Click here to view / add.'
 *
 * For admin user, put checkboxes beside comments to allow for bulk removal.
 *
 * Permissions for who can add blogs?  Display entry box only if
 * user meets these requirements...?
 *
 * Code cleanup: break into functions, use templates (or at least remove CSS)
 */

class WikiPlugin_WikiBlog extends WikiPlugin
{
    public function getName()
    {
        return _("WikiBlog");
    }

    public function getDescription()
    {
        return sprintf(_("Show and add blogs for %s"), '[pagename]');
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.23 $"
        );
    }

    // Arguments:
    //  page - page which is blogged to (default current page)
    //
    //  order - 'normal' - place in chronological order
    //        - 'reverse' - place in reverse chronological order
    //
    //  mode - 'show' - only show old blogs
    //         'add' - only show entry box for new blog
    //         'show,add' - show old blogs then entry box
    //         'add,show' - show entry box followed by old blogs
    //
    // TODO:
    //
    // - arguments to allow selection of time range to display
    // - arguments to display only XX blogs per page (can this 'paging'
    //    co-exist with the wiki??  difficult)
    // - arguments to allow comments outside this range to be
    //    display as e.g. June 2002 archive, July 2002 archive, etc..
    // - captions for 'show' and 'add' sections


    public function getDefaultArguments()
    {
        return array('pagename'   => '[pagename]',
                     'order'      => 'normal',
                     'mode'       => 'show,add',
                     'noheader'   => false
                    );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        // allow empty pagenames for ADMIN_USER style blogs: "Blog/day"
        //if (!$args['pagename'])
        //    return $this->error(_("No pagename specified"));

        // Get our form args.
        $blog = $request->getArg("blog");
        $request->setArg('blog', false);

        if ($request->isPost() and !empty($blog['addblog'])) {
            $this->add($request, $blog); // noreturn
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
                case 'show':
                    $html->pushContent($this->showAll($request, $args));
                    break;
                case 'add':
                    $html->pushContent($this->showForm($request, $args));
                    break;
                default:
                    return $this->error(sprintf("Bad mode ('%s')", $show));
            }
        }
        return $html;
    }

    public function add(&$request, $blog, $type = 'wikiblog')
    {
        $parent = $blog['pagename'];
        if (empty($parent)) {
            $prefix = "";   // allow empty parent for default "Blog/day"
            $parent = HOME_PAGE;
        } else {
            $prefix = $parent . SUBPAGE_SEPARATOR;
        }
        //$request->finish(fmt("No pagename specified for %s",$type));

        $now = time();
        $dbi = $request->getDbh();
        $user = $request->getUser();

        /*
         * Page^H^H^H^H Blog meta-data
         * This method is reused for all attachable pagetypes: wikiblog, comment and wikiforum
         *
         * This is info that won't change for each revision.
         * Nevertheless, it's now stored in the revision meta-data.
         * Several reasons:
         *  o It's more convenient to have all information required
         *    to render a page revision in the revision meta-data.
         *  o We can avoid a race condition, since version meta-data
         *    updates are atomic with the version creation.
         */

        $blog_meta = array('ctime'      => $now,
                           'creator'    => $user->getId(),
                           'creator_id' => $user->getAuthenticatedId(),
                           );

        // Version meta-data
        $summary = trim($blog['summary']);
        $version_meta = array('author'    => $blog_meta['creator'],
                              'author_id' => $blog_meta['creator_id'],
                              'markup'    => 2.0,   // assume new markup
                              'summary'   => $summary ? $summary : _("New comment."),
                              'mtime'     => $now,
                              'pagetype'  => $type,
                              $type       => $blog_meta,
                              );
        if ($type == 'comment') {
            unset($version_meta['summary']);
        }

        // Comment body.
        $body = trim($blog['body']);

        $saved = false;
        while (!$saved) {
            // Generate the page name.  For now, we use the format:
            //   Rootname/Blog/2003-01-11/14:03:02+00:00
            // This gives us natural chronological order when sorted
            // alphabetically. "Rootname/" is optional.

            $time = Iso8601DateTime();
            if ($type == 'wikiblog') {
                $pagename = "Blog";
            } elseif ($type == 'comment') {
                $pagename = "Comment";
            } elseif ($type == 'wikiforum') {
                $pagename = substr($summary, 0, 12);
            }

            // Check intermediate pages. If not existing they should RedirectTo the parent page.
            // Maybe add the BlogArchives plugin instead for the new interim subpage.
            $redirected = $prefix . $pagename;
            if (!$dbi->isWikiPage($redirected)) {
                require_once('lib/loadsave.php');
                $pageinfo = array('pagename' => $redirected,
                                  'content'  => '<?plugin RedirectTo page=' . $parent . ' ?>',
                                  'pagedata' => array(),
                                  'versiondata' => array('author' => $blog_meta['creator']),
                                  );
                SavePage($request, $pageinfo, '', '');
            }
            $redirected = $prefix . $pagename . SUBPAGE_SEPARATOR . preg_replace("/T.*/", "", "$time");
            if (!$dbi->isWikiPage($redirected)) {
                require_once('lib/loadsave.php');
                $pageinfo = array('pagename' => $redirected,
                                  'content'  => '<?plugin RedirectTo page=' . $parent . ' ?>',
                                  'pagedata' => array(),
                                  'versiondata' => array('author' => $blog_meta['creator']),
                                  );
                SavePage($request, $pageinfo, '', '');
            }

            $p = $dbi->getPage($prefix . $pagename . SUBPAGE_SEPARATOR
                               . str_replace("T", SUBPAGE_SEPARATOR, "$time"));
            $pr = $p->getCurrentRevision();

            // Version should be zero.  If not, page already exists
            // so increment timestamp and try again.
            if ($pr->getVersion() > 0) {
                $now++;
                continue;
            }

            // FIXME: there's a slight, but currently unimportant
            // race condition here.  If someone else happens to
            // have just created a blog with the same name,
            // we'll have locked it before we discover that the name
            // is taken.
            /*
             * FIXME:  For now all blogs are locked.  It would be
             * nice to allow only the 'creator' to edit by default.
             */
            $p->set('locked', true); //lock by default
            $saved = $p->save($body, 1, $version_meta);

            $now++;
        }

        $dbi->touch();
        $request->redirect($request->getURLtoSelf()); // noreturn

        // FIXME: when submit a comment from preview mode,
        // adds the comment properly but jumps to browse mode.
        // Any way to jump back to preview mode???
    }

    public function showAll(&$request, $args, $type = "wikiblog")
    {
        // FIXME: currently blogSearch uses WikiDB->titleSearch to
        // get results, so results are in alphabetical order.
        // When PageTypes fully implemented, could have smarter
        // blogSearch implementation / naming scheme.

        $dbi = $request->getDbh();

        $parent = $args['pagename'];
        $blogs = $this->findBlogs($dbi, $parent, $type);
        $html = HTML();
        if ($blogs) {
            // First reorder
            usort($blogs, array("WikiPlugin_WikiBlog",
                                "cmp"));
            if ($args['order'] == 'reverse') {
                $blogs = array_reverse($blogs);
            }

            $name = $this->_blogPrefix($type);
            if (!$args['noheader']) {
                $html->pushContent(HTML::h4(
                    array('class' => "$type-heading"),
                    fmt("%s on %s:", $name, WikiLink($parent))
                ));
            }
            foreach ($blogs as $rev) {
                if (!$rev->get($type)) {
                    // Ack! this is an old-style blog with data ctime in page meta-data.
                    $content = $this->_transformOldFormatBlog($rev, $type);
                } else {
                    $content = $rev->getTransformedContent($type);
                }
                $html->pushContent($content);
            }
        }
        return $html;
    }

    // all Blogs/Forum/Comment entries are subpages under this pagename, to find them faster.
    public function _blogPrefix($type = 'wikiblog')
    {
        if ($type == 'wikiblog') {
            $name = "Blog";
        } elseif ($type == 'comment') {
            $name = "Comment";
        } elseif ($type == 'wikiforum') {
            $name = "Message"; // FIXME: we use the first 12 chars of the summary
        }
        return $name;
    }

    public function _transformOldFormatBlog($rev, $type = 'wikiblog')
    {
        $page = $rev->getPage();
        $metadata = array();
        foreach (array('ctime', 'creator', 'creator_id') as $key) {
            $metadata[$key] = $page->get($key);
        }
        if (empty($metadata) and $type != 'wikiblog') {
            $metadata[$key] = $page->get('wikiblog');
        }
        $meta = $rev->getMetaData();
        $meta[$type] = $metadata;
        return new TransformedText($page, $rev->getPackedContent(), $meta, $type);
    }

    public function findBlogs(&$dbi, $parent, $type = 'wikiblog')
    {
        $prefix = (empty($parent) ? "" :  $parent . SUBPAGE_SEPARATOR) . $this->_blogPrefix($type);
        $pages = $dbi->titleSearch(new TextSearchQuery("^" . $prefix, true, 'posix'));

        $blogs = array();
        while ($page = $pages->next()) {
            if (!string_starts_with($page->getName(), $prefix)) {
                continue;
            }
            $current = $page->getCurrentRevision();
            if ($current->get('pagetype') == $type) {
                $blogs[] = $current;
            }
        }
        return $blogs;
    }

    public function cmp($a, $b)
    {
        return(strcmp(
            $a->get('mtime'),
            $b->get('mtime')
        ));
    }

    public function showForm(&$request, $args, $template = 'blogform')
    {
        // Show blog-entry form.
        return new Template(
            $template,
            $request,
            array('PAGENAME' => $args['pagename'])
        );
    }

    // "2004-12" => "December 2004"
    public function _monthTitle($month)
    {
        //list($year,$mon) = explode("-",$month);
        return strftime("%B %Y", strtotime($month . "-01"));
    }

    // "User/Blog/2004-12-13/12:28:50+01:00" => array('month' => "2004-12", ...)
    public function _blog($rev_or_page)
    {
        $pagename = $rev_or_page->getName();
        if (preg_match("/^(.*Blog)\/(\d\d\d\d-\d\d)-(\d\d)\/(.*)/", $pagename, $m)) {
            list(,$prefix,$month,$day,$time) = $m;
        }
        return array('pagename' => $pagename,
                     // page (list pages per month) or revision (list months)?
                     //'title' => isa($rev_or_page,'WikiDB_PageRevision') ? $rev_or_page->get('summary') : '',
                     //'monthtitle' => $this->_monthTitle($month),
                     'month'   => $month,
                     'day'     => $day,
                     'time'    => $time,
                     'prefix'  => $prefix);
    }

    public function _nonDefaultArgs($args)
    {
        return array_diff_assoc($args, $this->getDefaultArguments());
    }
}

// $Log: WikiBlog.php,v $
// Revision 1.23  2005/10/29 09:06:37  rurban
// move common blog methods to WikiBlog
//
// Revision 1.22  2004/12/15 15:33:18  rurban
// Blogs => Blog
//
// Revision 1.21  2004/12/14 21:35:15  rurban
// support new BLOG_EMPTY_DEFAULT_PREFIX
//
// Revision 1.20  2004/12/13 13:22:57  rurban
// new BlogArchives plugin for the new blog theme. enable default box method
// for all plugins. Minor search improvement.
//
// Revision 1.19  2004/11/26 18:39:02  rurban
// new regex search parser and SQL backends (90% complete, glob and pcre backends missing)
//
// Revision 1.18  2004/05/14 20:55:04  rurban
// simplified RecentComments
//
// Revision 1.17  2004/05/14 17:33:12  rurban
// new plugin RecentChanges
//
// Revision 1.16  2004/04/19 18:27:46  rurban
// Prevent from some PHP5 warnings (ref args, no :: object init)
//   php5 runs now through, just one wrong XmlElement object init missing
// Removed unneccesary UpgradeUser lines
// Changed WikiLink to omit version if current (RecentChanges)
//
// Revision 1.15  2004/04/18 05:42:17  rurban
// more fixes for page="0"
// better WikiForum support
//
// Revision 1.14  2004/03/29 21:33:32  rurban
// possible fix for problem reported by Whit Blauvelt
//   Message-ID: <20040327211707.GA22374@free.transpect.com>
// create intermediate redirect subpages for blog/comment/forum
//
// Revision 1.13  2004/03/15 10:59:40  rurban
// fix also early attach type bug, attached as wikiblog pagetype
//
// Revision 1.11  2004/03/12 20:59:31  rurban
// important cookie fix by Konstantin Zadorozhny
// new editpage feature: JS_SEARCHREPLACE
//
// Revision 1.10  2004/03/12 17:32:41  rurban
// new base class PageType_attach as base class for WikiBlog, Comment, and WikiForum.
// new plugin AddComment, which is a WikiBlog with different pagetype and template,
//   based on WikiBlog. WikiForum comes later.
//
// Revision 1.9  2004/02/27 02:10:50  rurban
// Patch #891133 by pablom517
//   "WikiBlog Plugin now sorts logs correctly"
//
// Revision 1.8  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.7  2003/11/17 16:23:55  carstenklapp
// Switched to Iso8601DateTime and more use of SUBPAGE_SEPARATOR. This
// allows plugin UnfoldSubpages (for example) to be added to page
// XxYy/Blog/ where desired, for a view of all Blogs in one day. This
// change should not break existing BLOGs, we are only checking for
// pagetype == 'wikiblog' now instead of relying on the subpage name to
// collect blog subpages. (** WARNING: Do not add UnfoldSubpages to both
// XxYy/Blog/ and XxYy/Blog/2003-11/16/ pages, due to recursion bug in
// UnfoldSubpages plugin.)
//
// Revision 1.6  2003/02/21 04:20:09  dairiki
// Big refactor. Formatting now done by the stuff in PageType.php.
// Split the template into two separate ones: one for the add comment form,
// one for comment display.
//
// Revision 1.5  2003/02/16 19:47:17  dairiki
// Update WikiDB timestamp when editing or deleting pages.
//
// Revision 1.4  2003/01/11 22:23:00  carstenklapp
// More refactoring to use templated output. Use page meta "summary" field.
//
// Revision 1.3  2003/01/06 02:29:02  carstenklapp
// New: use blog.tmpl template to format output. Some cosmetic
// issues, it mostly works but code still needs cleanup. Added
// getVersion() for PluginManager.
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
