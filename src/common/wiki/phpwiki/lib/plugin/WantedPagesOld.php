<?php
// -*-php-*-
rcs_id('$Id: WantedPagesOld.php,v 1.1 2004/11/20 11:28:49 rurban Exp $');
/*
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
 * A plugin which returns a list of referenced pages which do not exist yet.
 *
 **/
//include_once('lib/PageList.php');

class WikiPlugin_WantedPagesOld extends WikiPlugin
{
    public function getName()
    {
        return _("WantedPages");
    }

    public function getDescription()
    {
        return _("Lists referenced page names which do not exist yet.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.1 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('noheader' => false,
                     'exclude'  => _("PgsrcTranslation"),
                     'page'     => '[pagename]',
                     'sortby'   => false,
                     'limit'    => 50,
                     'paging'   => 'auto');
    }

    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor,markup or all
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));

        if ($exclude) {
            if (!is_array($exclude)) {
                $exclude = explode(',', $exclude);
            }
        }

        if ($page == _("WantedPages")) {
            $page = "";
        }

        // The PageList class can't handle the 'count' column needed
        // for this table
        $this->pagelist = array();

        // There's probably a more memory-efficient way to do this (eg
        // a tailored SQL query via the backend, but this gets the job
        // done.
        if (!$page) {
            $include_empty = false;
            $allpages_iter = $dbi->getAllPages($include_empty, $sortby, $limit);
            while ($page_handle = $allpages_iter->next()) {
                $name = $page_handle->getName();
                if ($name == _("InterWikiMap")) {
                    continue;
                }
                if (! in_array($name, $exclude)) {
                    $this->_iterateLinks($page_handle, $dbi);
                }
            }
        } elseif ($page && $pageisWikiPage = $dbi->isWikiPage($page)) {
            //only get WantedPages links for one page
            $page_handle = $dbi->getPage($page);
            $this->_iterateLinks($page_handle, $dbi);
            if (! $request->getArg('count')) {
                $args['count'] = count($this->pagelist);
            } else {
                $args['count'] = $request->getArg('count');
            }
        }
        ksort($this->pagelist);
        arsort($this->pagelist);

        $this->_rows = HTML();
        $caption = false;
        $this->_messageIfEmpty = _("<none>");

        if ($page) {
            // link count always seems to be 1 for a single page so
            // omit count column
            foreach ($this->pagelist as $key => $val) {
                $row = HTML::li(WikiLink((string) $key, 'unknown'));
                $this->_rows->pushContent($row);
            }
            if (!$noheader) {
                if ($pageisWikiPage) {
                    $pagelink = WikiLink($page);
                } else {
                    $pagelink = WikiLink($page, 'unknown');
                }
                $c = count($this->pagelist);
                $caption = fmt(
                    "Wanted Pages for %s (%d total):",
                    $pagelink,
                    $c
                );
            }
            return $this->_generateList($caption);
        } else {
            $spacer = new RawXml("&nbsp;&nbsp;&nbsp;&nbsp;");
            // Clicking on the number in the links column does a
            // FullTextSearch for the citations of the WantedPage
            // link.
            foreach ($this->pagelist as $key => $val) {
                $key = (string) $key; // TODO: Not sure why, but this
                                      // string cast type-coersion
                                      // does seem necessary here.
                // Enclose any FullTextSearch keys containing a space
                // with quotes in oder to request a defnitive search.
                $searchkey = (strstr($key, ' ') === false) ? $key : "\"$key\"";
                $row = HTML::tr(HTML::td(
                    array('align' => 'right'),
                    Button(
                        array('s' => $searchkey),
                        $val,
                        _("FullTextSearch")
                    ),
                    // Alternatively, get BackLinks
                                         // instead.
                                         //
                                         //Button(array('action'
                                         //             => _("BackLinks")),
                                         //       $val, $searchkey),
                                         HTML::td(HTML(
                                             $spacer,
                                             WikiLink(
                                                 $key,
                                                 'unknown'
                                             )
                                         ))
                ));
                $this->_rows->pushContent($row);
            }
            $c = count($this->pagelist);
            if (!$noheader) {
                $caption = sprintf(
                    _("Wanted Pages in this wiki (%d total):"),
                    $c
                );
            }
            $this->_columns = array(_("Count"), _("Page Name"));
            if ($c > 0) {
                return $this->_generateTable($caption);
            } else {
                return HTML(HTML::p($caption), HTML::p($messageIfEmpty));
            }
        }
    }

    public function _generateTable($caption)
    {
        if (count($this->pagelist) > 0) {
            $table = HTML::table(array('cellpadding' => 0,
                                       'cellspacing' => 1,
                                       'border'      => 0,
                                       'class'       => 'pagelist'));
            if ($caption) {
                $table->pushContent(HTML::caption(
                    array('align' => 'top'),
                    $caption
                ));
            }

            $row = HTML::tr();
            $spacer = new RawXml("&nbsp;&nbsp;&nbsp;&nbsp;");
            foreach ($this->_columns as $col_heading) {
                $row->pushContent(HTML::td(HTML(
                    $spacer,
                    HTML::u($col_heading)
                )));
                $table_summary[] = $col_heading;
            }
            // Table summary for non-visual browsers.
            $table->setAttr('summary', sprintf(
                _("Columns: %s."),
                implode(", ", $table_summary)
            ));

            $table->pushContent(
                HTML::thead($row),
                HTML::tbody(false, $this->_rows)
            );
        } else {
            $table = HTML();
            if ($caption) {
                $table->pushContent(HTML::p($caption));
            }
            $table->pushContent(HTML::p($this->_messageIfEmpty));
        }

        return $table;
    }

    public function _generateList($caption)
    {
        $list = HTML();
        $c = count($this->pagelist);
        if ($caption) {
            $list->pushContent(HTML::p($caption));
        }

        if ($c > 0) {
            $list->pushContent(HTML::ul($this->_rows));
        } else {
            $list->pushContent(HTML::p($this->_messageIfEmpty));
        }

        return $list;
    }

    public function _iterateLinks($page_handle, $dbi)
    {
        $links_iter = $page_handle->getLinks($reversed = false);
        while ($link_handle = $links_iter->next()) {
            if (! $dbi->isWikiPage($linkname = $link_handle->getName())) {
                if (! in_array($linkname, array_keys($this->pagelist))) {
                    $this->pagelist[$linkname] = 1;
                } else {
                    $this->pagelist[$linkname] += 1;
                }
            }
        }
    }
}

