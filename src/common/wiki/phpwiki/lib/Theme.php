<?php
/* Copyright (C) 2002,2004,2005 $ThePhpWikiProgrammingTeam
 *
 * This file is part of PhpWiki.
 *
 * PhpWiki is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * PhpWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PhpWiki; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Customize output by themes: templates, css, special links functions,
 * and more formatting.
 */

require_once(dirname(__FILE__) . '/HtmlElement.php');

/**
 * Make a link to a wiki page (in this wiki).
 *
 * This is a convenience function.
 *
 * @param mixed $page_or_rev
 * Can be:<dl>
 * <dt>A string</dt><dd>The page to link to.</dd>
 * <dt>A WikiDB_Page object</dt><dd>The page to link to.</dd>
 * <dt>A WikiDB_PageRevision object</dt><dd>A specific version of the page to link to.</dd>
 * </dl>
 *
 * @param string $type
 * One of:<dl>
 * <dt>'unknown'</dt><dd>Make link appropriate for a non-existant page.</dd>
 * <dt>'known'</dt><dd>Make link appropriate for an existing page.</dd>
 * <dt>'auto'</dt><dd>Either 'unknown' or 'known' as appropriate.</dd>
 * <dt>'button'</dt><dd>Make a button-style link.</dd>
 * <dt>'if_known'</dt><dd>Only linkify if page exists.</dd>
 * </dl>
 * Unless $type of of the latter form, the link will be of class 'wiki', 'wikiunknown',
 * 'named-wiki', or 'named-wikiunknown', as appropriate.
 *
 * @param mixed $label (string or XmlContent object)
 * Label for the link.  If not given, defaults to the page name.
 *
 * @return XmlContent The link
 */
function WikiLink($page_or_rev, $type = 'known', $label = false)
{
    global $WikiTheme, $request;

    if ($type == 'button') {
        return $WikiTheme->makeLinkButton($page_or_rev, $label);
    }

    $version = false;

    if (isa($page_or_rev, 'WikiDB_PageRevision')) {
        $version = $page_or_rev->getVersion();
        if ($page_or_rev->isCurrent()) {
            $version = false;
        }
        $page = $page_or_rev->getPage();
        $pagename = $page->getName();
        $wikipage = $pagename;
        $exists = true;
    } elseif (isa($page_or_rev, 'WikiDB_Page')) {
        $page = $page_or_rev;
        $pagename = $page->getName();
        $wikipage = $pagename;
    } elseif (isa($page_or_rev, 'WikiPageName')) {
        $wikipage = $page_or_rev;
        $pagename = $wikipage->name;
        if (!$wikipage->isValid('strict')) {
            return $WikiTheme->linkBadWikiWord($wikipage, $label);
        }
    } else {
        $wikipage = new WikiPageName($page_or_rev, $request->getPage());
        $pagename = $wikipage->name;
        if (!$wikipage->isValid('strict')) {
            return $WikiTheme->linkBadWikiWord($wikipage, $label);
        }
    }

    if ($type == 'auto' or $type == 'if_known') {
        if (isset($page)) {
            $exists = $page->exists();
        } else {
            $dbi = $request->_dbi;
            $exists = $dbi->isWikiPage($wikipage->name);
        }
    } elseif ($type == 'unknown') {
        $exists = false;
    } else {
        $exists = true;
    }

    // FIXME: this should be somewhere else, if really needed.
    // WikiLink makes A link, not a string of fancy ones.
    // (I think that the fancy split links are just confusing.)
    // Todo: test external ImageLinks http://some/images/next.gif
    if (
        isa($wikipage, 'WikiPageName') and
        ! $label and
        strchr(substr($wikipage->shortName, 1), SUBPAGE_SEPARATOR)
    ) {
        $parts = explode(SUBPAGE_SEPARATOR, $wikipage->shortName);
        $last_part = array_pop($parts);
        $sep = '';
        $link = HTML::span();
        foreach ($parts as $part) {
            $path[] = $part;
            $parent = join(SUBPAGE_SEPARATOR, $path);
            if ($WikiTheme->_autosplitWikiWords) {
                $part = " " . $part;
            }
            if ($part) {
                $link->pushContent($WikiTheme->linkExistingWikiWord($parent, $sep . $part));
            }
            $sep = $WikiTheme->_autosplitWikiWords
                   ? ' ' . SUBPAGE_SEPARATOR : SUBPAGE_SEPARATOR;
        }
        if ($exists) {
            $link->pushContent($WikiTheme->linkExistingWikiWord(
                $wikipage,
                $sep . $last_part,
                $version
            ));
        } else {
            $link->pushContent($WikiTheme->linkUnknownWikiWord($wikipage, $sep . $last_part));
        }
        return $link;
    }

    if ($exists) {
        return $WikiTheme->linkExistingWikiWord($wikipage, $label, $version);
    } elseif ($type == 'if_known') {
        if (!$label && isa($wikipage, 'WikiPageName')) {
            $label = $wikipage->shortName;
        }
        return HTML($label ? $label : $pagename);
    } else {
        return $WikiTheme->linkUnknownWikiWord($wikipage, $label);
    }
}



/**
 * Make a button.
 *
 * This is a convenience function.
 *
 * @param $action string
 * One of <dl>
 * <dt>[action]</dt><dd>Perform action (e.g. 'edit') on the selected page.</dd>
 * <dt>[ActionPage]</dt><dd>Run the actionpage (e.g. 'BackLinks') on the selected page.</dd>
 * <dt>'submit:'[name]</dt><dd>Make a form submission button with the given name.
 *      ([name] can be blank for a nameless submit button.)</dd>
 * <dt>a hash</dt><dd>Query args for the action. E.g.<pre>
 *      array('action' => 'diff', 'previous' => 'author')
 * </pre></dd>
 * </dl>
 *
 * @param $label string
 * A label for the button.  If ommited, a suitable default (based on the valued of $action)
 * will be picked.
 *
 * @param $page_or_rev mixed
 * Which page (& version) to perform the action on.
 * Can be one of:<dl>
 * <dt>A string</dt><dd>The pagename.</dd>
 * <dt>A WikiDB_Page object</dt><dd>The page.</dd>
 * <dt>A WikiDB_PageRevision object</dt><dd>A specific version of the page.</dd>
 * </dl>
 * ($Page_or_rev is ignored for submit buttons.)
 */
function Button($action, $label = false, $page_or_rev = false)
{
    global $WikiTheme;

    if (!is_array($action) && preg_match('/^submit:(.*)/', $action, $m)) {
        return $WikiTheme->makeSubmitButton($label, $m[1], $page_or_rev);
    } else {
        return $WikiTheme->makeActionButton($action, $label, $page_or_rev);
    }
}


class Theme
{
    public $HTML_DUMP_SUFFIX = '';
    public $DUMP_MODE = false;
    public $dumped_images;
    public $dumped_css;

    public function __construct($theme_name = 'default')
    {
        $this->_name = $theme_name;
        $this->_themes_dir = NormalizeLocalFileName("themes");
        $this->_path  = defined('PHPWIKI_DIR') ? NormalizeLocalFileName("") : "";
        $this->_theme = "themes/$theme_name";

        if ($theme_name != 'default') {
            $this->_default_theme = new Theme();
        }

        // by pixels
        if (
            (is_object($GLOBALS['request']) // guard against unittests
             and $GLOBALS['request']->getPref('doubleClickEdit'))
            or ENABLE_DOUBLECLICKEDIT
        ) {
            $this->initDoubleClickEdit();
        }

        // will be replaced by acDropDown
        if (ENABLE_LIVESEARCH) { // by bitflux.ch
            $this->initLiveSearch();
        }
        $this->_css = array();
    }

    public function file($file)
    {
        return $this->_path . "$this->_theme/$file";
    }

