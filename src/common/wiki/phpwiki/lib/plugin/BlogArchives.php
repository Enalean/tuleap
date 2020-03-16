<?php
// -*-php-*-
rcs_id('$Id: BlogArchives.php,v 1.5 2005/10/29 09:06:37 rurban Exp $');
/*
 * Copyright 2004 $ThePhpWikiProgrammingTeam
 */

//require_once('lib/PageList.php');
require_once('lib/plugin/WikiBlog.php');

/**
 * BlogArchives - List monthly links for the current users blog if signed,
 * or the ADMIN_USER's Blog if not.
 * On month=... list the blog titles per month.
 *
 * TODO: year=
 *       support PageList (paging, limit, info filters: title, num, month, year, ...)
 *       leave off time subpage? Blogs just per day with one summary title only?
 * @author: Reini Urban
 */
class WikiPlugin_BlogArchives extends WikiPlugin_WikiBlog
{
    public function getName()
    {
        return _("Archives");
    }

    public function getDescription()
    {
        return _("List blog months links for the current or ADMIN user");
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
        return //array_merge
               //(
               //PageList::supportedArgs(),
             array('user'     => '',
                   'order'    => 'reverse',        // latest first
                   'info'     => 'month,numpages', // ignored
                   'month'    => false,
                   'noheader' => 0
                   );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        if (is_array($argstr)) { // can do with array also.
            $args = $argstr;
            if (!isset($args['order'])) {
                $args['order'] = 'reverse';
            }
        } else {
            $args = $this->getArgs($argstr, $request);
        }
        if (empty($args['user'])) {
            $user = $request->getUser();
            if ($user->isAuthenticated()) {
                $args['user'] = $user->UserName();
            } else {
                $args['user'] = '';
            }
        }
        if (!$args['user'] or $args['user'] == ADMIN_USER) {
            if (BLOG_EMPTY_DEFAULT_PREFIX) {
                $args['user'] = '';         // "Blogs/day" pages
            } else {
                $args['user'] = ADMIN_USER; // "Admin/Blogs/day" pages
            }
        }
        $parent = (empty($args['user']) ? '' : $args['user'] . SUBPAGE_SEPARATOR);

        //$info = explode(',', $args['info']);
        //$pagelist = new PageList($args['info'], $args['exclude'], $args);
        //if (!is_array('pagename'), explode(',', $info))
        //    unset($pagelist->_columns['pagename']);

        $sp = HTML::Raw('&middot; ');
        if (!empty($args['month'])) {
            $prefix = $parent . $this->_blogPrefix('wikiblog') . SUBPAGE_SEPARATOR . $args['month'];
            $pages = $dbi->titleSearch(new TextSearchQuery("^" . $prefix, true, 'posix'));
            $html = HTML::ul();
            while ($page = $pages->next()) {
                $rev = $page->getCurrentRevision(false);
                if ($rev->get('pagetype') != 'wikiblog') {
                    continue;
                }
                $blog = $this->_blog($rev);
                $html->pushContent(HTML::li(WikiLink($page, 'known', $rev->get('summary'))));
            }
            if (!$args['noheader']) {
                return HTML(
                    HTML::h3(sprintf(_("Blog Entries for %s:"), $this->_monthTitle($args['month']))),
                    $html
                );
            } else {
                return $html;
            }
        }

        $blogs = $this->findBlogs($dbi, $args['user'], 'wikiblog');
        if ($blogs) {
            if (!$basepage) {
                $basepage = _("BlogArchives");
            }
            $html = HTML::ul();
            usort($blogs, array("WikiPlugin_WikiBlog", "cmp"));
            if ($args['order'] == 'reverse') {
                $blogs = array_reverse($blogs);
            }
            // collapse pagenames by month
            $months = array();
            foreach ($blogs as $rev) {
                $blog = $this->_blog($rev);
                $mon = $blog['month'];
                if (empty($months[$mon])) {
                    $months[$mon] =
                        array('title' => $this->_monthTitle($mon),
                              'num'   => 1,
                              'month' => $mon,
                              'link'  => WikiURL(
                                  $basepage,
                                  $this->_nonDefaultArgs(array('month' => $mon))
                              ));
                } else {
                    $months[$mon]['num']++;
                }
            }
            foreach ($months as $m) {
                $html->pushContent(HTML::li(HTML::a(
                    array('href' => $m['link'],
                                                          'class' => 'named-wiki'),
                    $m['title'] . " (" . $m['num'] . ")"
                )));
            }
            if (!$args['noheader']) {
                return HTML(HTML::h3(_("Blog Archives:")), $html);
            } else {
                return $html;
            }
        } else {
            return '';
        }
    }

    // box is used to display a fixed-width, narrow version with common header
    public function box($args = false, $request = false, $basepage = false)
    {
        if (!$request) {
            $request = $GLOBALS['request'];
        }
        if (!$args or empty($args['limit'])) {
            $args['limit'] = 10;
        }
        $args['noheader'] = 1;
        return $this->makeBox(_("Archives"), $this->run($request->_dbi, $args, $request, $basepage));
    }
}

// $Log: BlogArchives.php,v $
// Revision 1.5  2005/10/29 09:06:37  rurban
// move common blog methods to WikiBlog
//
// Revision 1.4  2004/12/16 18:29:00  rurban
// allow empty Blog prefix
//
// Revision 1.3  2004/12/15 17:45:08  rurban
// fix box method
//
// Revision 1.2  2004/12/14 21:35:15  rurban
// support new BLOG_EMPTY_DEFAULT_PREFIX
//
// Revision 1.1  2004/12/13 13:22:57  rurban
// new BlogArchives plugin for the new blog theme. enable default box method
// for all plugins. Minor search improvement.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
