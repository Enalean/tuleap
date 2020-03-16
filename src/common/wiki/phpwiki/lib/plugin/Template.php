<?php
// -*-php-*-
rcs_id('$Id: Template.php,v 1.4 2005/09/11 13:30:22 rurban Exp $');
/*
 Copyright 2005 $ThePhpWikiProgrammingTeam

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
 * Template: Parametrized blocks.
 *    Include text from a wiki page and replace certain placeholders by parameters.
 *    Similiar to CreatePage with the template argument, but at run-time.
 *    Similiar to the mediawiki templates but not with the "|" parameter seperator.
 * Usage:   <?plugin Template page=TemplateFilm vars="title=rurban&year=1999" ?>
 * Author:  Reini Urban
 * See also: http://meta.wikimedia.org/wiki/Help:Template
 *
 * Parameter expansion:
 *   vars="var1=value1&var2=value2"
 * We only support named parameters, not numbered ones as in mediawiki, and
 * the placeholder is %%var%% and not {{{var}}} as in mediawiki.
 *
 * The following predefined variables are automatically expanded if existing:
 *   pagename
 *   mtime     - last modified date + time
 *   ctime     - creation date + time
 *   author    - last author
 *   owner
 *   creator   - first author
 *   SERVER_URL, DATA_PATH, SCRIPT_NAME, PHPWIKI_BASE_URL and BASE_URL
 *
 * <noinclude> .. </noinclude> is stripped
 *
 * In work:
 * - ENABLE_MARKUP_TEMPLATE = true: (lib/InlineParser.php)
 *   Support a mediawiki-style syntax extension which maps
 *     {{TemplateFilm|title=Some Good Film|year=1999}}
 *   to
 *     <?plugin Template page=TemplateFilm vars="title=Some Good Film&year=1999" ?>
 */

class WikiPlugin_Template extends WikiPlugin
{
    public function getName()
    {
        return _("Template");
    }

    public function getDescription()
    {
        return _("Parametrized page inclusion.");
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
        return array(
                     'page'    => false, // the page to include
                     'vars'    => false,
                     'rev'     => false, // the revision (defaults to most recent)
                     'section' => false, // just include a named section
                     'sectionhead' => false // when including a named section show the heading
                     );
    }

    public function getWikiPageLinks($argstr, $basepage)
    {
        extract($this->getArgs($argstr));
        if ($page) {
            // Expand relative page names.
            $page = new WikiPageName($page, $basepage);
        }
        if (!$page or !$page->name) {
            return false;
        }
        return array($page->name);
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));
        if ($page) {
            // Expand relative page names.
            $page = new WikiPageName($page, $basepage);
            $page = $page->name;
        }
        if (!$page) {
            return $this->error(_("no page specified"));
        }

        // Protect from recursive inclusion. A page can include itself once
        static $included_pages = array();
        if (in_array($page, $included_pages)) {
            return $this->error(sprintf(
                _("recursive inclusion of page %s"),
                $page
            ));
        }

        $p = $dbi->getPage($page);
        if ($rev) {
            $r = $p->getRevision($rev);
            if (!$r) {
                return $this->error(sprintf(
                    _("%s(%d): no such revision"),
                    $page,
                    $rev
                ));
            }
        } else {
            $r = $p->getCurrentRevision();
        }
        $initial_content = $r->getPackedContent();
        $c = explode("\n", $initial_content);

        if ($section) {
            $c = extractSection($section, $c, $page, $quiet, $sectionhead);
            $initial_content = implode("\n", $c);
        }

        if (preg_match('/<noinclude>.+<\/noinclude>/s', $initial_content)) {
            $initial_content = preg_replace(
                "/<noinclude>.+?<\/noinclude>/s",
                "",
                $initial_content
            );
        }
        if (preg_match('/%%\w+%%/', $initial_content)) { // need variable expansion
            $var = array();
            if (!empty($vars)) {
                foreach (preg_split("/&/D", $vars) as $pair) {
                    list($key,$val) = preg_split("/=/D", $pair);
                    $var[$key] = $val;
                }
            }
            $thispage = $dbi->getPage($basepage);
            // pagename is not overridable
            if (empty($var['pagename'])) {
                $var['pagename'] = $page;
            }
            // those are overridable
            if (empty($var['mtime']) and preg_match('/%%mtime%%/', $initial_content)) {
                $thisrev  = $thispage->getCurrentRevision(false);
                $var['mtime'] = $GLOBALS['WikiTheme']->formatDateTime($thisrev->get('mtime'));
            }
            if (empty($var['ctime']) and preg_match('/%%ctime%%/', $initial_content)) {
                if ($first = $thispage->getRevision(1, false)) {
                    $var['ctime'] = $GLOBALS['WikiTheme']->formatDateTime($first->get('mtime'));
                }
            }
            if (empty($var['author']) and preg_match('/%%author%%/', $initial_content)) {
                $var['author'] = $thispage->getAuthor();
            }
            if (empty($var['owner']) and preg_match('/%%owner%%/', $initial_content)) {
                $var['owner'] = $thispage->getOwner();
            }
            if (empty($var['creator']) and preg_match('/%%creator%%/', $initial_content)) {
                $var['creator'] = $thispage->getCreator();
            }
            foreach (array("SERVER_URL", "DATA_PATH", "SCRIPT_NAME", "PHPWIKI_BASE_URL") as $c) {
                // constants are not overridable
                if (preg_match('/%%' . $c . '%%/', $initial_content)) {
                    $var[$c] = constant($c);
                }
            }
            if (preg_match('/%%BASE_URL%%/', $initial_content)) {
                $var['BASE_URL'] = PHPWIKI_BASE_URL;
            }

            foreach ($var as $key => $val) {
                $initial_content = preg_replace('/%%' . preg_quote($key, '/') . '%%/', $val, $initial_content);
            }
        }

        array_push($included_pages, $page);

        include_once('lib/BlockParser.php');
        $content = TransformText($initial_content, $r->get('markup'), $page);

        array_pop($included_pages);

        return HTML::div(array('class' => 'template'), $content);
    }
}

// $Log: Template.php,v $
// Revision 1.4  2005/09/11 13:30:22  rurban
// improve comments
//
// Revision 1.3  2005/09/10 20:43:19  rurban
// support <noinclude>
//
// Revision 1.2  2005/09/10 20:07:16  rurban
// fix BASE_URL
//
// Revision 1.1  2005/09/10 19:59:38  rurban
// Parametrized page inclusion ala mediawiki
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