    public function _findFile($file, $missing_okay = false)
    {
        if (file_exists($this->file($file))) {
            return "$this->_theme/$file";
        }

        // FIXME: this is a short-term hack.  Delete this after all files
        // get moved into themes/...
        if (file_exists($this->_path . $file)) {
            return $file;
        }

        if (isset($this->_default_theme)) {
            return $this->_default_theme->_findFile($file, $missing_okay);
        } elseif (!$missing_okay) {
            trigger_error("$this->_theme/$file: not found", E_USER_NOTICE);
        }
        return false;
    }

    public function _findData($file, $missing_okay = false)
    {
        $path = $this->_findFile($file, $missing_okay);
        if (!$path) {
            return false;
        }

        if (defined('DATA_PATH')) {
            return DATA_PATH . "/$path";
        }
        return $path;
    }

    ////////////////////////////////////////////////////////////////
    //
    // Date and Time formatting
    //
    ////////////////////////////////////////////////////////////////

    // Note:  Windows' implemetation of strftime does not include certain
    // format specifiers, such as %e (for date without leading zeros).  In
    // general, see:
    // http://msdn.microsoft.com/library/default.asp?url=/library/en-us/vclib/html/_crt_strftime.2c_.wcsftime.asp
    // As a result, we have to use %d, and strip out leading zeros ourselves.

    public $_dateFormat = "%B %d, %Y";
    public $_timeFormat = "%I:%M %p";

    public $_showModTime = true;

    /**
     * Set format string used for dates.
     *
     * @param $fs string Format string for dates.
     *
     * @param $show_mod_time bool If true (default) then times
     * are included in the messages generated by getLastModifiedMessage(),
     * otherwise, only the date of last modification will be shown.
     */
    public function setDateFormat($fs, $show_mod_time = true)
    {
        $this->_dateFormat = $fs;
        $this->_showModTime = $show_mod_time;
    }

    /**
     * Set format string used for times.
     *
     * @param $fs string Format string for times.
     */
    public function setTimeFormat($fs)
    {
        $this->_timeFormat = $fs;
    }

    /**
     * Format a date.
     *
     * Any time zone offset specified in the users preferences is
     * taken into account by this method.
     *
     * @param $time_t integer Unix-style time.
     *
     * @return string The date.
     */
    public function formatDate($time_t)
    {
        global $request;

        $offset_time = $time_t + 3600 * $request->getPref('timeOffset');
        // strip leading zeros from date elements (ie space followed by zero)
        return preg_replace(
            '/ 0/',
            ' ',
            strftime($this->_dateFormat, $offset_time)
        );
    }

    /**
     * Format a date.
     *
     * Any time zone offset specified in the users preferences is
     * taken into account by this method.
     *
     * @param $time_t integer Unix-style time.
     *
     * @return string The time.
     */
    public function formatTime($time_t)
    {
        //FIXME: make 24-hour mode configurable?
        global $request;
        $offset_time = $time_t + 3600 * $request->getPref('timeOffset');
        return preg_replace(
            '/^0/',
            ' ',
            strtolower(strftime($this->_timeFormat, $offset_time))
        );
    }

    /**
     * Format a date and time.
     *
     * Any time zone offset specified in the users preferences is
     * taken into account by this method.
     *
     * @param $time_t integer Unix-style time.
     *
     * @return string The date and time.
     */
    public function formatDateTime($time_t)
    {
        return $this->formatDate($time_t) . ' ' . $this->formatTime($time_t);
    }

    /**
     * Format a (possibly relative) date.
     *
     * If enabled in the users preferences, this method might
     * return a relative day (e.g. 'Today', 'Yesterday').
     *
     * Any time zone offset specified in the users preferences is
     * taken into account by this method.
     *
     * @param $time_t integer Unix-style time.
     *
     * @return string The day.
     */
    public function getDay($time_t)
    {
        global $request;

        if ($request->getPref('relativeDates') && ($date = $this->_relativeDay($time_t))) {
            return ucfirst($date);
        }
        return $this->formatDate($time_t);
    }

    /**
     * Format the "last modified" message for a page revision.
     *
     * @param $revision object A WikiDB_PageRevision object.
     *
     * @param $show_version bool Should the page version number
     * be included in the message.  (If this argument is omitted,
     * then the version number will be shown only iff the revision
     * is not the current one.
     *
     * @return string The "last modified" message.
     */
    public function getLastModifiedMessage($revision, $show_version = 'auto')
    {
        global $request;
        if (!$revision) {
            return '';
        }

        // dates >= this are considered invalid.
        if (! defined('EPOCH')) {
            define('EPOCH', 0); // seconds since ~ January 1 1970
        }

        $mtime = $revision->get('mtime');
        if ($mtime <= EPOCH) {
            return fmt("Never edited");
        }

        if ($show_version == 'auto') {
            $show_version = !$revision->isCurrent();
        }

        if ($request->getPref('relativeDates') && ($date = $this->_relativeDay($mtime))) {
            if ($this->_showModTime) {
                $date =  sprintf(
                    _("%s at %s"),
                    $date,
                    $this->formatTime($mtime)
                );
            }

            if ($show_version) {
                return fmt("Version %s, saved %s", $revision->getVersion(), $date);
            } else {
                return fmt("Last edited %s", $date);
            }
        }

        if ($this->_showModTime) {
            $date = $this->formatDateTime($mtime);
        } else {
            $date = $this->formatDate($mtime);
        }

        if ($show_version) {
            return fmt("Version %s, saved on %s", $revision->getVersion(), $date);
        } else {
            return fmt("Last edited on %s", $date);
        }
    }

    public function _relativeDay($time_t)
    {
        global $request;

        if (is_numeric($request->getPref('timeOffset'))) {
            $offset = 3600 * $request->getPref('timeOffset');
        } else {
            $offset = 0;
        }

        $now = time() + $offset;
        $today = localtime($now, true);
        $time = localtime($time_t + $offset, true);

        if ($time['tm_yday'] == $today['tm_yday'] && $time['tm_year'] == $today['tm_year']) {
            return _("today");
        }

        // Note that due to daylight savings chages (and leap seconds), $now minus
        // 24 hours is not guaranteed to be yesterday.
        $yesterday = localtime($now - (12 + $today['tm_hour']) * 3600, true);
        if (
            $time['tm_yday'] == $yesterday['tm_yday']
            and $time['tm_year'] == $yesterday['tm_year']
        ) {
            return _("yesterday");
        }

        return false;
    }

    /**
     * Format the "Author" and "Owner" messages for a page revision.
     */
    public function getOwnerMessage($page)
    {
        if (!ENABLE_PAGEPERM or !class_exists("PagePermission")) {
            return '';
        }
        $dbi = $GLOBALS['request']->_dbi;
        $owner = $page->getOwner();
        if ($owner <> ADMIN_USER) {
            //display owner user_name according to the user choice: real name, or Codendi login
            $owner = UserHelper::instance()->getDisplayNameFromUserName($owner);
        }
        if ($owner) {
            /*
            if ( mayAccessPage('change',$page->getName()) )
                return fmt("Owner: %s", $this->makeActionButton(array('action'=>_("chown"),
                                                                      's' => $page->getName()),
                                                                $owner, $page));
            */
            if ($dbi->isWikiPage($owner)) {
                return fmt("Owner: %s", WikiLink($owner));
            } else {
                return fmt("Owner: %s", '"' . $owner . '"');
            }
        }
    }

    public function getAuthorMessage($revision, $only_authenticated = true)
    {
        if (!$revision) {
            return '';
        }
        $dbi = $GLOBALS['request']->_dbi;
        $author = $revision->get('author_id');
        if ($author or $only_authenticated) {
            if (!$author) {
                $author = $revision->get('author');
            }
            if (!$author) {
                return '';
            }
            //display revision author user_name according to the user choice: real name, or Codendi login
            if ($author <> "The PhpWiki programming team") {
                $author = UserHelper::instance()->getDisplayNameFromUserName($author);
            }
            if ($dbi->isWikiPage($author)) {
                return fmt("by %s", WikiLink($author));
            } else {
                return fmt("by %s", '"' . $author . '"');
            }
        }
    }

