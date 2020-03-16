<?php
// -*-php-*-
rcs_id('$Id: PageHistory.php,v 1.30 2004/06/14 11:31:39 rurban Exp $');
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

require_once('lib/plugin/RecentChanges.php');

class _PageHistory_PageRevisionIter extends WikiDB_PageRevisionIterator
{
    public function __construct($rev_iter, $params)
    {
        $this->_iter = $rev_iter;

        extract($params);

        if (isset($since)) {
            $this->_since = $since;
        }

        $this->_include_major = empty($exclude_major_revisions);
        if (! $this->_include_major) {
            $this->_include_minor = true;
        } else {
            $this->_include_minor = !empty($include_minor_revisions);
        }

        if (empty($include_all_revisions)) {
            $this->_limit = 1;
        } elseif (isset($limit)) {
            $this->_limit = $limit;
        }
    }

    public function next()
    {
        if (!$this->_iter) {
            return false;
        }

        if (isset($this->_limit)) {
            if ($this->_limit <= 0) {
                $this->free();
                return false;
            }
            $this->_limit--;
        }

        while (($rev = $this->_iter->next())) {
            if (isset($this->_since) && $rev->get('mtime') < $this->_since) {
                $this->free();
                return false;
            }
            if ($rev->get('is_minor_edit') ? $this->_include_minor : $this->_include_major) {
                return $rev;
            }
        }
        return false;
    }


    public function free()
    {
        if ($this->_iter) {
            $this->_iter->free();
        }
        $this->_iter = false;
    }
}


class _PageHistory_HtmlFormatter extends _RecentChanges_HtmlFormatter
{
    public function include_versions_in_URLs()
    {
        return true;
    }

    public function title()
    {
        return array(fmt(
            "PageHistory for %s",
            WikiLink($this->_args['page'])
        ),
                     "\n",
                     $this->rss_icon());
    }

    public function empty_message()
    {
        return _("No revisions found");
    }

    public function description()
    {
        $button = HTML::input(array('type'  => 'submit',
                                    'value' => _("compare revisions"),
                                    'class' => 'wikiaction'));

        $js_desc = $no_js_desc = _RecentChanges_HtmlFormatter::description();

        $js_desc->pushContent("\n", _("Check any two boxes to compare revisions."));
        $no_js_desc->pushContent("\n", fmt("Check any two boxes then %s.", $button));

        return IfJavaScript($js_desc, $no_js_desc);
    }


    public function format($changes)
    {
        $this->_itemcount = 0;

        $pagename = $this->_args['page'];

        $html[] = _RecentChanges_HtmlFormatter::format($changes);

        $html[] = HTML::input(array('type'  => 'hidden',
                                    'name'  => 'action',
                                    'value' => 'diff'));
        $html[] = HTML::input(array('type'  => 'hidden',
                                    'name'  => 'group_id',
                                    'value' => GROUP_ID));
        if (USE_PATH_INFO) {
            $action = WikiURL($pagename);
        } else {
            $action = SCRIPT_NAME;
            $html[] = HTML::input(array('type'  => 'hidden',
                                        'name'  => 'pagename',
                                        'value' => $pagename));
        }

        return HTML(
            HTML::form(
                array('method' => 'get',
                                     'action' => $action,
                                     'name'   => 'diff-select'),
                $html
            ),
            "\n",
            JavaScript('
        var diffCkBoxes = document.forms["diff-select"].elements["versions[]"];

        function diffCkBox_onclick() {
            var nchecked = 0, box = diffCkBoxes;
            for (i = 0; i < box.length; i++)
                if (box[i].checked) nchecked++;
            if (nchecked == 2)
                this.form.submit();
            else if (nchecked > 2) {
                for (i = 0; i < box.length; i++)
                    if (box[i] != this) box[i].checked = 0;
            }
        }

        for (i = 0; i < diffCkBoxes.length; i++)
            diffCkBoxes[i].onclick = diffCkBox_onclick;')
        );
    }

    public function diffLink($rev)
    {
        return HTML::input(array('type'  => 'checkbox',
                                 'name'  => 'versions[]',
                                 'value' => $rev->getVersion()));
    }

    public function pageLink($rev, $text_link = false)
    {
        $text = fmt("Version %d", $rev->getVersion());
        return _RecentChanges_HtmlFormatter::pageLink($rev, $text);
    }

    public function format_revision($rev)
    {
        $class = 'rc-' . $this->importance($rev);

        $time = $this->time($rev);
        if ($rev->get('is_minor_edit')) {
            $minor_flag = HTML(
                " ",
                HTML::span(
                    array('class' => 'pageinfo-minoredit'),
                    "(" . _("minor edit") . ")"
                )
            );
        } else {
            $time = HTML::strong(array('class' => 'pageinfo-majoredit'), $time);
            $minor_flag = '';
        }

        return HTML::li(
            array('class' => $class),
            $this->diffLink($rev),
            ' ',
            $this->pageLink($rev),
            ' ',
            $time,
            ' ',
            $this->summaryAsHTML($rev),
            ' ... ',
            $this->authorLink($rev),
            $minor_flag
        );
    }
}


class _PageHistory_RssFormatter extends _RecentChanges_RssFormatter
{
    public function include_versions_in_URLs()
    {
        return true;
    }

