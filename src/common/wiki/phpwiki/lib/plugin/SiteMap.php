<?php
// -*-php-*-
rcs_id('$Id: SiteMap.php,v 1.13 2004/12/14 21:36:06 rurban Exp $');
/**
 Copyright 1999,2000,2001,2002,2004 $ThePhpWikiProgrammingTeam

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
 * http://sourceforge.net/tracker/?func=detail&aid=537380&group_id=6121&atid=306121
 *
 * Submitted By: Cuthbert Cat (cuthbertcat)
 *
 * This is a quick mod of BackLinks to do the job recursively. If your
 * site is categorized correctly, and all the categories are listed in
 * CategoryCategory, then a RecBackLinks there will produce a contents
 * page for the entire site.
 *
 * The list is as deep as the recursion level.
 *
 * direction: Get BackLinks or forward links (links listed on the page)
 *
 * firstreversed: If true, get BackLinks for the first page and forward
 * links for the rest. Only applicable when direction = 'forward'.
 *
 * excludeunknown: If true (default) then exclude any mentioned pages
 * which don't exist yet.  Only applicable when direction = 'forward'.
 */
require_once('lib/PageList.php');

class WikiPlugin_SiteMap extends WikiPlugin
{
    public $_pagename;

    public function getName()
    {
        return _("SiteMap");
    }