    ////////////////////////////////////////////////////////////////
    //
    // Hooks for other formatting
    //
    ////////////////////////////////////////////////////////////////

    //FIXME: PHP 4.1 Warnings
    //lib/Theme.php:84: Notice[8]: The call_user_method() function is deprecated,
    //use the call_user_func variety with the array(&$obj, "method") syntax instead

    public function getFormatter($type, $format)
    {
        $method = strtolower("get${type}Formatter");
        if (method_exists($this, $method)) {
            return $this->{$method}($format);
        }
        return false;
    }

    ////////////////////////////////////////////////////////////////
    //
    // Links
    //
    ////////////////////////////////////////////////////////////////

    public $_autosplitWikiWords = false;
    public function setAutosplitWikiWords($autosplit = true)
    {
        $this->_autosplitWikiWords = $autosplit ? true : false;
    }

    public function maybeSplitWikiWord($wikiword)
    {
        if ($this->_autosplitWikiWords) {
            return SplitPagename($wikiword);
        } else {
            return $wikiword;
        }
    }

    public $_anonEditUnknownLinks = true;
    public function setAnonEditUnknownLinks($anonedit = true)
    {
        $this->_anonEditUnknownLinks = $anonedit ? true : false;
    }

    public function linkExistingWikiWord($wikiword, $linktext = '', $version = false)
    {
        global $request;

        if ($version !== false and !$this->HTML_DUMP_SUFFIX) {
            $url = WikiURL($wikiword, array('version' => $version));
        } else {
            $url = WikiURL($wikiword);
        }

        // Extra steps for dumping page to an html file.
        if ($this->HTML_DUMP_SUFFIX) {
            $url = preg_replace('/^\./', '%2e', $url); // dot pages
        }

        $link = HTML::a(array('href' => $url));

        if (isa($wikiword, 'WikiPageName')) {
             $default_text = $wikiword->shortName;
        } else {
            $default_text = $wikiword;
        }

        if (!empty($linktext)) {
            $link->pushContent($linktext);
            $link->setAttr('class', 'named-wiki');
            $link->setAttr('title', $this->maybeSplitWikiWord($default_text));
        } else {
            $link->pushContent($this->maybeSplitWikiWord($default_text));
            $link->setAttr('class', 'wiki');
        }
        if ($request->getArg('frame')) {
            $link->setAttr('target', '_top');
        }
        return $link;
    }

    public function linkUnknownWikiWord($wikiword, $linktext = '')
    {
        global $request;

        // Get rid of anchors on unknown wikiwords
        if (isa($wikiword, 'WikiPageName')) {
            $default_text = $wikiword->shortName;
            $wikiword = $wikiword->name;
        } else {
            $default_text = $wikiword;
        }

        if ($this->DUMP_MODE) { // HTML, PDF or XML
            $link = HTML::u(empty($linktext) ? $wikiword : $linktext);
            $link->addTooltip(sprintf(_("Empty link to: %s"), $wikiword));
            $link->setAttr('class', empty($linktext) ? 'wikiunknown' : 'named-wikiunknown');
            return $link;
        } else {
            // if AnonEditUnknownLinks show "?" only users which are allowed to edit this page
            if (
                ! $this->_anonEditUnknownLinks and
                ( ! $request->_user->isSignedIn()
                  or ! mayAccessPage('edit', $request->getArg('pagename')))
            ) {
                $text = HTML::span(empty($linktext) ? $wikiword : $linktext);
                $text->setAttr('class', empty($linktext) ? 'wikiunknown' : 'named-wikiunknown');
                return $text;
            } else {
                $url = WikiURL($wikiword, array('action' => 'create'));
                $button = $this->makeButton('?', $url);
                $button->addTooltip(sprintf(_("Create: %s"), $wikiword));
            }
        }

        $link = HTML::span();
        if (!empty($linktext)) {
            $link->pushContent(HTML::u($linktext));
            $link->setAttr('class', 'named-wikiunknown');
        } else {
            $link->pushContent(HTML::u($this->maybeSplitWikiWord($default_text)));
            $link->setAttr('class', 'wikiunknown');
        }
        if (!isa($button, "ImageButton")) {
            $button->setAttr('rel', 'nofollow');
        }
        $link->pushContent($button);
        if ($request->getPref('googleLink')) {
            $gbutton = $this->makeButton('G', "http://www.google.com/search?q="
                                         . urlencode($wikiword));
            $gbutton->addTooltip(sprintf(_("Google:%s"), $wikiword));
            $link->pushContent($gbutton);
        }
        if ($request->getArg('frame')) {
            $link->setAttr('target', '_top');
        }

        return $link;
    }

    public function linkBadWikiWord($wikiword, $linktext = '')
    {
        global $ErrorManager;

        if ($linktext) {
            $text = $linktext;
        } elseif (isa($wikiword, 'WikiPageName')) {
            $text = $wikiword->shortName;
        } else {
            $text = $wikiword;
        }

        if (isa($wikiword, 'WikiPageName')) {
            $message = $wikiword->getWarnings();
        } else {
            $message = sprintf(_("'%s': Bad page name"), $wikiword);
        }
        $ErrorManager->warning($message);

        return HTML::span(array('class' => 'badwikiword'), $text);
    }

    ////////////////////////////////////////////////////////////////
    //
    // Images and Icons
    //
    ////////////////////////////////////////////////////////////////
    public $_imageAliases = array();
    public $_imageAlt = array();

    /**
     *
     * (To disable an image, alias the image to <code>false</code>.
     */
    public function addImageAlias($alias, $image_name)
    {
        // fall back to the PhpWiki-supplied image if not found
        if ($this->_findFile("images/$image_name", true)) {
            $this->_imageAliases[$alias] = $image_name;
        }
    }

    public function addImageAlt($alias, $alt_text)
    {
        $this->_imageAlt[$alias] = $alt_text;
    }
    public function getImageAlt($alias)
    {
        return $this->_imageAlt[$alias];
    }

    public function getImageURL($image)
    {
        $aliases = &$this->_imageAliases;

        if (isset($aliases[$image])) {
            $image = $aliases[$image];
            if (!$image) {
                return false;
            }
        }

        // If not extension, default to .png.
        if (!preg_match('/\.\w+$/', $image)) {
            $image .= '.png';
        }

        // FIXME: this should probably be made to fall back
        //        automatically to .gif, .jpg.
        //        Also try .gif before .png if browser doesn't like png.

        $path = $this->_findData("images/$image", 'missing okay');
        if (!$path) { // search explicit images/ or button/ links also
            $path = $this->_findData("$image", 'missing okay');
        }

        if ($this->DUMP_MODE) {
            if (empty($this->dumped_images)) {
                $this->dumped_images = array();
            }
            $path = "images/" . basename($path);
            if (!in_array($path, $this->dumped_images)) {
                $this->dumped_images[] = $path;
            }
        }
        return $path;
    }

    public function setLinkIcon($proto, $image = false)
    {
        if (!$image) {
            $image = $proto;
        }

        $this->_linkIcons[$proto] = $image;
    }

    public function getLinkIconURL($proto)
    {
        $icons = &$this->_linkIcons;
        if (!empty($icons[$proto])) {
            return $this->getImageURL($icons[$proto]);
        } elseif (!empty($icons['*'])) {
            return $this->getImageURL($icons['*']);
        }
        return false;
    }

    public $_linkIcon = 'front'; // or 'after' or 'no'.
    // maybe also 'spanall': there is a scheme currently in effect with front, which
    // spans the icon only to the first, to let the next words wrap on line breaks
    // see stdlib.php:PossiblyGlueIconToText()
    public function getLinkIconAttr()
    {
        return $this->_linkIcon;
    }
    public function setLinkIconAttr($where)
    {
        $this->_linkIcon = $where;
    }