// $Log: WantedPagesOld.php,v $
// Revision 1.1  2004/11/20 11:28:49  rurban
// fix a yet unused PageList customPageListColumns bug (merge class not decl to _types)
// change WantedPages to use PageList
// change WantedPages to print the list of referenced pages, not just the count.
//   the old version was renamed to WantedPagesOld
//   fix and add handling of most standard PageList arguments (limit, exclude, ...)
// TODO: pagename sorting, dumb/WantedPagesIter and SQL optimization
//
// Revision 1.12  2004/10/04 23:39:34  rurban
// just aesthetics
//
// Revision 1.11  2004/04/20 00:56:00  rurban
// more paging support and paging fix for shorter lists
//
// Revision 1.10  2004/04/18 01:44:02  rurban
// more sortby+limit support
//
// Revision 1.9  2004/04/10 04:15:06  rurban
// sf.net 927122 Suggestion
//
// Revision 1.8  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.7  2003/12/19 06:57:49  carstenklapp
// Bugfix: Enclose FullTextSearch query with quotes when the [Wiki Word]
// contains spaces.
//
// Revision 1.6  2003/11/19 17:08:23  carstenklapp
// New feature: Clicking on the number of citations in the links column
// now does a FullTextSearch for the WantedPage link!
//
// Revision 1.5  2003/03/25 21:05:27  dairiki
// Ensure pagenames are strings.
//
// Revision 1.4  2003/01/18 22:14:24  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