    public function image_properties()
    {
        return false;
    }

    public function textinput_properties()
    {
        return false;
    }

    public function channel_properties()
    {
        global $request;

        $rc_url = WikiURL($request->getArg('pagename'), false, 'absurl');

        $title = sprintf(
            _("%s: %s"),
            WIKI_NAME,
            SplitPagename($this->_args['page'])
        );

        return array('title'          => $title,
                     'dc:description' => _("History of changes."),
                     'link'           => $rc_url,
                     'dc:date'        => Iso8601DateTime(time()));
    }


    public function item_properties($rev)
    {
        if (!($title = $this->summary($rev))) {
            $title = sprintf(_("Version %d"), $rev->getVersion());
        }

        return array( 'title'           => $title,
                      'link'            => $this->pageURL($rev),
                      'dc:date'         => $this->time($rev),
                      'dc:contributor'  => $rev->get('author'),
                      'wiki:version'    => $rev->getVersion(),
                      'wiki:importance' => $this->importance($rev),
                      'wiki:status'     => $this->status($rev),
                      'wiki:diff'       => $this->diffURL($rev),
                      );
    }
}

class WikiPlugin_PageHistory extends WikiPlugin_RecentChanges
{
    public function getName()
    {
        return _("PageHistory");
    }

    public function getDescription()
    {
        return sprintf(_("List PageHistory for %s"), '[pagename]');
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.30 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('days'         => false,
                     'show_minor'   => true,
                     'show_major'   => true,
                     'limit'        => false,
                     'page'         => '[pagename]',
                     'format'       => false);
    }

    public function getDefaultFormArguments()
    {
        $dflts = WikiPlugin_RecentChanges::getDefaultFormArguments();
        $dflts['textinput'] = 'page';
        return $dflts;
    }

    public function getMostRecentParams($args)
    {
        $params = WikiPlugin_RecentChanges::getMostRecentParams($args);
        $params['include_all_revisions'] = true;
        return $params;
    }

    public function getChanges($dbi, $args)
    {
        $page = $dbi->getPage($args['page']);
        $iter = $page->getAllRevisions();
        $params = $this->getMostRecentParams($args);
        return new _PageHistory_PageRevisionIter($iter, $params);
    }

    public function format($changes, $args)
    {
        global $WikiTheme;
        $format = $args['format'];

        $fmt_class = $WikiTheme->getFormatter('PageHistory', $format);
        if (!$fmt_class) {
            if ($format == 'rss') {
                $fmt_class = '_PageHistory_RssFormatter';
            } else {
                $fmt_class = '_PageHistory_HtmlFormatter';
            }
        }

        $fmt = new $fmt_class($args);
        return $fmt->format($changes);
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        $pagename = $args['page'];
        if (empty($pagename)) {
            return $this->makeForm("", $request);
        }

        $page = $dbi->getPage($pagename);
        $current = $page->getCurrentRevision();
        if ($current->getVersion() < 1) {
            return HTML(
                HTML::p(fmt(
                    "I'm sorry, there is no such page as %s.",
                    WikiLink($pagename, 'unknown')
                )),
                $this->makeForm("", $request)
            );
        }
        // Hack alert: format() is a NORETURN for rss formatters.
        return $this->format($this->getChanges($dbi, $args), $args);
    }
}

// $Log: PageHistory.php,v $
// Revision 1.30  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.29  2004/05/18 16:23:40  rurban
// rename split_pagename to SplitPagename
//
// Revision 1.28  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.27  2003/02/27 22:48:44  dairiki
// Fixes invalid HTML generated by PageHistory plugin.
//
// (<noscript> is block-level and not allowed within <p>.)
//
// Revision 1.26  2003/02/27 21:15:14  dairiki
// Javascript fix.
//
// Fix so that you can never have more than two checkboxes checked. (If this
// happens, all but the current checkbox are unchecked.)
//
// It used to be that one could view a PageHistory, check two boxes to view
// a diff, then hit the back button.  (The originally checked two boxes are
// still checked at this point.)  Checking a third box resulted in viewing
// a diff between a quasi-random pair of versions selected from the three
// which were selected.   Now clicking the third box results in the first
// two being unchecked.
//
// Revision 1.25  2003/02/17 02:19:01  dairiki
// Fix so that PageHistory will work when the current revision
// of a page has been "deleted".
//
// Revision 1.24  2003/01/18 21:49:00  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//
// Revision 1.23  2003/01/04 23:27:39  carstenklapp
// New: Gracefully handle non-existant pages. Added copyleft;
// getVersion() for PluginManager.
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
