<?php
rcs_id('$Id$');
/**
 */
require_once('lib/Theme.php');

$Theme = new Theme('wikilens');

// CSS file defines fonts, colors and background images for this
// style.  The companion '*-heavy.css' file isn't defined, it's just
// expected to be in the same directory that the base style is in.

// This should result in phpwiki-printer.css being used when
// printing or print-previewing with style "PhpWiki" or "MacOSX" selected.
$Theme->setDefaultCSS('PhpWiki',
                       array(''      => 'phpwiki.css',
                             'print' => 'phpwiki-printer.css'));

// This allows one to manually select "Printer" style (when browsing page)
// to see what the printer style looks like.
$Theme->addAlternateCSS(_("Printer"), 'phpwiki-printer.css', 'print, screen');
$Theme->addAlternateCSS(_("Top & bottom toolbars"), 'phpwiki-topbottombars.css');
$Theme->addAlternateCSS(_("Modern"), 'phpwiki-modern.css');

/**
 * The logo image appears on every page and links to the HomePage.
 */
$Theme->addImageAlias('logo', WIKI_NAME . 'Logo.png');

/**
 * The Signature image is shown after saving an edited page. If this
 * is set to false then the "Thank you for editing..." screen will
 * be omitted.
 */

$Theme->addImageAlias('signature', WIKI_NAME . "Signature.png");
// Uncomment this next line to disable the signature.
$Theme->addImageAlias('signature', false);

/*
 * Link icons.
 */
//$Theme->setLinkIcon('http');
$Theme->setLinkIcon('https');
$Theme->setLinkIcon('ftp');
$Theme->setLinkIcon('mailto');
//$Theme->setLinkIcon('interwiki');
$Theme->setLinkIcon('wikiuser');
//$Theme->setLinkIcon('*', 'url');

//$Theme->setButtonSeparator("\n | ");

/**
 * WikiWords can automatically be split by inserting spaces between
 * the words. The default is to leave WordsSmashedTogetherLikeSo.
 */
//$Theme->setAutosplitWikiWords(false);

/**
 * Layout improvement with dangling links for mostly closed wiki's:
 * If false, only users with edit permissions will be presented the 
 * special wikiunknown class with "?" and Tooltip.
 * If true (default), any user will see the ?, but will be presented 
 * the PrintLoginForm on a click.
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
$Theme->setDateFormat("%B %d, %Y");
$Theme->setTimeFormat("%H:%M");

/*
 * To suppress times in the "Last edited on" messages, give a
 * give a second argument of false:
 */
//$Theme->setDateFormat("%B %d, %Y", false); 

/**
 * Custom UserPreferences:
 * A list of name => _UserPreference class pairs.
 * Rationale: Certain themes should be able to extend the predefined list 
 * of preferences. Display/editing is done in the theme specific userprefs.tmpl
 * but storage/sanification/update/... must be extended to the Get/SetPreferences methods.
 */

class _UserPreference_recengine // recommendation engine method
extends _UserPreference
{
    var $valid_values = array('php','mysuggest','mymovielens','mycluto');
    var $default_value = 'php';

    function sanify ($value) {
        if (!in_array($value,$this->valid_values)) return $this->default_value;
        else return $value;
    }
}

class _UserPreference_recalgo // recommendation engine algorithm
extends _UserPreference
{
    var $valid_values = array
        (
         'itemCos',  // Item-based Top-N recommendation algorithm with cosine-based similarity function
         'itemProb', // Item-based Top-N recommendation algorithm with probability-based similarity function. 
                     // This algorithms tends to outperform the rest.
         'userCos',  // User-based Top-N recommendation algorithm with cosine-based similarity function.
         'bayes');   // Naïve Bayesian Classifier
    var $default_value = 'itemProb';

    function sanify ($value) {
        if (!in_array($value,$this->valid_values)) return $this->default_value;
        else return $value;
    }
}

class _UserPreference_recnnbr // recommendation engine key clustering, neighborhood size
extends _UserPreference_numeric{}

$Theme->customUserPreferences(array(
                                   'recengine' => new _UserPreference_recengine('php'),
                                   'recalgo'   => new _UserPreference_recalgo('itemProb'),
                                   //recnnbr: typically 15-30 for item-based, 40-80 for user-based algos
                                   'recnnbr'   => new _UserPreference_recnnbr(10,14,80),
                                   ));

require_once('lib/PageList.php');

/**
 *  Custom PageList classes
 *  Rationale: Certain themes should be able to extend the predefined list 
 *  of pagelist types. E.g. certain plugins, like MostPopular might use 
 *  info=pagename,hits,rating
 *  which displays the rating column whenever the wikilens theme is active.
 *  Similarly as in certain plugins, like WikiAdminRename or _WikiTranslation
 */
class _PageList_Column_rating extends _PageList_Column {
    function _getValue ($page_handle, &$revision_handle) {
        static $prefix = 0;
        $loader = new WikiPluginLoader();
        $args = "pagename=".$page_handle->_pagename;
        $args .= " small=1";
        $args .= " imgPrefix=".$prefix++;
        return $loader->expandPi('<'."?plugin RateIt $args ?".'>',
                                 $GLOBALS['request'], $page_handle);
    }
};

// register custom PageList type
$Theme->addPageListColumn(array('rating' => 
                                new _PageList_Column_rating('rating', _("Rate"))));


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
