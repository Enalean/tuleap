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
 * This file defines the default appearance ("theme") of PhpWiki.
 */

require_once('lib/Theme.php');

$WikiTheme = new Theme('Codendi');

// CSS file defines fonts, colors and background images for this
// style.  The companion '*-heavy.css' file isn't defined, it's just
// expected to be in the same directory that the base style is in.

// This should result in phpwiki-printer.css being used when
// printing or print-previewing with style "PhpWiki" or "MacOSX" selected.
$WikiTheme->setDefaultCSS(
    'PhpWiki',
    array(''      => 'phpwiki.css',
    'print' => 'phpwiki-printer.css')
);

// This allows one to manually select "Printer" style (when browsing page)
// to see what the printer style looks like.
$WikiTheme->addAlternateCSS(_("Printer"), 'phpwiki-printer.css', 'print, screen');
$WikiTheme->addAlternateCSS(_("Top & bottom toolbars"), 'phpwiki-topbottombars.css');
$WikiTheme->addAlternateCSS(_("Modern"), 'phpwiki-modern.css');

/**
 * The logo image appears on every page and links to the HomePage.
 */
$WikiTheme->addImageAlias('logo', WIKI_NAME . 'Logo.png');

/**
 * The Signature image is shown after saving an edited page. If this
 * is set to false then the "Thank you for editing..." screen will
 * be omitted.
 */

$WikiTheme->addImageAlias('signature', WIKI_NAME . "Signature.png");
// Uncomment this next line to disable the signature.
//$WikiTheme->addImageAlias('signature', false);

/*
 * Link icons.
 */
$WikiTheme->setLinkIcon('http');
$WikiTheme->setLinkIcon('https');
$WikiTheme->setLinkIcon('ftp');
$WikiTheme->setLinkIcon('mailto');
$WikiTheme->setLinkIcon('interwiki');
$WikiTheme->setLinkIcon('wikiuser');
$WikiTheme->setLinkIcon('*', 'url');

$WikiTheme->setButtonSeparator("\n | ");

/**
 * WikiWords can automatically be split by inserting spaces between
 * the words. The default is to leave WordsSmashedTogetherLikeSo.
 */
$WikiTheme->setAutosplitWikiWords(false);

/**
 * Layout improvement with dangling links for mostly closed wiki's:
 * If false, only users with edit permissions will be presented the
 * special wikiunknown class with "?" and Tooltip.
 * If true (default), any user will see the ?, but will be presented
 * the PrintLoginForm on a click.
 */
//$WikiTheme->setAnonEditUnknownLinks(false);

/*
 * You may adjust the formats used for formatting dates and times
 * below.  (These examples give the default formats.)
 * Formats are given as format strings to PHP strftime() function See
 * http://www.php.net/manual/en/function.strftime.php for details.
 * Do not include the server's zone (%Z), times are converted to the
 * user's time zone.
 *
 * Suggestion for french:
 *   $WikiTheme->setDateFormat("%A %e %B %Y");
 *   $WikiTheme->setTimeFormat("%H:%M:%S");
 * Suggestion for capable php versions, using the server locale:
 *   $WikiTheme->setDateFormat("%x");
 *   $WikiTheme->setTimeFormat("%X");
 */
//$WikiTheme->setDateFormat("%B %d, %Y");
//$WikiTheme->setTimeFormat("%I:%M %p");

/*
 * To suppress times in the "Last edited on" messages, give a
 * give a second argument of false:
 */
//$WikiTheme->setDateFormat("%B %d, %Y", false);


/**
 * Custom UserPreferences:
 * A list of name => _UserPreference class pairs.
 * Rationale: Certain themes should be able to extend the predefined list
 * of preferences. Display/editing is done in the theme specific userprefs.tmpl
 * but storage/sanification/update/... must be extended to the Get/SetPreferences methods.
 * See themes/wikilens/themeinfo.php
 */
//$WikiTheme->customUserPreference();

/**
 * Register custom PageList type and define custom PageList classes.
 * Rationale: Certain themes should be able to extend the predefined list
 * of pagelist types. E.g. certain plugins, like MostPopular might use
 * info=pagename,hits,rating
 * which displays the rating column whenever the wikilens theme is active.
 * See themes/wikilens/themeinfo.php
 */
//$WikiTheme->addPageListColumn();

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
