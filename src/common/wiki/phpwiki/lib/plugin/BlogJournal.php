<?php
// -*-php-*-
rcs_id('$Id: BlogJournal.php,v 1.4 2005/11/21 20:56:23 rurban Exp $');
/*
 * Copyright 2005 $ThePhpWikiProgrammingTeam
 */

require_once('lib/plugin/WikiBlog.php');

/**
 * BlogJournal - Include the latest blog entries for the current users blog if signed,
 *               or the ADMIN_USER's Blog if not.
 * UnfoldSubpages for blogs.
 * Rui called this plugin "JournalLast", but this was written completely independent,
 * without having seen the src.
 *
 * @author: Reini Urban
 */
class WikiPlugin_BlogJournal extends WikiPlugin_WikiBlog
{
    public function getName()
    {
        return _("BlogJournal");
    }

    public function getDescription()
    {
        return _("Include latest blog entries for the current or ADMIN user");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.4 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('count'    => 7,
                     'user'     => '',
                     'order'    => 'reverse',        // latest first
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
        $user = $request->getUser();
        if (empty($args['user'])) {
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

        $sp = HTML::Raw('&middot; ');
        $prefix = $parent . $this->_blogPrefix('wikiblog');
        if ($args['month']) {
            $prefix .= (SUBPAGE_SEPARATOR . $args['month']);
        }
        $pages = $dbi->titleSearch(new TextSearchQuery("^" . $prefix, true, 'posix'));
        $html = HTML();
        $i = 0;
        while (($page = $pages->next()) and $i < $args['count']) {
            $rev = $page->getCurrentRevision(false);
            if ($rev->get('pagetype') != 'wikiblog') {
                continue;
            }
            $i++;
            $blog = $this->_blog($rev);
            //$html->pushContent(HTML::h3(WikiLink($page, 'known', $rev->get('summary'))));
            $html->pushContent($rev->getTransformedContent('wikiblog'));
        }
        if ($args['user'] == $user->UserName()) {
            $html->pushContent(WikiLink(_("WikiBlog"), 'known', "New entry"));
        }
        if (!$i) {
            return HTML(HTML::h3(_("No Blog Entries")), $html);
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
}

// $Log: BlogJournal.php,v $
// Revision 1.4  2005/11/21 20:56:23  rurban
// no duplicate headline and no direct page link anymore
//
// Revision 1.3  2005/11/21 20:47:21  rurban
// fix count error
//
// Revision 1.2  2005/10/29 09:06:37  rurban
// move common blog methods to WikiBlog
//
// Revision 1.1  2005/10/29 09:03:17  rurban
// Include the latest blog entries for the current users blog if signed,
// or the ADMIN_USER's Blog if not.
// UnfoldSubpages for blogs.
// Rui called this plugin "JournalLast", but this was written completely
// independently, without having seen the src (yet).
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