    public function addButtonAlias($text, $alias = false)
    {
        $aliases = &$this->_buttonAliases;

        if (is_array($text)) {
            $aliases = array_merge($aliases, $text);
        } elseif ($alias === false) {
            unset($aliases[$text]);
        } else {
            $aliases[$text] = $alias;
        }
    }

    public function getButtonURL($text)
    {
        $aliases = &$this->_buttonAliases;
        if (isset($aliases[$text])) {
            $text = $aliases[$text];
        }

        $qtext = urlencode($text);
        $url = $this->_findButton("$qtext.png");
        if ($url && strstr($url, '%')) {
            $url = preg_replace_callback(
                '|([^/]+)$|',
                function (array $matches) {
                    return urlencode($matches[1]);
                },
                $url
            );
        }
        if (!$url) {// Jeff complained about png not supported everywhere.
                    // This was not PC until 2005.
            $url = $this->_findButton("$qtext.gif");
            if ($url && strstr($url, '%')) {
                $url = preg_replace_callback(
                    '|([^/]+)$|',
                    function (array $matches) {
                        return urlencode($matches[1]);
                    },
                    $url
                );
            }
        }
        if ($url and $this->DUMP_MODE) {
            if (empty($this->dumped_buttons)) {
                $this->dumped_buttons = array();
            }
            $file = $url;
            if (defined('DATA_PATH')) {
                $file = substr($url, strlen(DATA_PATH) + 1);
            }
            $url = "images/buttons/" . basename($file);
            if (!array_key_exists($text, $this->dumped_buttons)) {
                $this->dumped_buttons[$text] = $file;
            }
        }
        return $url;
    }

    public function _findButton($button_file)
    {
        if (empty($this->_button_path)) {
            $this->_button_path = $this->_getButtonPath();
        }

        foreach ($this->_button_path as $dir) {
            if ($path = $this->_findData("$dir/$button_file", 1)) {
                return $path;
            }
        }
        return false;
    }

    public function _getButtonPath()
    {
        $button_dir = $this->_findFile("buttons");
        $path_dir = $this->_path . $button_dir;
        if (!file_exists($path_dir) || !is_dir($path_dir)) {
            return array();
        }
        $path = array($button_dir);

        $dir = dir($path_dir);
        while (($subdir = $dir->read()) !== false) {
            if ($subdir[0] == '.') {
                continue;
            }
            if ($subdir == 'CVS') {
                continue;
            }
            if (is_dir("$path_dir/$subdir")) {
                $path[] = "$button_dir/$subdir";
            }
        }
        $dir->close();
        // add default buttons
        $path[] = "themes/default/buttons";
        $path_dir = $this->_path . "themes/default/buttons";
        $dir = dir($path_dir);
        while (($subdir = $dir->read()) !== false) {
            if ($subdir[0] == '.') {
                continue;
            }
            if ($subdir == 'CVS') {
                continue;
            }
            if (is_dir("$path_dir/$subdir")) {
                $path[] = "themes/default/buttons/$subdir";
            }
        }
        $dir->close();

        return $path;
    }

    ////////////////////////////////////////////////////////////////
    //
    // Button style
    //
    ////////////////////////////////////////////////////////////////

    public function makeButton($text, $url, $class = false)
    {
        // FIXME: don't always try for image button?

        // Special case: URLs like 'submit:preview' generate form
        // submission buttons.
        if (preg_match('/^submit:(.*)$/', $url, $m)) {
            return $this->makeSubmitButton($text, $m[1], $class);
        }

        $imgurl = $this->getButtonURL($text);
        if ($imgurl) {
            return new ImageButton($text, $url, $class, $imgurl);
        } else {
            return new Button($this->maybeSplitWikiWord($text), $url, $class);
        }
    }

    public function makeSubmitButton($text, $name, $class = false)
    {
        $imgurl = $this->getButtonURL($text);

        if ($imgurl) {
            return new SubmitImageButton($text, $name, $class, $imgurl);
        } else {
            return new SubmitButton($text, $name, $class);
        }
    }

    /**
     * Make button to perform action.
     *
     * This constructs a button which performs an action on the
     * currently selected version of the current page.
     * (Or anotherpage or version, if you want...)
     *
     * @param $action string The action to perform (e.g. 'edit', 'lock').
     * This can also be the name of an "action page" like 'LikePages'.
     * Alternatively you can give a hash of query args to be applied
     * to the page.
     *
     * @param $label string Textual label for the button.  If left empty,
     * a suitable name will be guessed.
     *
     * @param $page_or_rev mixed  The page to link to.  This can be
     * given as a string (the page name), a WikiDB_Page object, or as
     * WikiDB_PageRevision object.  If given as a WikiDB_PageRevision
     * object, the button will link to a specific version of the
     * designated page, otherwise the button links to the most recent
     * version of the page.
     *
     * @return object A Button object.
     */
    public function makeActionButton($action, $label = false, $page_or_rev = false)
    {
        extract($this->_get_name_and_rev($page_or_rev));

        if (is_array($action)) {
            $attr = $action;
            $action = isset($attr['action']) ? $attr['action'] : 'browse';
        } else {
            $attr['action'] = $action;
        }

        $class = is_safe_action($action) ? 'wikiaction' : 'wikiadmin';
        if (!$label) {
            $label = $this->_labelForAction($action);
        }

        if ($version) {
            $attr['version'] = $version;
        }

        if ($action == 'browse') {
            unset($attr['action']);
        }

        return $this->makeButton($label, WikiURL($pagename, $attr), $class);
    }

    /**
     * Make a "button" which links to a wiki-page.
     *
     * These are really just regular WikiLinks, possibly
     * disguised (e.g. behind an image button) by the theme.
     *
     * This method should probably only be used for links
     * which appear in page navigation bars, or similar places.
     *
     * Use linkExistingWikiWord, or LinkWikiWord for normal links.
     *
     * @param $page_or_rev mixed The page to link to.  This can be
     * given as a string (the page name), a WikiDB_Page object, or as
     * WikiDB_PageRevision object.  If given as a WikiDB_PageRevision
     * object, the button will link to a specific version of the
     * designated page, otherwise the button links to the most recent
     * version of the page.
     *
     * @return object A Button object.
     */
    public function makeLinkButton($page_or_rev, $label = false, $action = false)
    {
        extract($this->_get_name_and_rev($page_or_rev));

        $args = $version ? array('version' => $version) : false;
        if ($action) {
            $args['action'] = $action;
        }

        return $this->makeButton(
            $label ? $label : $pagename,
            WikiURL($pagename, $args),
            'wiki'
        );
    }

    public function _get_name_and_rev($page_or_rev)
    {
        $version = false;

        if (empty($page_or_rev)) {
            global $request;
            $pagename = $request->getArg("pagename");
            $version = $request->getArg("version");
        } elseif (is_object($page_or_rev)) {
            if (isa($page_or_rev, 'WikiDB_PageRevision')) {
                $rev = $page_or_rev;
                $page = $rev->getPage();
                if (!$rev->isCurrent()) {
                    $version = $rev->getVersion();
                }
            } else {
                $page = $page_or_rev;
            }
            $pagename = $page->getName();
        } elseif (is_numeric($page_or_rev)) {
            $version = $page_or_rev;
        } else {
            $pagename = (string) $page_or_rev;
        }
        return compact('pagename', 'version');
    }

    public function _labelForAction($action)
    {
        switch ($action) {
            case 'edit':
                return _("Edit");
            case 'diff':
                return _("Diff");
            case 'logout':
                return _("Sign Out");
            case 'login':
                return _("Sign In");
            case 'lock':
                return _("Lock Page");
            case 'unlock':
                return _("Unlock Page");
            case 'remove':
                return _("Remove Page");
            default:
                // I don't think the rest of these actually get used.
                // 'setprefs'
                // 'upload' 'dumpserial' 'loadfile' 'zip'
                // 'save' 'browse'
                return gettext(ucfirst($action));
        }
    }

