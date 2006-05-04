<?php
rcs_id('$Id: themeinfo.php 2085 2005-09-28 14:44:54Z nterray $');

/*
 * This file defines the Sidebar appearance ("theme") of PhpWiki.
 */

require_once('lib/Theme.php');

class Theme_Sidebar extends Theme {

    function findTemplate ($name) {
        // hack for navbar.tmpl to hide the buttonseparator
        if ($name == "navbar") {
            //$old = $Theme->getButtonSeparator();
            $this->setButtonSeparator(HTML::Raw('<br /> &middot; '));
            //$this->setButtonSeparator("\n");
            //$Theme->setButtonSeparator($old);
        }
        if ($name == "actionbar" || $name == "signin") {
            //$old = $Theme->getButtonSeparator();
            //$this->setButtonSeparator(HTML::br());
            $this->setButtonSeparator(" ");
            //$Theme->setButtonSeparator($old);
        }
        return $this->_path . $this->_findFile("templates/$name.tmpl");
    }

    function calendarLink($date = false) {
        return $this->calendarBase() . SUBPAGE_SEPARATOR . 
               strftime("%Y-%m-%d", $date ? $date : time());
    }

    function calendarBase() {
        static $UserCalPageTitle = false;
        if (!$UserCalPageTitle) 
            $UserCalPageTitle = $GLOBALS['request']->_user->getId() . 
                                SUBPAGE_SEPARATOR . _("Calendar");
        return $UserCalPageTitle;
    }
}
$Theme = new Theme_Sidebar('Sidebar');

$dbi = $GLOBALS['request']->getDbh();
// display flat calender dhtml under the clock
if ($dbi->isWikiPage($Theme->calendarBase())) {
    $jslang = @$GLOBALS['LANG'];
    $Theme->addMoreHeaders($Theme->_CSSlink(0,
        $Theme->_findFile('jscalendar/calendar-phpwiki.css'),'all'));
    $Theme->addMoreHeaders("\n");
    $Theme->addMoreHeaders(JavaScript('',
        array('src' => $Theme->_findData('jscalendar/calendar_stripped.js'))));
    $Theme->addMoreHeaders("\n");
    if (!($langfile = $Theme->_findData("jscalendar/lang/calendar-$jslang.js")))
        $langfile = $Theme->_findData("jscalendar/lang/calendar-en.js");
    $Theme->addMoreHeaders(JavaScript('',array('src' => $langfile)));
    $Theme->addMoreHeaders("\n");
    $Theme->addMoreHeaders(JavaScript('',
        array('src' => $Theme->_findData('jscalendar/calendar-setup_stripped.js'))));
    $Theme->addMoreHeaders("\n");
}

// CSS file defines fonts, colors and background images for this
// style.  The companion '*-heavy.css' file isn't defined, it's just
// expected to be in the same directory that the base style is in.

//$Theme->setDefaultCSS(_("Sidebar"), 'sidebar.css');
//$Theme->addAlternateCSS('PhpWiki', 'phpwiki.css');
$Theme->setDefaultCSS('PhpWiki', 'phpwiki.css');
$Theme->addAlternateCSS(_("Printer"), 'phpwiki-printer.css', 'print, screen');
$Theme->addAlternateCSS(_("Modern"), 'phpwiki-modern.css');

/**
 * The logo image appears on every page and links to the HomePage.
 */
//$Theme->addImageAlias('logo', 'logo.png');

/**
 * The Signature image is shown after saving an edited page. If this
 * is not set, any signature defined in index.php will be used. If it
 * is not defined by index.php or in here then the "Thank you for
 * editing..." screen will be omitted.
 */

// Comment this next line out to enable signature.
$Theme->addImageAlias('signature', false);

/*
 * Link icons.
 */
$Theme->setLinkIcon('http');
$Theme->setLinkIcon('https');
$Theme->setLinkIcon('ftp');
$Theme->setLinkIcon('mailto');
$Theme->setLinkIcon('interwiki');
$Theme->setLinkIcon('*', 'url');

//$Theme->setButtonSeparator(' | ');

/**
 * WikiWords can automatically be split by inserting spaces between
 * the words. The default is to leave WordsSmashedTogetherLikeSo.
 */
$Theme->setAutosplitWikiWords(true);

/**
 * If true (default) show create '?' buttons on not existing pages, even if the 
 * user is not signed in.
 * If false, anon users get no links and it looks cleaner, but then they 
 * cannot easily fix missing pages.
 */
$Theme->setAnonEditUnknownLinks(false);

/*
 * You may adjust the formats used for formatting dates and times
 * below.  (These examples give the default formats.)
 * Formats are given as format strings to PHP strftime() function See
 * http://www.php.net/manual/en/function.strftime.php for details.
 * Do not include the server's zone (%Z), times are converted to the
 * user's time zone.
 */
//$Theme->setDateFormat("%B %d, %Y");


// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
