<?php
// display.php: fetch page or get default content
rcs_id('$Id: display.php,v 1.65 2005/05/05 08:54:40 rurban Exp $');

require_once __DIR__ . '/Template.php';

/**
 * Extract keywords from Category* links on page.
 */
function GleanKeywords($page)
{
    if (!defined('KEYWORDS')) {
        return '';
    }
    include_once("lib/TextSearchQuery.php");
    $search = new TextSearchQuery(KEYWORDS, true);
    $KeywordLinkRegexp = $search->asRegexp();
    // iterate over the pagelinks (could be a large number) [15ms on PluginManager]
    // or do a titleSearch and check the categories if they are linked?
    $links = $page->getPageLinks();
    $keywords[] = SplitPagename($page->getName());
    while ($link = $links->next()) {
        if (preg_match($KeywordLinkRegexp, $link->getName(), $m)) {
            $keywords[] = SplitPagename($m[0]);
        }
    }
    $keywords[] = WIKI_NAME;
    return join(', ', $keywords);
}

/** Make a link back to redirecting page.
 *
 * @param $pagename string  Name of redirecting page.
 * @return XmlContent Link to the redirecting page.
 */
function RedirectorLink($pagename)
{
    $url = WikiURL($pagename, array('redirectfrom' => ''));
    return HTML::a(
        array('class' => 'redirectfrom wiki',
                         'href' => $url),
        $pagename
    );
}


function actionPage(&$request, $action)
{
    global $WikiTheme;

    $pagename = $request->getArg('pagename');
    $version = $request->getArg('version');

    $page = $request->getPage();
    $revision = $page->getCurrentRevision();

    $dbi = $request->getDbh();
    $actionpage = $dbi->getPage($action);
    $actionrev = $actionpage->getCurrentRevision();

    $pagetitle = HTML(fmt(
        "%s: %s",
        $actionpage->getName(),
        $WikiTheme->linkExistingWikiWord($pagename, false, $version)
    ));

    $validators = new HTTP_ValidatorSet(array('pageversion' => $revision->getVersion(),
                                              '%mtime' => $revision->get('mtime')));

    $request->appendValidators(array('pagerev' => $revision->getVersion(),
                                     '%mtime' => $revision->get('mtime')));
    $request->appendValidators(array('actionpagerev' => $actionrev->getVersion(),
                                     '%mtime' => $actionrev->get('mtime')));

    $transformedContent = $actionrev->getTransformedContent();
    $template = Template('browse', array('CONTENT' => $transformedContent));
/*
    if (!headers_sent()) {
        //FIXME: does not work yet. document.write not supported (signout button)
        // http://www.w3.org/People/mimasa/test/xhtml/media-types/results
        if (ENABLE_XHTML_XML
            and (!isBrowserIE() and
                 strstr($request->get('HTTP_ACCEPT'),'application/xhtml+xml')))
            header("Content-Type: application/xhtml+xml; charset=" . $GLOBALS['charset']);
        else
            header("Content-Type: text/html; charset=" . $GLOBALS['charset']);
    }
*/
    GeneratePage($template, $pagetitle, $revision);
    $request->checkValidators();
    flush();
}