    public function getDescription()
    {
        return _("Recursively get BackLinks or links");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.13 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('exclude'        => '',
                     'include_self'   => 0,
                     'noheader'       => 0,
                     'page'           => '[pagename]',
                     'description'    => $this->getDescription(),
                     'reclimit'       => 4,
                     'info'           => false,
                     'direction'      => 'back',
                     'firstreversed'  => false,
                     'excludeunknown' => true,
                     'includepages'   => '' // to be used only from the IncludeSiteMap plugin
                     );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor
    // exclude arg allows multiple pagenames
    // exclude=HomePage,RecentChanges

    // Fixme: overcome limitation if two SiteMap plugins are in the same page!
    // static $VisitedPages still holds it
    public function recursivelyGetBackLinks(
        $startpage,
        $pagearr,
        $level = '*',
        $reclimit = '***'
    ) {
        static $VisitedPages = array();

        $startpagename = $startpage->getName();
        //trigger_error("DEBUG: recursivelyGetBackLinks( $startpagename , $level )");
        if ($level == $reclimit) {
            return $pagearr;
        }
        if (in_array($startpagename, $VisitedPages)) {
            return $pagearr;
        }
        array_push($VisitedPages, $startpagename);
        $pagelinks = $startpage->getLinks();
        while ($link = $pagelinks->next()) {
            $linkpagename = $link->getName();
            if (
                ($linkpagename != $startpagename)
                and (!$this->ExcludedPages or !preg_match("/" . $this->ExcludedPages . "/", $linkpagename))
            ) {
                $pagearr[$level . " [$linkpagename]"] = $link;
                $pagearr = $this->recursivelyGetBackLinks(
                    $link,
                    $pagearr,
                    $level . '*',
                    $reclimit
                );
            }
        }
        return $pagearr;
    }

    public function recursivelyGetLinks(
        $startpage,
        $pagearr,
        $level = '*',
        $reclimit = '***'
    ) {
        static $VisitedPages = array();

        $startpagename = $startpage->getName();
        //trigger_error("DEBUG: recursivelyGetLinks( $startpagename , $level )");
        if ($level == $reclimit) {
            return $pagearr;
        }
        if (in_array($startpagename, $VisitedPages)) {
            return $pagearr;
        }
        array_push($VisitedPages, $startpagename);
        $reversed = (($this->firstreversed)
                     && ($startpagename == $this->initialpage));
        //trigger_error("DEBUG: \$reversed = $reversed");
        $pagelinks = $startpage->getLinks($reversed);
        while ($link = $pagelinks->next()) {
            $linkpagename = $link->getName();
            if (
                ($linkpagename != $startpagename) and
                (!$this->ExcludedPages or !preg_match("/$this->ExcludedPages/", $linkpagename))
            ) {
                if (!$this->excludeunknown or $this->dbi->isWikiPage($linkpagename)) {
                    $pagearr[$level . " [$linkpagename]"] = $link;
                    $pagearr = $this->recursivelyGetLinks(
                        $link,
                        $pagearr,
                        $level . '*',
                        $reclimit
                    );
                }
            }
        }
        return $pagearr;
    }


    public function run($dbi, $argstr, &$request, $basepage)
    {
        include_once('lib/BlockParser.php');

        $args = $this->getArgs($argstr, $request, false);
        extract($args);
        if (!$page) {
            return '';
        }
        $this->_pagename = $page;
        $out = ''; // get rid of this
        $html = HTML();
        if (empty($exclude)) {
            $exclude = array();
        }
        if (!$include_self) {
            $exclude[] = $page;
        }
        $this->ExcludedPages = empty($exclude) ? "" : ("^(?:" . join("|", $exclude) . ")");
        $this->_default_limit = str_pad('', 3, '*');
        if (is_numeric($reclimit)) {
            if ($reclimit < 0) {
                $reclimit = 0;
            }
            if ($reclimit > 10) {
                $reclimit = 10;
            }
            $limit = str_pad('', $reclimit + 2, '*');
        } else {
            $limit = '***';
        }
        //Fixme:  override given arg
        $description = $this->getDescription();
        if (! $noheader) {
            $out = $this->getDescription() . " " . sprintf(
                _("(max. recursion level: %d)"),
                $reclimit
            ) . ":\n\n";
            $html->pushContent(TransformText($out, 1.0, $page));
        }
        $pagelist = new PageList($info, $exclude);
        $p = $dbi->getPage($page);

        $pagearr = array();
        if ($direction == 'back') {
            $pagearr = $this->recursivelyGetBackLinks(
                $p,
                $pagearr,
                "*",
                $limit
            );
        } else {
            $this->dbi = $dbi;
            $this->initialpage = $page;
            $this->firstreversed = $firstreversed;
            $this->excludeunknown = $excludeunknown;
            $pagearr = $this->recursivelyGetLinks($p, $pagearr, "*", $limit);
        }

        reset($pagearr);
        if (!empty($includepages)) {
            // disallow direct usage, only via child class IncludeSiteMap
            if (!isa($this, "WikiPlugin_IncludeSiteMap")) {
                $includepages = '';
            }
            if (!is_string($includepages)) {
                $includepages = ' '; // avoid plugin loader problems
            }
            $loader = new WikiPluginLoader();
            $plugin = $loader->getPlugin('IncludePage', false);
            $nothing = '';
        }

        foreach ($pagearr as $key => $link) {
            if (!empty($includepages)) {
                $a = substr_count($key, '*');
                $indenter = str_pad($nothing, $a);
                //$request->setArg('IncludePage', 1);
                // quote linkname, by Stefan Schorn
                $plugin_args = 'page=\'' . $link->getName() . '\' ' . $includepages;
                $pagehtml = $plugin->run($dbi, $plugin_args, $request, $basepage);
                $html->pushContent($pagehtml);
                //$html->pushContent( HTML(TransformText($indenter, 1.0, $page), $pagehtml));
                //$out .= $indenter . $pagehtml . "\n";
            } else {
                $out .= $key . "\n";
            }
        }
        if (empty($includepages)) {
            return TransformText($out, 2.0, $page);
        } else {
            return $html;
        }
    }
}

// $Log: SiteMap.php,v $
// Revision 1.13  2004/12/14 21:36:06  rurban
// exclude is already handled by getArgs
//
// Revision 1.12  2004/11/01 09:14:25  rurban
// avoid ConvertOldMarkup step, using markup=2 (memory problems)
//
// Revision 1.11  2004/03/24 19:39:03  rurban
// php5 workaround code (plus some interim debugging code in XmlElement)
//   php5 doesn't work yet with the current XmlElement class constructors,
//   WikiUserNew does work better than php4.
// rewrote WikiUserNew user upgrading to ease php5 update
// fixed pref handling in WikiUserNew
// added Email Notification
// added simple Email verification
// removed emailVerify userpref subclass: just a email property
// changed pref binary storage layout: numarray => hash of non default values
// print optimize message only if really done.
// forced new cookie policy: delete pref cookies, use only WIKI_ID as plain string.
//   prefs should be stored in db or homepage, besides the current session.
//
// Revision 1.10  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.9  2004/02/12 13:05:50  rurban
// Rename functional for PearDB backend
// some other minor changes
// SiteMap comes with a not yet functional feature request: includepages (tbd)
//
// Revision 1.8  2004/01/24 23:24:07  rurban
// Patch by Alec Thomas, allows Perl regular expressions in SiteMap exclude lists.
//   exclude=WikiWikiWeb,(?:Category|Topic).*
// It is backwards compatible unless old exclude lists, and therefore Wiki
// page names, contain regular expression characters.
//
// Revision 1.7  2003/02/21 04:12:06  dairiki
// Minor fixes for new cached markup.
//
// Revision 1.6  2003/01/18 22:08:01  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
