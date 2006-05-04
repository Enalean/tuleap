<?php // -*-php-*-
rcs_id('$Id: AllPages.php 2691 2006-03-02 15:31:51Z guerin $');
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

require_once('lib/PageList.php');

/**
 */
class WikiPlugin_AllPages
extends WikiPlugin
{
    function getName () {
        return _("AllPages");
    }

    function getDescription () {
        return _("List all pages in this wiki.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 2691 $");
    }

    function getDefaultArguments() {
        return array('noheader'      => false,
                     'include_empty' => false,
                     'exclude'       => '',
                     'info'          => '',
                     'sortby'        => 'pagename',   // +mtime,-pagename
                     'limit'         => 0,
                     'paging'        => 'auto',
                     'debug'         => false
                     );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor,markup or all
    // exclude arg allows multiple pagenames exclude=HomePage,RecentChanges
    // sortby: [+|-] pagename|mtime|hits

    function run($dbi, $argstr, &$request, $basepage) {
        $args = $this->getArgs($argstr, $request);
        extract($args);
        // Todo: extend given _GET args
        if ($sorted = $request->getArg('sortby'))
            $sortby = $sorted;
        elseif ($sortby)
            $request->setArg('sortby',$sortby);

        if (! $request->getArg('count'))  $args['count'] = $dbi->numPages(false,$exclude);
        else $args['count'] = $request->getArg('count');
        $pagelist = new PageList($info, $exclude, $args);
        //if (!$sortby) $sorted='pagename';
        if (!$noheader)
            $pagelist->setCaption(_("Pages in this wiki (%d total):"));

        // deleted pages show up as version 0.
        if ($include_empty)
            $pagelist->_addColumn('version');

        //if (defined('DEBUG') and DEBUG) $debug = true;
        if ($debug)
            $timer = new DebugTimer;
        $pagelist->addPages( $dbi->getAllPages($include_empty, $sortby, $limit) );
        if ($debug) {
            return HTML($pagelist,
                        HTML::p(fmt("Elapsed time: %s s", $timer->getStats())));
        } else {
            return $pagelist;
        }
    }

    function getmicrotime(){
        list($usec, $sec) = explode(" ",microtime());
        return (float)$usec + (float)$sec;
    }
};

// $Log$
// Revision 1.21  2004/04/20 00:06:53  rurban
// paging support
//
// Revision 1.20  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.19  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.18  2004/01/25 07:58:30  rurban
// PageList sortby support in PearDB and ADODB backends
//
// Revision 1.17  2003/02/27 20:10:30  dairiki
// Disable profiling output when DEBUG is defined but false.
//
// Revision 1.16  2003/02/21 04:08:26  dairiki
// New class DebugTimer in prepend.php to help report timing.
//
// Revision 1.15  2003/01/18 21:19:25  carstenklapp
// Code cleanup:
// Reformatting; added copyleft, getVersion, getDescription
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