    //----------------------------------------------------------------
    public $_buttonSeparator = "\n | ";

    public function setButtonSeparator($separator)
    {
        $this->_buttonSeparator = $separator;
    }

    public function getButtonSeparator()
    {
        return $this->_buttonSeparator;
    }


    ////////////////////////////////////////////////////////////////
    //
    // CSS
    //
    // Notes:
    //
    // Based on testing with Galeon 1.2.7 (Mozilla 1.2):
    // Automatic media-based style selection (via <link> tags) only
    // seems to work for the default style, not for alternate styles.
    //
    // Doing
    //
    //  <link rel="stylesheet" type="text/css" href="phpwiki.css" />
    //  <link rel="stylesheet" type="text/css" href="phpwiki-printer.css" media="print" />
    //
    // works to make it so that the printer style sheet get used
    // automatically when printing (or print-previewing) a page
    // (but when only when the default style is selected.)
    //
    // Attempts like:
    //
    //  <link rel="alternate stylesheet" title="Modern"
    //        type="text/css" href="phpwiki-modern.css" />
    //  <link rel="alternate stylesheet" title="Modern"
    //        type="text/css" href="phpwiki-printer.css" media="print" />
    //
    // Result in two "Modern" choices when trying to select alternate style.
    // If one selects the first of those choices, one gets phpwiki-modern
    // both when browsing and printing.  If one selects the second "Modern",
    // one gets no CSS when browsing, and phpwiki-printer when printing.
    //
    // The Real Fix?
    // =============
    //
    // We should probably move to doing the media based style
    // switching in the CSS files themselves using, e.g.:
    //
    //  @import url(print.css) print;
    //
    ////////////////////////////////////////////////////////////////

    public function _CSSlink($title, $css_file, $media, $is_alt = false)
    {
        // Don't set title on default style.  This makes it clear to
        // the user which is the default (i.e. most supported) style.
        if ($is_alt and isBrowserKonqueror()) {
            return HTML();
        }
        $link = HTML::link(array('rel'     => $is_alt ? 'alternate stylesheet' : 'stylesheet',
                                 'type'    => 'text/css',
                                 'charset' => $GLOBALS['charset'],
                                 'href'    => $this->_findData($css_file)));
        if ($is_alt) {
            $link->setAttr('title', $title);
        }

        if ($media) {
            $link->setAttr('media', $media);
        }
        if ($this->DUMP_MODE) {
            if (empty($this->dumped_css)) {
                $this->dumped_css = array();
            }
            if (!in_array($css_file, $this->dumped_css)) {
                $this->dumped_css[] = $css_file;
            }
            $link->setAttr('href', basename($link->getAttr('href')));
        }

        return $link;
    }

    /** Set default CSS source for this theme.
     *
     * To set styles to be used for different media, pass a
     * hash for the second argument, e.g.
     *
     * $theme->setDefaultCSS('default', array('' => 'normal.css',
     *                                        'print' => 'printer.css'));
     *
     * If you call this more than once, the last one called takes
     * precedence as the default style.
     *
     * @param string $title Name of style (currently ignored, unless
     * you call this more than once, in which case, some of the style
     * will become alternate (rather than default) styles, and then their
     * titles will be used.
     *
     * @param mixed $css_files Name of CSS file, or hash containing a mapping
     * between media types and CSS file names.  Use a key of '' (the empty string)
     * to set the default CSS for non-specified media.  (See above for an example.)
     */
    public function setDefaultCSS($title, $css_files)
    {
        if (!is_array($css_files)) {
            $css_files = array('' => $css_files);
        }
        // Add to the front of $this->_css
        unset($this->_css[$title]);
        $this->_css = array_merge(array($title => $css_files), $this->_css);
    }

    /** Set alternate CSS source for this theme.
     *
     * @param string $title Name of style.
     * @param string $css_files Name of CSS file.
     */
    public function addAlternateCSS($title, $css_files)
    {
        if (!is_array($css_files)) {
            $css_files = array('' => $css_files);
        }
        $this->_css[$title] = $css_files;
    }

    /**
     * @return string HTML for CSS.
     */
    public function getCSS()
    {
        $css = array();
        $is_alt = false;
        foreach ($this->_css as $title => $css_files) {
            ksort($css_files); // move $css_files[''] to front.
            foreach ($css_files as $media => $css_file) {
                $css[] = $this->_CSSlink($title, $css_file, $media, $is_alt);
                if ($is_alt) {
                    break;
                }
            }
            $is_alt = true;
        }
        return HTML($css);
    }

    public function findTemplate($name)
    {
        if ($tmp = $this->_findFile("templates/$name.tmpl", 1)) {
            return $this->_path . $tmp;
        } else {
            $f1 = $this->file("templates/$name.tmpl");
            trigger_error("pwd: " . getcwd(), E_USER_ERROR);
            if (isset($this->_default_theme)) {
                $f2 = $this->_default_theme->file("templates/$name.tmpl");
                trigger_error("$f1 nor $f2 found", E_USER_ERROR);
            } else {
                trigger_error("$f1 not found", E_USER_ERROR);
            }
            return false;
        }
    }

    public $_MoreHeaders = array();
    public function addMoreHeaders($element)
    {
        array_push($this->_MoreHeaders, $element);
    }
    public function getMoreHeaders()
    {
        if (empty($this->_MoreHeaders)) {
            return '';
        }
        $out = '';
        //$out = "<!-- More Headers -->\n";
        foreach ($this->_MoreHeaders as $h) {
            if (is_object($h)) {
                $out .= printXML($h);
            } else {
                $out .= "$h\n";
            }
        }
        return $out;
    }

    public $_MoreAttr = array();
    // new arg: named elements to be able to remove them. such as DoubleClickEdit for htmldumps
    public function addMoreAttr($tag, $name, $element)
    {
        // protect from duplicate attr (body jscript: themes, prefs, ...)
        static $_attr_cache = array();
        $hash = md5($tag . "/" . $element);
        if (!empty($_attr_cache[$hash])) {
            return;
        }
        $_attr_cache[$hash] = 1;

        if (empty($this->_MoreAttr) or !is_array($this->_MoreAttr[$tag])) {
            $this->_MoreAttr[$tag] = array($name => $element);
        } else {
            $this->_MoreAttr[$tag][$name] = $element;
        }
    }

    public function getMoreAttr($tag)
    {
        if (empty($this->_MoreAttr[$tag])) {
            return '';
        }
        $out = '';
        foreach ($this->_MoreAttr[$tag] as $name => $element) {
            if (is_object($element)) {
                $out .= printXML($element);
            } else {
                $out .= "$element";
            }
        }
        return $out;
    }

    /**
     * Custom UserPreferences:
     * A list of name => _UserPreference class pairs.
     * Rationale: Certain themes should be able to extend the predefined list
     * of preferences. Display/editing is done in the theme specific userprefs.tmpl
     * but storage/sanification/update/... must be extended to the Get/SetPreferences methods.
     * These values are just ignored if another theme is used.
     */
    public function customUserPreferences($array)
    {
        global $customUserPreferenceColumns; // FIXME: really a global?
        if (empty($customUserPreferenceColumns)) {
            $customUserPreferenceColumns = array();
        }
        //array('wikilens' => new _UserPreference_wikilens());
        foreach ($array as $field => $prefobj) {
            $customUserPreferenceColumns[$field] = $prefobj;
        }
    }

