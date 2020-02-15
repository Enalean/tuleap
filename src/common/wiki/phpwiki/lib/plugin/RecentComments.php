<?php
// -*-php-*-
rcs_id('$Id: RecentComments.php,v 1.3 2004/05/14 20:55:03 rurban Exp $');

/**
 * List of basepages with recently added comments.
 * Idea from http://www.wakkawiki.com/RecentlyCommented
 * @author: Reini Urban
 */

require_once("lib/plugin/RecentChanges.php");
require_once("lib/plugin/WikiBlog.php");

class WikiPlugin_RecentComments extends WikiPlugin_RecentChanges
{
    public function getName()
    {
        return _("RecentComments");
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
        $args = WikiPlugin_RecentChanges::getDefaultArguments();
        $args['show_minor'] = false;
        $args['show_all'] = true;
        $args['caption'] = _("Recent Comments");
        return $args;
    }

    public function format($changes, $args)
    {
        $fmt = new _RecentChanges_CommentFormatter($args);
        return $fmt->format($changes);
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        // HACKish: fix for SF bug #622784  (1000 years of RecentChanges ought
        // to be enough for anyone.)
        $args['days'] = min($args['days'], 365000);
        return $this->format($this->getChanges($request->_dbi, $args), $args);
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
        // sort out pages with no comments
        $changes = new RecentCommentsRevisionIterator($changes, $dbi);
        return $changes;
    }
}

class _RecentChanges_CommentFormatter extends _RecentChanges_HtmlFormatter
{

    public function empty_message()
    {
        return _("No comments found");
    }

    public function title()
    {
        return;
    }

    public function format_revision($rev)
    {
        static $doublettes = array();
        if (isset($doublettes[$rev->getPageName()])) {
            return;
        }
        $doublettes[$rev->getPageName()] = 1;
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
            ' . . . . ',
            _("latest comment by "),
            $this->authorLink($rev)
        );
        return $line;
    }
}

/**
 * List of pages which have comments
 * i.e. sort out all non-commented pages.
 */
class RecentCommentsRevisionIterator extends WikiDB_PageRevisionIterator
{
    public function __construct($revisions, &$dbi)
    {
        $this->_revisions = $revisions;
        $this->_wikidb = $dbi;
        $this->_current = 0;
        $this->_blog = new WikiPlugin_WikiBlog();
    }

    public function next()
    {
        if (!empty($this->comments) and $this->_current) {
            if (isset($this->comments[$this->_current])) {
                return $this->comments[$this->_current++];
            } else {
                $this->_current = 0;
            }
        }
        while (($rev = $this->_revisions->next())) {
            $this->comments = $this->_blog->findBlogs($this->_wikidb, $rev->getPageName(), 'comment');
            if ($this->comments) {
                if (count($this->comments) > 2) {
                    usort($this->comments, array("WikiPlugin_WikiBlog",
                                                 "cmp"));
                }
                if (isset($this->comments[$this->_current])) {
                    //$this->_current++;
                    return $this->comments[$this->_current++];
                }
            } else {
                $this->_current = 0;
            }
        }
        $this->free();
        return false;
    }
}

// $Log: RecentComments.php,v $
// Revision 1.3  2004/05/14 20:55:03  rurban
// simplified RecentComments
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
