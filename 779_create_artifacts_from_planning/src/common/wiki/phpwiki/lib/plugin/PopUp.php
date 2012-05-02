<?php // -*-php-*-
rcs_id('$Id: PopUp.php,v 1.1 2004/11/24 19:03:30 rurban Exp $');
/**
 Copyright 2004 Nicolas Noble <pixels@users.sf.net>

 This file is (not yet) part of PhpWiki.

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
 * Display a page in a clickable popup link.
 *
 * Usage:
 * <?plugin PopUp
 *     link="HomePage"
 *     title="PopUpped HomePage"
 *     text="Click here to popup the HomePage"
 *     width=300
 *     height=200
 *     resizable=no
 *     scrollbars=no
 *     toolbar=no
 *     location=no
 *     directories=no
 *     status=no
 *     menubar=no
 *     copyhistory=no
 * ?>
 * <?plugin PopUp close=yes ?>
 */

class WikiPlugin_PopUp
extends WikiPlugin
{
    function getName () {
        return _("PopUp");
    }
    function getDescription () {
        return _("Used to create a clickable popup link.");

    }
    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.1 $");
    }
    function getDefaultArguments() {
        return array('link'        => "HomePage",
	             'title'       => "",
	             'text'        => "",
		     'width'       => "500",
		     'height'      => "400",
		     'resizable'   => "no",
		     'scrollbars'  => "no",
		     'toolbar'     => "no",
		     'location'    => "no",
		     'directories' => "no",
		     'status'      => "no",
		     'menubar'     => "no",
		     'copyhistory' => "no",
		     'close'       => "no",
		    );
    }

    function run($dbi, $argstr, &$request, $basepage) {
        extract($this->getArgs($argstr, $request));
        return HTML::a(array('href' => WikiURL($link),
	                      'target' => "_blank",
	                      'onClick' => ($close == "yes" ? "window.close()" : ("window.open('" .
			          WikiURL($link) . "', '" .
				  ($title == "" ? ($text == "" ? $link : $text) : $title) . "', '" .
				  "width=$width," .
				  "height=$height," .
				  "resizable=$resizable," .
				  "scrollbars=$scrollbars," .
				  "toolbar=$toolbar," .
				  "location=$location," .
				  "directories=$directories," .
				  "status=$status," .
				  "menubar=$menubar," .
				  "copyhistory=$copyhistory')"
				  )) . ";return false;"
	                     ),
		        ($text == "" ? ($close == "yes" ? "Close window" : $link) : $text)
		       );
    }
};

// $Log: PopUp.php,v $
// Revision 1.1  2004/11/24 19:03:30  rurban
// new plugin by Nicolas Noble
//
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>