function displayPage(&$request, $template = false)
{
    global $WikiTheme, $pv;
    $pagename = $request->getArg('pagename');
    $version = $request->getArg('version');
    $page = $request->getPage();
    if ($version) {
        $revision = $page->getRevision($version);
        if (!$revision) {
            NoSuchRevision($request, $page, $version);
        }
    } else {
        $revision = $page->getCurrentRevision();
    }

    if (isSubPage($pagename)) {
        $pages = explode(SUBPAGE_SEPARATOR, $pagename);
        $last_page = array_pop($pages); // deletes last element from array as side-effect
        $pageheader = HTML::span(HTML::a(
            array('href' => WikiURL($pages[0]),
                                              'class' => 'pagetitle'
                                              ),
            $WikiTheme->maybeSplitWikiWord($pages[0] . SUBPAGE_SEPARATOR)
        ));
        $first_pages = $pages[0] . SUBPAGE_SEPARATOR;
        array_shift($pages);
        foreach ($pages as $p) {
            if ($pv != 2) {    //Add the Backlink in page title
                   $pageheader->pushContent(HTML::a(
                       array('href' => WikiURL($first_pages . $p),
                                                  'class' => 'backlinks'),
                       $WikiTheme->maybeSplitWikiWord($p . SUBPAGE_SEPARATOR)
                   ));
            } else {    // Remove Backlinks
                $pageheader->pushContent(HTML::h1($pagename));
            }
            $first_pages .= $p . SUBPAGE_SEPARATOR;
        }
        if ($pv != 2) {
            $backlink = HTML::a(
                array('href' => WikiURL(
                    $pagename,
                    array('action' => _("BackLinks"))
                ),
                      'class' => 'backlinks'),
                $WikiTheme->maybeSplitWikiWord($last_page)
            );
            $backlink->addTooltip(sprintf(_("BackLinks for %s"), $pagename));
        } else {
            $backlink = HTML::h1($pagename);
        }
        $pageheader->pushContent($backlink);
    } else {
        if ($pv != 2) {
            $pageheader = HTML::a(
                array('href' => WikiURL(
                    $pagename,
                    array('action' => _("BackLinks"))
                ),
                       'class' => 'backlinks'),
                $WikiTheme->maybeSplitWikiWord($pagename)
            );
            $pageheader->addTooltip(sprintf(_("BackLinks for %s"), $pagename));
        } else {
            $pageheader = HTML::h1($pagename); //Remove Backlinks
        }
        if ($request->getArg('frame')) {
            $pageheader->setAttr('target', '_top');
        }
    }

    // {{{ Codendi hook to insert stuff between navbar and header
    $eM = EventManager::instance();

    $ref_html = '';
    $crossref_fact = new CrossReferenceFactory($pagename, ReferenceManager::REFERENCE_NATURE_WIKIPAGE, GROUP_ID);
    $crossref_fact->fetchDatas();
    if ($crossref_fact->getNbReferences() > 0) {
        $ref_html .= '<h3>' . $GLOBALS['Language']->getText('cross_ref_fact_include', 'references') . '</h3>';
        $ref_html .= $crossref_fact->getHTMLDisplayCrossRefs();
    }

    $additional_html = false;
    $eM->processEvent('wiki_before_content', array(
                    'html' => &$additional_html,
                    'group_id' => GROUP_ID,
                    'wiki_page' => $pagename
        ));
    if ($additional_html) {
        $beforeHeader = HTML();
        $beforeHeader->pushContent($additional_html);
        $beforeHeader->pushContent(HTML::raw($ref_html));
        $toks['BEFORE_HEADER'] = $beforeHeader;
    } else {
        $beforeHeader = HTML();
        $beforeHeader->pushContent(HTML::raw($ref_html));
        $toks['BEFORE_HEADER'] = $beforeHeader;
    }
    // }}} /Codendi hook

    $pagetitle = SplitPagename($pagename);
    if (($redirect_from = $request->getArg('redirectfrom'))) {
        $redirect_message = HTML::span(
            array('class' => 'redirectfrom'),
            fmt(
                "(Redirected from %s)",
                RedirectorLink($redirect_from)
            )
        );
    // abuse the $redirected template var for some status update notice
    } elseif ($request->getArg('errormsg')) {
        $redirect_message = $request->getArg('errormsg');
        $request->setArg('errormsg', false);
    }

    $request->appendValidators(array('pagerev' => $revision->getVersion(),
                                     '%mtime' => $revision->get('mtime')));

    $page_content = $revision->getTransformedContent();

    // if external searchengine (google) referrer, highlight the searchterm
    // FIXME: move that to the transformer?
    // OR: add the searchhightplugin line to the content?
    if ($result = isExternalReferrer($request)) {
        if (DEBUG and !empty($result['query'])) {
            //$GLOBALS['SearchHighlightQuery'] = $result['query'];
            /* simply add the SearchHighlight plugin to the top of the page.
               This just parses the wikitext, and doesn't highlight the markup */
            include_once('lib/WikiPlugin.php');
            $loader = new WikiPluginLoader();
            $xml = $loader->expandPI('<' . '?plugin SearchHighlight s="' . $result['query'] . '"?' . '>', $request, $markup);
            if ($xml and is_array($xml)) {
                foreach (array_reverse($xml) as $line) {
                    array_unshift($page_content->_content, $line);
                }
                array_unshift(
                    $page_content->_content,
                    HTML::div(_("You searched for: "), HTML::strong($result['query']))
                );
            }

            if (0) {
            /* Parse the transformed (mixed HTML links + strings) lines?
               This looks like overkill.
             */
                require_once("lib/TextSearchQuery.php");
                $query = new TextSearchQuery($result['query']);
                $hilight_re = $query->getHighlightRegexp();
            //$matches = preg_grep("/$hilight_re/i", $revision->getContent());
            // FIXME!
                for ($i = 0; $i < count($page_content->_content); $i++) {
                    $found = false;
                    $line = $page_content->_content[$i];
                    if (is_string($line)) {
                        while (preg_match("/^(.*?)($hilight_re)/i", $line, $m)) {
                            $found = true;
                            $line = substr($line, strlen($m[0]));
                            $html[] = $m[1];    // prematch
                            $html[] = HTML::strong(array('class' => 'search-term'), $m[2]); // match
                        }
                    }
                    if ($found) {
                        $html[] = $line;  // postmatch
                        $page_content->_content[$i] = HTML::span(
                            array('class' => 'search-context'),
                            $html
                        );
                    }
                }
            }
        }
    }

    $toks['CONTENT'] = new Template('browse', $request, $page_content);

    $toks['TITLE'] = $pagetitle;   // <title> tag
    $toks['HEADER'] = $pageheader; // h1 with backlink
    $toks['revision'] = $revision;
    if (!empty($redirect_message)) {
        $toks['redirected'] = $redirect_message;
    }
    $toks['ROBOTS_META'] = 'index,follow';
    $toks['PAGE_DESCRIPTION'] = $page_content->getDescription();
    $toks['PAGE_KEYWORDS'] = GleanKeywords($page);
    if (!$template) {
        $template = new Template('html', $request);
    }

    $template->printExpansion($toks);
    $page->increaseHitCount();

    $request->checkValidators();
    flush();
}