    /** addPageListColumn(array('rating' => new _PageList_Column_rating('rating', _("Rate"))))
     *  Register custom PageList types for special themes, like
     *  'rating' for wikilens
     */
    public function addPageListColumn($array)
    {
        global $customPageListColumns;
        if (empty($customPageListColumns)) {
            $customPageListColumns = array();
        }
        foreach ($array as $column => $obj) {
            $customPageListColumns[$column] = $obj;
        }
    }

    // Works only on action=browse. Patch #970004 by pixels
    // Usage: call $WikiTheme->initDoubleClickEdit() from theme init or
    // define ENABLE_DOUBLECLICKEDIT
    public function initDoubleClickEdit()
    {
        if (!$this->HTML_DUMP_SUFFIX) {
            $this->addMoreAttr('body', 'DoubleClickEdit', HTML::Raw(" ondblclick=\"url = document.URL; url2 = url; if (url.indexOf('?') != -1) url2 = url.slice(0, url.indexOf('?')); if ((url.indexOf('action') == -1) || (url.indexOf('action=browse') != -1)) document.location = url2 + '?action=edit';\""));
        }
    }

    // Immediate title search results via XMLHTML(HttpRequest
    // by Bitflux GmbH, bitflux.ch. You need to install the livesearch.js seperately.
    // Google's or acdropdown is better.
    public function initLiveSearch()
    {
        if (!$this->HTML_DUMP_SUFFIX) {
            $this->addMoreAttr(
                'body',
                'LiveSearch',
                HTML::Raw(" onload=\"liveSearchInit()")
            );
            $this->addMoreHeaders(JavaScript('var liveSearchURI="'
                                             . WikiURL(_("TitleSearch"), false, true) . '";'));
            $this->addMoreHeaders(JavaScript('', array
                                             ('src' => $this->_findData('livesearch.js'))));
        }
    }
}


/**
 * A class representing a clickable "button".
 *
 * In it's simplest (default) form, a "button" is just a link associated
 * with some sort of wiki-action.
 */
class Button extends HtmlElement
{
    /** Constructor
     *
     * @param $text string The text for the button.
     * @param $url string The url (href) for the button.
     * @param $class string The CSS class for the button.
     */
    public function __construct($text, $url, $class = false)
    {
        global $request;
        //php5 workaround
        $this->_init('a', array('href' => $url));
        if ($class) {
            $this->setAttr('class', $class);
        }
        if ($request->getArg('frame')) {
            $this->setAttr('target', '_top');
        }
        // Google honors this
        if (
            in_array(strtolower($text), array('edit','create','diff'))
            and !$request->_user->isAuthenticated()
        ) {
            $this->setAttr('rel', 'nofollow');
        }
        $this->pushContent($GLOBALS['WikiTheme']->maybeSplitWikiWord($text));
    }
}


/**
 * A clickable image button.
 */
class ImageButton extends Button
{
    /** Constructor
     *
     * @param $text string The text for the button.
     * @param $url string The url (href) for the button.
     * @param $class string The CSS class for the button.
     * @param $img_url string URL for button's image.
     * @param $img_attr array Additional attributes for the &lt;img&gt; tag.
     */
    public function __construct($text, $url, $class, $img_url, $img_attr = false)
    {
        parent::__construct('a', array('href' => $url));
        if ($class) {
            $this->setAttr('class', $class);
        }
        // Google honors this
        if (
            in_array(strtolower($text), array('edit','create','diff'))
            and !$GLOBALS['request']->_user->isAuthenticated()
        ) {
            $this->setAttr('rel', 'nofollow');
        }

        if (!is_array($img_attr)) {
            $img_attr = array();
        }
        $img_attr['src'] = $img_url;
        $img_attr['alt'] = $text;
        $img_attr['class'] = 'wiki-button';
        $img_attr['border'] = 0;
        $this->pushContent(HTML::img($img_attr));
    }
}

/**
 * A class representing a form <samp>submit</samp> button.
 */
class SubmitButton extends HtmlElement
{
    /** Constructor
     *
     * @param $text string The text for the button.
     * @param $name string The name of the form field.
     * @param $class string The CSS class for the button.
     */
    public function __construct($text, $name = false, $class = false)
    {
        parent::__construct('input', array('type' => 'submit',
                                          'value' => $text));
        if ($name) {
            $this->setAttr('name', $name);
        }
        if ($class) {
            $this->setAttr('class', $class);
        }
    }
}


/**
 * A class representing an image form <samp>submit</samp> button.
 */
class SubmitImageButton extends SubmitButton
{
    /** Constructor
     *
     * @param $text string The text for the button.
     * @param $name string The name of the form field.
     * @param $class string The CSS class for the button.
     * @param $img_url string URL for button's image.
     * @param $img_attr array Additional attributes for the &lt;img&gt; tag.
     */
    public function __construct($text, $name, $class, $img_url)
    {
        parent::__construct('input', array('type'  => 'image',
                                          'src'   => $img_url,
                                          'value' => $text,
                                          'alt'   => $text));
        if ($name) {
            $this->setAttr('name', $name);
        }
        if ($class) {
            $this->setAttr('class', $class);
        }
    }
}

/**
 * A sidebar box with title and body, narrow fixed-width.
 * To represent abbrevated content of plugins, links or forms,
 * like "Getting Started", "Search", "Sarch Pagename",
 * "Login", "Menu", "Recent Changes", "Last comments", "Last Blogs"
 * "Calendar"
 * ... See http://tikiwiki.org/
 *
 * Usage:
 * sidebar.tmpl:
 *   $menu = SidebarBox("Menu",HTML::dl(HTML::dt(...))); $menu->format();
 *   $menu = PluginSidebarBox("RecentChanges",array('limit'=>10)); $menu->format();
 */
class SidebarBox
{

    public function __construct($title, $body)
    {
        require_once('lib/WikiPlugin.php');
        $this->title = $title;
        $this->body = $body;
    }
    public function format()
    {
        return WikiPlugin::makeBox($this->title, $this->body);
    }
}

/**
 * A sidebar box for plugins.
 * Any plugin may provide a box($args=false, $request=false, $basepage=false)
 * method, with the help of WikiPlugin::makeBox()
 */
class PluginSidebarBox extends SidebarBox
{

    public $_plugin;
    public $_args = false;
    public $_basepage = false;

    public function __construct($name, $args = false, $basepage = false)
    {
        $loader = new WikiPluginLoader();
        $plugin = $loader->getPlugin($name);
        if (!$plugin) {
            return $loader->_error(sprintf(
                _("Plugin %s: undefined"),
                $name
            ));
        }/*
        if (!method_exists($plugin, 'box')) {
            return $loader->_error(sprintf(_("%s: has no box method"),
                                           get_class($plugin)));
        }*/
        $this->_plugin   = $plugin;
        $this->_args     = $args ? $args : array();
        $this->_basepage = $basepage;
    }

    public function format($args = false)
    {
        return $this->_plugin->box(
            $args ? array_merge($this->_args, $args) : $this->_args,
            $GLOBALS['request'],
            $this->_basepage
        );
    }
}

// Various boxes which are no plugins
class RelatedLinksBox extends SidebarBox
{
    public function __construct($title = false, $body = '', $limit = 20)
    {
        global $request;
        $this->title = $title ? $title : _("Related Links");
        $this->body = HTML($body);
        $page = $request->getPage($request->getArg('pagename'));
        $revision = $page->getCurrentRevision();
        $page_content = $revision->getTransformedContent();
        //$cache = &$page->_wikidb->_cache;
        $counter = 0;
        $sp = HTML::Raw('&middot; ');
        foreach ($page_content->getWikiPageLinks() as $link) {
            if (!$request->_dbi->isWikiPage($link)) {
                continue;
            }
            $this->body->pushContent($sp, WikiLink($link), HTML::br());
            $counter++;
            if ($limit and $counter > $limit) {
                continue;
            }
        }
    }
}

