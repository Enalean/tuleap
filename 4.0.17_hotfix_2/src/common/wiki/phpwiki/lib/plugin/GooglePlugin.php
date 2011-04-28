<?php // -*-php-*-
rcs_id('$Id: GooglePlugin.php,v 1.5 2004/06/13 14:30:26 rurban Exp $');
/**
 Copyright 2004 $ThePhpWikiProgrammingTeam

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

require_once("lib/Google.php");

/**
 * This module is a wrapper for the Google Web APIs. It allows you to do Google searches, 
 * retrieve pages from the Google cache, and ask Google for spelling suggestions.
 *
 * Note: You must first obtain a license key at http://www.google.com/apis/
 * Max 1000 queries per day.
 *
 * Other possible sample usages:
 *   Auto-monitor the web for new information on a subject
 *   Glean market research insights and trends over time
 *   Invent a catchy online game
 *   Create a novel UI for searching
 *   Add Google's spell-checking to an application
 */
class WikiPlugin_GooglePlugin
extends WikiPlugin
{
    function getName () {
        return _("GooglePlugin");
    }

    function getDescription () {
        return _("Make use of the Google API");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.5 $");
    }

    function getDefaultArguments() {
        return array('q'          => '',
                     'mode'       => 'search', // or 'cache' or 'spell'
                     'startIndex' => 1,
                     'maxResults' => 10, // fixed to 10 for now by google
                     'formsize'   => 30,
                     // 'language' => `??
                     //'license_key'  => false,
                     );
    }

    function run($dbi, $argstr, &$request, $basepage) {
        $args = $this->getArgs($argstr, $request);
        //        if (empty($args['s']))
        //    return '';
        $html = HTML();
        extract($args);
        // prevent from dump
        if ($q and $request->isPost()) {
            require_once("lib/Google.php");
            $google = new Google();
            if (!$google) return '';
            switch ($mode) {
                case 'search': $result = $google->doGoogleSearch($q); break;
                case 'cache':  $result = $google->doGetCachedPage($q); break;
                case 'spell':  $result = $google->doSpellingSuggestion($q); break;
                default:
                	trigger_error("Invalid mode");
            }
            if (isa($result,'HTML'))
                $html->pushContent($result);
            if (isa($result,'GoogleSearchResults')) {
                //TODO: result template
                if (!empty($result->resultElements)) {
                    $list = HTML::ol();
                    foreach ($result->resultElements as $res) {
                    	$li = HTML::li(LinkURL($res['URL'],$res['directoryTitle']),HTML::br(),
                    	               $res['directoryTitle'] ? HTML(HTML::raw('&nbsp;&nbsp;'),HTML::em($res['summary']),' -- ',LinkURL($res['URL'])) : '');
                        $list->pushContent($li);
                    }
                    $html->pushContent($list);
                }
                else 
                    return _("Nothing found");
            }
            if (is_string($result)) {
                // cache content also?
                $html->pushContent(HTML::blockquote(HTML::raw($result)));
            }
        }
        if ($formsize < 1)  $formsize = 30;
        // todo: template
        $form = HTML::form(array('action' => $request->getPostURL(),
                                 'method' => 'post',
                                 //'class'  => 'class', //fixme
                                 'accept-charset' => $GLOBALS['charset']),
                           HiddenInputs(array('pagename' => $basepage,
                                              'mode' => $mode)));
        $form->pushContent(HTML::input(array('type' => 'text',
                                             'value' => $q,
                                             'name'  => 'q',
                                             'size'  => $formsize)));
        $form->pushContent(HTML::input(array('type' => 'submit',
                                             'class' => 'button',
                                             'value' => gettext($mode)
                                             )));
        return HTML($html,$form);
    }
};

// $Log: GooglePlugin.php,v $
// Revision 1.5  2004/06/13 14:30:26  rurban
// security fix: check permissions in SearchReplace
//
// Revision 1.4  2004/06/13 14:15:28  rurban
// GooglePlugin now actually works (templated result missing)
//
// Revision 1.3  2004/06/13 13:54:25  rurban
// Catch fatals on the four dump calls (as file and zip, as html and mimified)
// FoafViewer: Check against external requirements, instead of fatal.
// Change output for xhtmldumps: using file:// urls to the local fs.
// Catch SOAP fatal by checking for GOOGLE_LICENSE_KEY
// Import GOOGLE_LICENSE_KEY and FORTUNE_DIR from config.ini.
//
// Revision 1.2  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//
// Revision 1.1  2004/02/29 01:37:59  rurban
// New experimental feature: use the Google API directly
// Needs a free license key and the soap library nosoap,
// Todo: templates for search and results, some proxy debugging
//
// Revision 1.7  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.6  2004/02/19 22:06:53  rurban
// use new class, to be able to get rid of lib/interwiki.php
//
// Revision 1.5  2003/02/26 01:56:52  dairiki
// Tuning/fixing of POST action URLs and hidden inputs.
//
// Revision 1.4  2003/01/30 02:46:46  carstenklapp
// Bugfix: Plugin was redirecting to nonexistant local wiki page named
// "ExternalSearch" instead of the invoked url. Reported by Arthur Chereau.
//
// Revision 1.3  2003/01/18 21:41:01  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
