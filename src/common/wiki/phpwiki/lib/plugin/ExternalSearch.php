<?php
// -*-php-*-
rcs_id('$Id: ExternalSearch.php,v 1.12 2004/11/28 20:42:33 rurban Exp $');
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

/**
 * Redirects to an external web site based on form input.
 * See http://phpwiki.sourceforge.net/phpwiki/ExternalSearchPlugin
 *
 * useimage sample:
   ExternalSearch
     url="http://www.geourl.org/near/?xsize=2048&ysize=1024&xoffset=1650&yoffset=550"
     useimage="http://www.geourl.org/maps/au.png"
     name="Go Godzilla All Over It"
 */

class WikiPlugin_ExternalSearch extends WikiPlugin
{
    public function getName()
    {
        return _("ExternalSearch");
    }

    public function getDescription()
    {
        return _("Redirects to an external web site based on form input");
        //fixme: better description
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.12 $"
        );
    }

    public function _getInterWikiUrl(&$request)
    {
        $intermap = getInterwikiMap();
        $map = $intermap->_map;

        if (in_array($this->_url, array_keys($map))) {
            if (empty($this->_name)) {
                $this->_name = $this->_url;
            }
            $this->_url = sprintf($map[$this->_url], '%s');
        }
        if (empty($this->_name)) {
            $this->_name = $this->getName();
        }
    }

    public function getDefaultArguments()
    {
        return array('s'        => false,
                     'formsize' => 30,
                     'url'      => false,
                     'name'     => '',
                     'useimage' => false,
                     'width'    => false,
                     'height'   => false,
                     'debug'    => false
                     );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        if (empty($args['url'])) {
            return '';
        }

        extract($args);

        $posted = $_POST;
        if (in_array('url', array_keys($posted))) {
            $s = $posted['s'];
            $this->_url = $posted['url'];
            $this->_getInterWikiUrl($request);
            if (strstr($this->_url, '%s')) {
                $this->_url = sprintf($this->_url, $s);
            } else {
                $this->_url .= $s;
            }
            if ($debug) {
                trigger_error("redirect url: " . $this->_url);
            } else {
                $request->redirect($this->_url); //no return!
            }
        }
        $this->_name = $name;
        $this->_s = $s;
        if ($formsize < 1) {
            $formsize = 30;
        }
        $this->_url = $url;
        $this->_getInterWikiUrl($request);
        $form = HTML::form(
            array('action' => $request->getPostURL(),
                                 'method' => 'post',
                                 //'class'  => 'class', //fixme
                                 'accept-charset' => $GLOBALS['charset']),
            HiddenInputs(array('pagename' => $basepage))
        );

        $form->pushContent(HTML::input(array('type' => 'hidden',
                                             'name'  => 'url',
                                             'value' => $this->_url)));
        if (!empty($args["useimage"])) {
            //FIXME: This does not work with Gecko
            $button = HTML::img(array('src' => $useimage, 'alt' => 'imagebutton'));
            if (!empty($width)) {
                $button->setAttr('width', $width);
            }
            if (!empty($height)) {
                $button->setAttr('height', $height);
            }
            $form->pushContent(HTML::button(
                array('type' => 'button',
                                                  'class' => 'button',
                                                  'value' => $this->_name,
                                                  ),
                $button
            ));
        } else {
            $form->pushContent(HTML::input(array('type' => 'submit',
                                                 'class' => 'button',
                                                 'value' => $this->_name)));
            $form->pushContent(HTML::input(array('type' => 'text',
                                                 'value' => $this->_s,
                                                 'name'  => 's',
                                                 'size'  => $formsize)));
        }
        return $form;
    }
}

// $Log: ExternalSearch.php,v $
// Revision 1.12  2004/11/28 20:42:33  rurban
// Optimize PearDB _extract_version_data and _extract_page_data.
//
// Revision 1.11  2004/09/17 14:25:45  rurban
// update comments
//
// Revision 1.10  2004/05/17 13:36:49  rurban
// Apply RFE #952323 "ExternalSearchPlugin improvement", but
//   with <button><img></button>
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
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
