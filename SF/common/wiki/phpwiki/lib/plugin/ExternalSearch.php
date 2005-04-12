<?php // -*-php-*-
rcs_id('$Id$');
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

//require_once("lib/interwiki.php");

/**
 */
class WikiPlugin_ExternalSearch
extends WikiPlugin
{
    function getName () {
        return _("ExternalSearch");
    }

    function getDescription () {
        return _("Redirects to an external web site based on form input");
        //fixme: better description
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function _getInterWikiUrl(&$request) {
        $intermap = getInterwikiMap();
        $map = $intermap->_map;

        if (in_array($this->_url, array_keys($map))) {
            if (empty($this->_name))
                $this->_name = $this->_url;
            $this->_url = sprintf($map[$this->_url],'%s');
        }
        if (empty($this->_name))
            $this->_name = $this->getName();
    }

    function getDefaultArguments() {
        return array('s'        => false,
                     'formsize' => 30,
                     'url'      => false,
                     'name'     => '',
                     'debug'    => false
                     );
    }

    function run($dbi, $argstr, &$request, $basepage) {
        $args = $this->getArgs($argstr, $request);
        if (empty($args['url']))
            return '';

        extract($args);

        $posted = $GLOBALS['HTTP_POST_VARS'];
        if (in_array('url', array_keys($posted))) {
            $s = $posted['s'];
            $this->_url = $posted['url'];
            $this->_getInterWikiUrl($request);
            if (strstr($this->_url, '%s')) {
                $this->_url = sprintf($this->_url, $s);
            } else
                $this->_url .= $s;

            if ($debug) {
                trigger_error("redirect url: " . $this->_url);
            } else
                $request->redirect($this->_url); //no return!
        }

        $this->_name = $name;

        $this->_s = $s;
        if ($formsize < 1)
            $formsize = 30;
        $this->_url = $url;

        $this->_getInterWikiUrl($request);

        $form = HTML::form(array('action' => $request->getPostURL(),
                                 'method' => 'post',
                                 //'class'  => 'class', //fixme
                                 'accept-charset' => $GLOBALS['charset']),
                           HiddenInputs(array('pagename' => $basepage)));

        $form->pushContent(HTML::input(array('type' => 'text',
                                             'value' => $this->_s,
                                             'name'  => 's',
                                             'size'  => $formsize)));

        $form->pushContent(HTML::input(array('type' => 'hidden',
                                             'name'  => 'url',
                                             'value' => $this->_url)));

        $form->pushContent(HTML::input(array('type' => 'submit',
                                             'class' => 'button',
                                             'value' => $this->_name)));
        return $form;
    }
};

// $Log$
// Revision 1.1  2005/04/12 13:33:33  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.9  2004/04/19 18:27:46  rurban
// Prevent from some PHP5 warnings (ref args, no :: object init)
//   php5 runs now through, just one wrong XmlElement object init missing
// Removed unneccesary UpgradeUser lines
// Changed WikiLink to omit version if current (RecentChanges)
//
// Revision 1.8  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
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