// $Log: display.php,v $
// Revision 1.65  2005/05/05 08:54:40  rurban
// fix pagename split for title and header
//
// Revision 1.64  2005/04/23 11:21:55  rurban
// honor theme-specific SplitWikiWord in the HEADER
//
// Revision 1.63  2004/11/30 17:48:38  rurban
// just comments
//
// Revision 1.62  2004/11/30 09:51:35  rurban
// changed KEYWORDS from pageprefix to search term. added installer detection.
//
// Revision 1.61  2004/11/21 11:59:19  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.60  2004/11/19 19:22:03  rurban
// ModeratePage part1: change status
//
// Revision 1.59  2004/11/17 20:03:58  rurban
// Typo: call SearchHighlight not SearchHighLight
//
// Revision 1.58  2004/11/09 17:11:16  rurban
// * revert to the wikidb ref passing. there's no memory abuse there.
// * use new wikidb->_cache->_id_cache[] instead of wikidb->_iwpcache, to effectively
//   store page ids with getPageLinks (GleanDescription) of all existing pages, which
//   are also needed at the rendering for linkExistingWikiWord().
//   pass options to pageiterator.
//   use this cache also for _get_pageid()
//   This saves about 8 SELECT count per page (num all pagelinks).
// * fix passing of all page fields to the pageiterator.
// * fix overlarge session data which got broken with the latest ACCESS_LOG_SQL changes
//
// Revision 1.57  2004/11/01 10:43:57  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.56  2004/10/14 13:44:14  rurban
// fix lib/display.php:159: Warning[2]: Argument to array_reverse() should be an array
//
// Revision 1.55  2004/09/26 14:58:35  rurban
// naive SearchHighLight implementation
//
// Revision 1.54  2004/09/17 14:19:41  rurban
// disable Content-Type header for now, until it is fixed
//
// Revision 1.53  2004/06/25 14:29:20  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.52  2004/06/14 11:31:37  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.51  2004/05/18 16:23:39  rurban
// rename split_pagename to SplitPagename
//
// Revision 1.50  2004/05/04 22:34:25  rurban
// more pdf support
//
// Revision 1.49  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