class RelatedExternalLinksBox extends SidebarBox
{
    public function __construct($title = false, $body = '', $limit = 20)
    {
        global $request;
        $this->title = $title ? $title : _("External Links");
        $this->body = HTML($body);
        $page = $request->getPage($request->getArg('pagename'));
        $cache = &$page->_wikidb->_cache;
        $counter = 0;
        $sp = HTML::Raw('&middot; ');
        foreach ($cache->getWikiPageLinks() as $link) {
            if ($link) {
                $this->body->pushContent($sp, WikiLink($link), HTML::br());
                $counter++;
                if ($limit and $counter > $limit) {
                    continue;
                }
            }
        }
    }
}

function listAvailableThemes()
{
    $available_themes = array();
    $dir_root = 'themes';
    if (defined('PHPWIKI_DIR')) {
        $dir_root = PHPWIKI_DIR . "/$dir_root";
    }
    $dir = dir($dir_root);
    if ($dir) {
        while ($entry = $dir->read()) {
            if (
                is_dir($dir_root . '/' . $entry)
                && (substr($entry, 0, 1) != '.')
                && $entry != 'CVS'
            ) {
                array_push($available_themes, $entry);
            }
        }
        $dir->close();
    }
    return $available_themes;
}

function listAvailableLanguages()
{
    $available_languages = array('en');
    $dir_root = 'locale';
    if (defined('PHPWIKI_DIR')) {
        $dir_root = PHPWIKI_DIR . "/$dir_root";
    }
    if ($dir = dir($dir_root)) {
        while ($entry = $dir->read()) {
            if (
                is_dir($dir_root . "/" . $entry)
                && (substr($entry, 0, 1) != '.')
                && $entry != 'po'
                && $entry != 'CVS'
            ) {
                array_push($available_languages, $entry);
            }
        }
        $dir->close();
    }
    return $available_languages;
}

