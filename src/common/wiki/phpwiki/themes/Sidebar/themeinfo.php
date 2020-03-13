<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * This file defines the Sidebar appearance ("theme") of PhpWiki,
 * which can be used as parent class for all sidebar themes. See blog.
 * This use the dynamic jscalendar, which doesn't need extra requests
 * per month/year change.
 */

require_once('lib/Theme.php');
require_once('lib/WikiPlugin.php');

class Theme_Sidebar extends Theme
{

    public function __construct($theme_name = 'Sidebar')
    {
        parent::__construct($theme_name);

        $this->calendarInit(true);
    }

    public function findTemplate($name)
    {
        // hack for navbar.tmpl to hide the buttonseparator
        if ($name == "navbar") {
            $this->setButtonSeparator(HTML::Raw("<br />\n&nbsp;&middot;&nbsp;"));
        }
        if ($name == "actionbar" || $name == "signin") {
            $this->setButtonSeparator(" ");
        }
        return parent::findTemplate($name);
    }

    public function calendarLink($date = false)
    {
        return $this->calendarBase() . SUBPAGE_SEPARATOR .
               strftime("%Y-%m-%d", $date ? $date : time());
    }

    public function calendarBase()
    {
        static $UserCalPageTitle = false;
        global $request;

        if (!$UserCalPageTitle) {
            $UserCalPageTitle = $request->_user->getId() .
                                SUBPAGE_SEPARATOR . _("Calendar");
        }
        if (!$UserCalPageTitle) {
            $UserCalPageTitle = (BLOG_EMPTY_DEFAULT_PREFIX ? ''
                                 : ($request->_user->getId() . SUBPAGE_SEPARATOR)) . "Blog";
        }
        return $UserCalPageTitle;
    }

    public function calendarInit($force = false)
    {
        $dbi = $GLOBALS['request']->getDbh();
        // display flat calender dhtml in the sidebar
        if ($force or $dbi->isWikiPage($this->calendarBase())) {
            $jslang = @$GLOBALS['LANG'];
            $this->addMoreHeaders(
                $this->_CSSlink(
                    0,
                    $this->_findFile('jscalendar/calendar-phpwiki.css'),
                    'all'
                )
            );
            $this->addMoreHeaders(JavaScript(
                '',
                array('src' => $this->_findData('jscalendar/calendar' . (DEBUG ? '' : '_stripped') . '.js'))
            ));
            if (!($langfile = $this->_findData("jscalendar/lang/calendar-$jslang.js"))) {
                $langfile = $this->_findData("jscalendar/lang/calendar-en.js");
            }
            $this->addMoreHeaders(JavaScript('', array('src' => $langfile)));
            $this->addMoreHeaders(JavaScript(
                '',
                array('src' =>
                $this->_findData('jscalendar/calendar-setup' . (DEBUG ? '' : '_stripped') . '.js'))
            ));

            // Get existing date entries for the current user
            require_once("lib/TextSearchQuery.php");
            $iter = $dbi->titleSearch(new TextSearchQuery("^" . $this->calendarBase() . SUBPAGE_SEPARATOR, true, "auto"));
            $existing = array();
            while ($page = $iter->next()) {
                if ($page->exists()) {
                    $existing[] = basename($page->_pagename);
                }
            }
            if (!empty($existing)) {
                $js_exist = '{"' . join('":1,"', $existing) . '":1}';
                //var SPECIAL_DAYS = {"2004-05-11":1,"2004-05-12":1,"2004-06-01":1}
                $this->addMoreHeaders(JavaScript('
// This table holds the existing calender entries for the current user
// calculated from the database
var SPECIAL_DAYS = ' . $js_exist . ';
// This function returns true if the date exists in SPECIAL_DAYS
function dateExists(date, y, m, d) {
    var year = date.getFullYear();
    m = m + 1;
    m = m < 10 ? "0" + m : m;  // integer, 0..11
    d = d < 10 ? "0" + d : d;  // integer, 1..31
    var date = year+"-"+m+"-"+d;
    var exists = SPECIAL_DAYS[date];
    if (!exists) return false;
    else return true;
}
// This is the actual date status handler. 
// Note that it receives the date object as well as separate 
// values of year, month and date.
function dateStatusFunc(date, y, m, d) {
    if (dateExists(date, y, m, d)) return "existing";
    else return false;
}'));
            } else {
                $this->addMoreHeaders(JavaScript('
function dateStatusFunc(date, y, m, d) { return false;}'));
            }
        }
    }
}

$WikiTheme = new Theme_Sidebar('Sidebar');

// CSS file defines fonts, colors and background images for this
// style.  The companion '*-heavy.css' file isn't defined, it's just
// expected to be in the same directory that the base style is in.

$WikiTheme->setDefaultCSS(_("Sidebar"), 'sidebar.css');
//$WikiTheme->addAlternateCSS('PhpWiki', 'phpwiki.css');
//$WikiTheme->setDefaultCSS('PhpWiki', 'phpwiki.css');
$WikiTheme->addAlternateCSS(_("Printer"), 'phpwiki-printer.css', 'print, screen');
$WikiTheme->addAlternateCSS(_("Modern"), 'phpwiki-modern.css');

/**
 * The logo image appears on every page and links to the HomePage.
 */
//$WikiTheme->addImageAlias('logo', 'logo.png');

/**
 * The Signature image is shown after saving an edited page. If this
 * is not set, any signature defined in index.php will be used. If it
 * is not defined by index.php or in here then the "Thank you for
 * editing..." screen will be omitted.
 */

// Comment this next line out to enable signature.
$WikiTheme->addImageAlias('signature', false);

/*
 * Link icons.
 */
$WikiTheme->setLinkIcon('http');
$WikiTheme->setLinkIcon('https');
$WikiTheme->setLinkIcon('ftp');
$WikiTheme->setLinkIcon('mailto');
$WikiTheme->setLinkIcon('interwiki');
$WikiTheme->setLinkIcon('*', 'url');

//$WikiTheme->setButtonSeparator(' | ');

/**
 * WikiWords can automatically be split by inserting spaces between
 * the words. The default is to leave WordsSmashedTogetherLikeSo.
 */
$WikiTheme->setAutosplitWikiWords(true);

/**
 * If true (default) show create '?' buttons on not existing pages, even if the
 * user is not signed in.
 * If false, anon users get no links and it looks cleaner, but then they
 * cannot easily fix missing pages.
 */
$WikiTheme->setAnonEditUnknownLinks(false);

/*
 * You may adjust the formats used for formatting dates and times
 * below.  (These examples give the default formats.)
 * Formats are given as format strings to PHP strftime() function See
 * http://www.php.net/manual/en/function.strftime.php for details.
 * Do not include the server's zone (%Z), times are converted to the
 * user's time zone.
 */
//$WikiTheme->setDateFormat("%B %d, %Y");


// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