// $Log: Theme.php,v $
// Revision 1.132  2005/07/24 09:51:22  rurban
// guard doubleClickEdit for unittests
//
// Revision 1.131  2005/06/10 06:09:06  rurban
// enable getPref('doubleClickEdit')
//
// Revision 1.130  2005/05/06 16:43:35  rurban
// split long lines
//
// Revision 1.129  2005/05/05 08:57:26  rurban
// support action=revert
//
// Revision 1.128  2005/04/23 11:23:49  rurban
// improve semantics in the setAutosplitWikiWords method: switch to true if no args
//
// Revision 1.127  2005/02/11 14:45:44  rurban
// support ENABLE_LIVESEARCH, enable PDO sessions
//
// Revision 1.126  2005/02/04 11:43:18  rurban
// update comments
//
// Revision 1.125  2005/02/03 05:09:56  rurban
// livesearch.js support
//
// Revision 1.124  2005/01/27 16:28:15  rurban
// especially for Google: nofollow on unauthenticated edit,diff,create,pdf
//
// Revision 1.123  2005/01/25 07:03:02  rurban
// change addMoreAttr() to support named attr, to remove DoubleClickEdit for htmldumps
//
// Revision 1.122  2005/01/21 11:51:22  rurban
// changed (c)
//
// Revision 1.121  2005/01/20 10:14:37  rurban
// rel=nofollow on edit/create page links
//
// Revision 1.120  2004/12/20 13:20:23  rurban
// fix problem described in patch #1088131. SidebarBox may be used before lib/WikiPlugin.php
// is loaded.
//
// Revision 1.119  2004/12/14 21:38:12  rurban
// just aesthetics
//
// Revision 1.118  2004/12/13 14:34:46  rurban
// box parent method exists
//
// Revision 1.117  2004/11/30 17:44:54  rurban
// revisison neutral
//
// Revision 1.116  2004/11/21 11:59:16  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.115  2004/11/17 17:24:02  rurban
// more verbose on fatal template not found
//
// Revision 1.114  2004/11/11 18:31:26  rurban
// add simple backtrace on such general failures to get at least an idea where
//
// Revision 1.113  2004/11/09 17:11:04  rurban
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
// Revision 1.112  2004/11/03 16:50:31  rurban
// some new defaults and constants, renamed USE_DOUBLECLICKEDIT to ENABLE_DOUBLECLICKEDIT
//
// Revision 1.111  2004/10/21 20:20:53  rurban
// From patch #970004 "Double clic to edit" by pixels.
//
// Revision 1.110  2004/10/15 11:05:10  rurban
// fix yesterdays premature dumphtml fix for $default_text (thanks John Shen)
//
// Revision 1.109  2004/10/14 21:06:02  rurban
// fix dumphtml with USE_PATH_INFO (again). fix some PageList refs
//
// Revision 1.108  2004/09/26 12:24:02  rurban
// no anchor (PCRE memory), explicit ^ instead
//
// Revision 1.107  2004/06/21 16:22:29  rurban
// add DEFAULT_DUMP_DIR and HTML_DUMP_DIR constants, for easier cmdline dumps,
// fixed dumping buttons locally (images/buttons/),
// support pages arg for dumphtml,
// optional directory arg for dumpserial + dumphtml,
// fix a AllPages warning,
// show dump warnings/errors on DEBUG,
// don't warn just ignore on wikilens pagelist columns, if not loaded.
// RateIt pagelist column is called "rating", not "ratingwidget" (Dan?)
//
// Revision 1.106  2004/06/20 14:42:54  rurban
// various php5 fixes (still broken at blockparser)
//
// Revision 1.105  2004/06/14 11:31:36  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.104  2004/06/11 09:07:30  rurban
// support theme-specific LinkIconAttr: front or after or none
//
// Revision 1.103  2004/06/07 22:44:14  rurban
// added simplified chown, setacl actions
//
// Revision 1.102  2004/06/07 18:59:28  rurban
// added Chown link to Owner in statusbar
//
// Revision 1.101  2004/06/03 12:59:40  rurban
// simplify translation
// NS4 wrap=virtual only
//
// Revision 1.100  2004/06/03 10:18:19  rurban
// fix FileUser locking issues, new config ENABLE_PAGEPERM
//
// Revision 1.99  2004/06/01 15:27:59  rurban
// AdminUser only ADMIN_USER not member of Administrators
// some RateIt improvements by dfrankow
// edit_toolbar buttons
//
// Revision 1.98  2004/05/27 17:49:05  rurban
// renamed DB_Session to DbSession (in CVS also)
// added WikiDB->getParam and WikiDB->getAuthParam method to get rid of globals
// remove leading slash in error message
// added force_unlock parameter to File_Passwd (no return on stale locks)
// fixed adodb session AffectedRows
// added FileFinder helpers to unify local filenames and DATA_PATH names
// editpage.php: new edit toolbar javascript on ENABLE_EDIT_TOOLBAR
//
// Revision 1.97  2004/05/18 16:23:39  rurban
// rename split_pagename to SplitPagename
//
// Revision 1.96  2004/05/13 13:48:34  rurban
// doc update for the new 1.3.10 release
//
// Revision 1.94  2004/05/13 11:52:34  rurban
// search also default buttons
//
// Revision 1.93  2004/05/12 10:49:55  rurban
// require_once fix for those libs which are loaded before FileFinder and
//   its automatic include_path fix, and where require_once doesn't grok
//   dirname(__FILE__) != './lib'
// upgrade fix with PearDB
// navbar.tmpl: remove spaces for IE &nbsp; button alignment
//
// Revision 1.92  2004/05/03 21:57:47  rurban
// locale updates: we previously lost some words because of wrong strings in
//   PhotoAlbum, german rewording.
// fixed $_SESSION registering (lost session vars, esp. prefs)
// fixed ending slash in listAvailableLanguages/Themes
//
// Revision 1.91  2004/05/03 11:40:42  rurban
// put listAvailableLanguages() and listAvailableThemes() from SystemInfo and
// UserPreferences into Themes.php
//
// Revision 1.90  2004/05/02 19:12:14  rurban
// fix sf.net bug #945154 Konqueror alt css
//
// Revision 1.89  2004/04/29 21:25:45  rurban
// default theme navbar consistency: linkButtons instead of action buttons
//   3rd makeLinkButtin arg for action support
//
// Revision 1.88  2004/04/19 18:27:45  rurban
// Prevent from some PHP5 warnings (ref args, no :: object init)
//   php5 runs now through, just one wrong XmlElement object init missing
// Removed unneccesary UpgradeUser lines
// Changed WikiLink to omit version if current (RecentChanges)
//
// Revision 1.87  2004/04/19 09:13:23  rurban
// new pref: googleLink
//
// Revision 1.86  2004/04/18 01:11:51  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//
// Revision 1.85  2004/04/12 13:04:50  rurban
// added auth_create: self-registering Db users
// fixed IMAP auth
// removed rating recommendations
// ziplib reformatting
//
// Revision 1.84  2004/04/10 02:30:49  rurban
// Fixed gettext problem with VIRTUAL_PATH scripts (Windows only probably)
// Fixed "cannot setlocale..." (sf.net problem)
//
// Revision 1.83  2004/04/09 17:49:03  rurban
// Added PhpWiki RssFeed to Sidebar
// sidebar formatting
// some browser dependant fixes (old-browser support)
//
// Revision 1.82  2004/04/06 20:00:10  rurban
// Cleanup of special PageList column types
// Added support of plugin and theme specific Pagelist Types
// Added support for theme specific UserPreferences
// Added session support for ip-based throttling
//   sql table schema change: ALTER TABLE session ADD sess_ip CHAR(15);
// Enhanced postgres schema
// Added DB_Session_dba support
//
// Revision 1.81  2004/04/01 15:57:10  rurban
// simplified Sidebar theme: table, not absolute css positioning
// added the new box methods.
// remaining problems: large left margin, how to override _autosplitWikiWords in Template only
//
// Revision 1.80  2004/03/30 02:14:03  rurban
// fixed yet another Prefs bug
// added generic PearDb_iter
// $request->appendValidators no so strict as before
// added some box plugin methods
// PageList commalist for condensed output
//
// Revision 1.79  2004/03/24 19:39:02  rurban
// php5 workaround code (plus some interim debugging code in XmlElement)
//   php5 doesn't work yet with the current XmlElement class constructors,
//   WikiUserNew does work better than php4.
// rewrote WikiUserNew user upgrading to ease php5 update
// fixed pref handling in WikiUserNew
// added Email Notification
// added simple Email verification
// removed emailVerify userpref subclass: just a email property
// changed pref binary storage layout: numarray => hash of non default values
// print optimize message only if really done.
// forced new cookie policy: delete pref cookies, use only WIKI_ID as plain string.
//   prefs should be stored in db or homepage, besides the current session.
//
// Revision 1.78  2004/03/18 22:32:33  rurban
// work to make it php5 compatible
//
// Revision 1.77  2004/03/08 19:30:01  rurban
// fixed Theme->getButtonURL
// AllUsers uses now WikiGroup (also DB User and DB Pref users)
// PageList fix for empty pagenames
//
// Revision 1.76  2004/03/08 18:17:09  rurban
// added more WikiGroup::getMembersOf methods, esp. for special groups
// fixed $LDAP_SET_OPTIONS
// fixed _AuthInfo group methods
//
// Revision 1.75  2004/03/01 09:34:37  rurban
// fixed button path logic: now fallback to default also
//
// Revision 1.74  2004/02/28 21:14:08  rurban
// generally more PHPDOC docs
//   see http://xarch.tu-graz.ac.at/home/rurban/phpwiki/xref/
// fxied WikiUserNew pref handling: empty theme not stored, save only
//   changed prefs, sql prefs improved, fixed password update,
//   removed REPLACE sql (dangerous)
// moved gettext init after the locale was guessed
// + some minor changes
//
// Revision 1.73  2004/02/26 03:22:05  rurban
// also copy css and images with XHTML Dump
//
// Revision 1.72  2004/02/26 02:25:53  rurban
// fix empty and #-anchored links in XHTML Dumps
//
// Revision 1.71  2004/02/15 21:34:37  rurban
// PageList enhanced and improved.
// fixed new WikiAdmin... plugins
// editpage, Theme with exp. htmlarea framework
//   (htmlarea yet committed, this is really questionable)
// WikiUser... code with better session handling for prefs
// enhanced UserPreferences (again)
// RecentChanges for show_deleted: how should pages be deleted then?
//
// Revision 1.70  2004/01/26 09:17:48  rurban
// * changed stored pref representation as before.
//   the array of objects is 1) bigger and 2)
//   less portable. If we would import packed pref
//   objects and the object definition was changed, PHP would fail.
//   This doesn't happen with an simple array of non-default values.
// * use $prefs->retrieve and $prefs->store methods, where retrieve
//   understands the interim format of array of objects also.
// * simplified $prefs->get() and fixed $prefs->set()
// * added $user->_userid and class '_WikiUser' portability functions
// * fixed $user object ->_level upgrading, mostly using sessions.
//   this fixes yesterdays problems with loosing authorization level.
// * fixed WikiUserNew::checkPass to return the _level
// * fixed WikiUserNew::isSignedIn
// * added explodePageList to class PageList, support sortby arg
// * fixed UserPreferences for WikiUserNew
// * fixed WikiPlugin for empty defaults array
// * UnfoldSubpages: added pagename arg, renamed pages arg,
//   removed sort arg, support sortby arg
//
// Revision 1.69  2003/12/05 01:32:28  carstenklapp
// New feature: Easier to run multiple wiks off of one set of code. Name
// your logo and signature image files "YourWikiNameLogo.png" and
// "YourWikiNameSignature.png" and put them all into
// themes/default/images. YourWikiName should match what is defined as
// WIKI_NAME in index.php. In case the image is not found, the default
// shipped with PhpWiki will be used.
//
// Revision 1.68  2003/03/04 01:53:30  dairiki
// Inconsequential decrufting.
//
// Revision 1.67  2003/02/26 03:40:22  dairiki
// New action=create.  Essentially the same as action=edit, except that if the
// page already exists, it falls back to action=browse.
//
// This is for use in the "question mark" links for unknown wiki words
// to avoid problems and confusion when following links from stale pages.
// (If the "unknown page" has been created in the interim, the user probably
// wants to view the page before editing it.)
//
// Revision 1.66  2003/02/26 00:10:26  dairiki
// More/better/different checks for bad page names.
//
// Revision 1.65  2003/02/24 22:41:57  dairiki
// Fix stupid typo.
//
// Revision 1.64  2003/02/24 22:06:14  dairiki
// Attempts to fix auto-selection of printer CSS when printing.
// See new comments lib/Theme.php for more details.
// Also see SF patch #669563.
//
// Revision 1.63  2003/02/23 03:37:05  dairiki
// Stupid typo/bug fix.
//
// Revision 1.62  2003/02/21 04:14:52  dairiki
// New WikiLink type 'if_known'.  This gives linkified name if page
// exists, otherwise, just plain text.
//
// Revision 1.61  2003/02/18 21:52:05  dairiki
// Fix so that one can still link to wiki pages with # in their names.
// (This was made difficult by the introduction of named tags, since
// '[Page #1]' is now a link to anchor '1' in page 'Page'.
//
// Now the ~ escape for page names should work: [Page ~#1].
//
// Revision 1.60  2003/02/15 01:59:47  dairiki
// Theme::getCSS():  Add Default-Style HTTP(-eqiv) header in attempt
// to fix default stylesheet selection on some browsers.
// For details on the Default-Style header, see:
//  http://home.dairiki.org/docs/html4/present/styles.html#h-14.3.2
//
// Revision 1.59  2003/01/04 22:30:16  carstenklapp
// New: display a "Never edited." message instead of an invalid epoch date.
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
