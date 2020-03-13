<?php
//rcs_id('$Id: stdlib.php,v 1.244 2005/09/11 13:24:33 rurban Exp $');
/*
 Copyright 1999,2000,2001,2002,2004,2005 $ThePhpWikiProgrammingTeam

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

/*
  Standard functions for Wiki functionality
    WikiURL ($pagename, $args, $get_abs_url)
    AbsoluteURL ($url)
    IconForLink ($protocol_or_url)
    PossiblyGlueIconToText($proto_or_url, $text)
    IsSafeURL($url)
    LinkURL ($url, $linktext)
    LinkImage ($url, $alt)

    SplitQueryArgs ($query_args)
    LinkPhpwikiURL ($url, $text, $basepage)
    ConvertOldMarkup ($content, $markup_type = "block")
    MangleXmlIdentifier($str)
    UnMangleXmlIdentifier($str)

    class Stack { push($item), pop(), cnt(), top() }
    class Alert { show() }
    class WikiPageName {getParent(),isValid(),getWarnings() }

    expand_tabs($str, $tab_width = 8)
    SplitPagename ($page)
    NoSuchRevision ($request, $page, $version)
    TimezoneOffset ($time, $no_colon)
    Iso8601DateTime ($time)
    Rfc2822DateTime ($time)
    ParseRfc1123DateTime ($timestr)
    CTime ($time)
    ByteFormatter ($bytes = 0, $longformat = false)
    __printf ($fmt)
    __sprintf ($fmt)
    __vsprintf ($fmt, $args)

    file_mtime ($filename)
    sort_file_mtime ($a, $b)
    class fileSet {fileSet($directory, $filepattern = false),
                   getFiles($exclude=false, $sortby=false, $limit=false) }
    class ListRegexExpand { listMatchCallback($item, $key),
                            expandRegex ($index, &$pages) }

    glob_to_pcre ($glob)
    glob_match ($glob, $against, $case_sensitive = true)
    explodeList ($input, $allnames, $glob_style = true, $case_sensitive = true)
    explodePageList ($input, $perm = false)
    isa ($object, $class)
    can ($object, $method)
    function_usable ($function_name)
    wikihash ($x)
    better_srand ($seed = '')
    count_all ($arg)
    isSubPage ($pagename)
    subPageSlice ($pagename, $pos)

    phpwiki_version ()
    isWikiWord ($word)
    obj2hash ($obj, $exclude = false, $fields = false)
    isUtf8String ($s)
    fixTitleEncoding ($s)
    url_get_contents ($uri)
    GenerateId ($name)
    firstNWordsOfContent ($n, $content)
    extractSection ($section, $content, $page, $quiet = false, $sectionhead = false)
    isExternalReferrer()

  function: LinkInterWikiLink($link, $linktext)
  moved to: lib/interwiki.php
  function: linkExistingWikiWord($wikiword, $linktext, $version)
  moved to: lib/Theme.php
  function: LinkUnknownWikiWord($wikiword, $linktext)
  moved to: lib/Theme.php
  function: UpdateRecentChanges($dbi, $pagename, $isnewpage)
  gone see: lib/plugin/RecentChanges.php
*/
if (defined('_PHPWIKI_STDLIB_LOADED')) {
    return;
} else {
    define('_PHPWIKI_STDLIB_LOADED', true);
}

define('MAX_PAGENAME_LENGTH', 100);

/**
 * Convert string to a valid XML identifier.
 *
 * XML 1.0 identifiers are of the form: [A-Za-z][A-Za-z0-9:_.-]*
 *
 * We would like to have, e.g. named anchors within wiki pages
 * names like "Table of Contents" --- clearly not a valid XML
 * fragment identifier.
 *
 * This function implements a one-to-one map from {any string}
 * to {valid XML identifiers}.
 *
 * It does this by
 * converting all bytes not in [A-Za-z0-9:_-],
 * and any leading byte not in [A-Za-z] to 'xbb.',
 * where 'bb' is the hexadecimal representation of the
 * character.
 *
 * As a special case, the empty string is converted to 'empty.'
 *
 * @param string $str
 * @return string
 */
function MangleXmlIdentifier($str)
{
    if (!$str) {
        return 'empty.';
    }

    return preg_replace_callback(
        '/[^-_:A-Za-z0-9]|(?<=^)[^A-Za-z]/',
        function (array $matches) {
            return 'x' . sprintf('%02x', ord($matches[0])) . '.';
        },
        $str
    );
}

/**
 * Generates a valid URL for a given Wiki pagename.
 * @param mixed $pagename If a string this will be the name of the Wiki page to link to.
 *               If a WikiDB_Page object function will extract the name to link to.
 *               If a WikiDB_PageRevision object function will extract the name to link to.
 * @param array $args
 * @param bool $get_abs_url Default value is false.
 * @return string The absolute URL to the page passed as $pagename.
 */
function WikiURL($pagename, $args = '', $get_abs_url = false)
{
    $anchor = false;

    if (is_object($pagename)) {
        if (isa($pagename, 'WikiDB_Page')) {
            $pagename = $pagename->getName();
        } elseif (isa($pagename, 'WikiDB_PageRevision')) {
            $page = $pagename->getPage();
            $args['version'] = $pagename->getVersion();
            $pagename = $page->getName();
        } elseif (isa($pagename, 'WikiPageName')) {
            $anchor = $pagename->anchor;
            $pagename = $pagename->name;
        } else { // php5
            $anchor = $pagename->anchor;
            $pagename = $pagename->name;
        }
    }
    if (!$get_abs_url and DEBUG and $GLOBALS['request']->getArg('start_debug')) {
        if (!$args) {
            $args = 'start_debug=' . $GLOBALS['request']->getArg('start_debug');
        } elseif (is_array($args)) {
            $args['start_debug'] = $GLOBALS['request']->getArg('start_debug');
        } else {
            $args .= '&start_debug=' . $GLOBALS['request']->getArg('start_debug');
        }
    }
    if (is_array($args)) {
        $enc_args = array();
        foreach ($args as $key => $val) {
            // avoid default args
            if (USE_PATH_INFO and $key == 'pagename') {
            } elseif ($key == 'action' and $val == 'browse') {
            } elseif (!is_array($val)) { // ugly hack for getURLtoSelf() which also takes POST vars
                $enc_args[] = urlencode($key) . '=' . urlencode($val);
            }
        }
        $args = join('&', $enc_args);
    }

    if (USE_PATH_INFO or !empty($GLOBALS['WikiTheme']->HTML_DUMP_SUFFIX)) {
        $url = $get_abs_url ? (SERVER_URL . VIRTUAL_PATH . "/") : "";
        $url = $url . preg_replace('/%2f/i', '/', rawurlencode($pagename));
        if (!empty($GLOBALS['WikiTheme']->HTML_DUMP_SUFFIX)) {
            $url .= $GLOBALS['WikiTheme']->HTML_DUMP_SUFFIX;
        }
        if ($args) {
            $url .= "?$args";
        }
    } else {
        $url = $get_abs_url ? SERVER_URL . SCRIPT_NAME : basename(SCRIPT_NAME);
        $url .= "?pagename=" . rawurlencode($pagename);
        if ($args) {
            $url .= "&$args";
        }
    }
    global $group_id;
    $url .= '&group_id=' . $group_id;
    global $pv;
    if ($pv) {
        $url .= '&pv=' . $pv;
    }
    if ($anchor) {
        $url .= "#" . MangleXmlIdentifier($anchor);
    }
    return $url;
}

/** Convert relative URL to absolute URL.
 *
 * This converts a relative URL to one of PhpWiki's support files
 * to an absolute one.
 *
 * @param string $url
 * @return string Absolute URL
 */
function AbsoluteURL($url)
{
    if (preg_match('/^https?:/', $url)) {
        return $url;
    }
    if ($url[0] != '/') {
        $base = USE_PATH_INFO ? VIRTUAL_PATH : dirname(SCRIPT_NAME);
        while ($base != '/' and substr($url, 0, 3) == "../") {
            $url = substr($url, 3);
            $base = dirname($base);
        }
        if ($base != '/') {
            $base .= '/';
        }
        $url = $base . $url;
    }
    return SERVER_URL . $url;
}

function DataURL($url)
{
    if (preg_match('/^https?:/', $url)) {
        return $url;
    }
    $url = NormalizeWebFileName($url);
    if (DEBUG and $GLOBALS['request']->getArg('start_debug') and substr($url, -4, 4) == '.php') {
        $url .= "?start_debug=1"; // XMLRPC and SOAP debugging helper.
    }
    return AbsoluteURL($url);
}

/**
 * Generates icon in front of links.
 *
 * @param string $protocol_or_url URL or protocol to determine which icon to use.
 *
 * @return HtmlElement HtmlElement object that contains data to create img link to
 * icon for use with url or protocol passed to the function. False if no img to be
 * displayed.
 */
function IconForLink($protocol_or_url)
{
    global $WikiTheme;
    if (0 and $filename_suffix == false) {
        // display apache style icon for file type instead of protocol icon
        // - archive: unix:gz,bz2,tgz,tar,z; mac:dmg,dmgz,bin,img,cpt,sit; pc:zip;
        // - document: html, htm, text, txt, rtf, pdf, doc
        // - non-inlined image: jpg,jpeg,png,gif,tiff,tif,swf,pict,psd,eps,ps
        // - audio: mp3,mp2,aiff,aif,au
        // - multimedia: mpeg,mpg,mov,qt
    } else {
        list ($proto) = explode(':', $protocol_or_url, 2);
        $src = $WikiTheme->getLinkIconURL($proto);
        if ($src) {
            return HTML::img(array('src' => $src, 'alt' => "", 'class' => 'linkicon', 'border' => 0));
        } else {
            return false;
        }
    }
}

/**
 * Glue icon in front of or after text.
 * Pref: 'noLinkIcons'  - ignore icon if set
 * Theme: 'LinkIcons'   - 'yes'   at front
 *                      - 'no'    display no icon
 *                      - 'front' display at left
 *                      - 'after' display at right
 *
 * @param string $protocol_or_url Protocol or URL.  Used to determine the
 * proper icon.
 * @param string $text The text.
 * @return XmlContent.
 */
function PossiblyGlueIconToText($proto_or_url, $text)
{
    global $request, $WikiTheme;
    if ($request->getPref('noLinkIcons')) {
        return $text;
    }
    $icon = IconForLink($proto_or_url);
    if (!$icon) {
        return $text;
    }
    if ($where = $WikiTheme->getLinkIconAttr()) {
        if ($where == 'no') {
            return $text;
        }
        if ($where != 'after') {
            $where = 'front';
        }
    } else {
        $where = 'front';
    }
    if ($where == 'after') {
        // span the icon only to the last word (tie them together),
        // to let the previous words wrap on line breaks.
        if (!is_object($text)) {
            preg_match('/^(\s*\S*)(\s*)$/', $text, $m);
            list (, $prefix, $last_word) = $m;
        } else {
            $last_word = $text;
            $prefix = false;
        }
        $text = HTML::span(
            array('style' => 'white-space: nowrap'),
            $last_word,
            HTML::Raw('&nbsp;'),
            $icon
        );
        if ($prefix) {
            $text = HTML($prefix, $text);
        }
        return $text;
    }
    // span the icon only to the first word (tie them together),
    // to let the next words wrap on line breaks
    if (!is_object($text)) {
        preg_match('/^\s*(\S*)(.*?)\s*$/', $text, $m);
        list (, $first_word, $tail) = $m;
    } else {
        $first_word = $text;
        $tail = false;
    }
    $text = HTML::span(
        array('style' => 'white-space: nowrap'),
        $icon,
        $first_word
    );
    if ($tail) {
        $text = HTML($text, $tail);
    }
    return $text;
}

/**
 * Determines if the url passed to function is safe, by detecting if the characters
 * '<', '>', or '"' are present.
 * Check against their urlencoded values also.
 *
 * @param string $url URL to check for unsafe characters.
 * @return bool True if same, false else.
 */
function IsSafeURL($url)
{
    $valid_local_uri  = new Valid_LocalURI();
    $valid_ftp_uri    = new Valid_FTPURI();
    $valid_mailto_uri = new Valid_MailtoURI();

    return !preg_match('/([<>"])|(%3C)|(%3E)|(%22)/', $url) && (
            $valid_local_uri->validate($url) || $valid_ftp_uri->validate($url) || $valid_mailto_uri->validate($url)
        );
}

/**
 * Generates an HtmlElement object to store data for a link.
 *
 * @param string $url URL that the link will point to.
 * @param string $linktext Text to be displayed as link.
 * @return HtmlElement HtmlElement object that contains data to construct an html link.
 */
function LinkURL($url, $linktext = '')
{
    // FIXME: Is this needed (or sufficient?)
    if (! IsSafeURL($url)) {
        $link = HTML::strong(HTML::u(
            array('class' => 'baduri'),
            _("BAD URL -- remove all of <, >, \"")
        ));
    } else {
        if (!$linktext) {
            $linktext = preg_replace("/mailto:/A", "", $url);
        }
        $args = array('href' => $url, 'rel' => 'noreferrer');
        if (defined('EXTERNAL_LINK_TARGET')) { // can also be set in the css
            $args['target'] = is_string(EXTERNAL_LINK_TARGET) ? EXTERNAL_LINK_TARGET : "_blank";
        }
        $link = HTML::a($args, PossiblyGlueIconToText($url, $linktext));
    }
    $link->setAttr('class', $linktext ? 'namedurl' : 'rawurl');
    return $link;
}

/**
 * Inline Images
 *
 * Syntax: [image.png size=50% border=n align= hspace= vspace= width= height=]
 * Disallows sizes which are too small.
 * Spammers may use such (typically invisible) image attributes to higher their GoogleRank.
 *
 * Handle embeddable objects, like svg, class, vrml, swf, svgz, pdf, avi, wmv especially.
 */
function LinkImage($url, $alt = false)
{
    $force_img = "png|jpg|gif|jpeg|bmp|pl|cgi";
    // Disallow tags in img src urls. Typical CSS attacks.
    // FIXME: Is this needed (or sufficient?)
    if (! IsSafeURL($url)) {
        $link = HTML::strong(HTML::u(
            array('class' => 'baduri'),
            _("BAD URL -- remove all of <, >, \"")
        ));
    } else {
        // support new syntax: [image.jpg size=50% border=n]
        if (!preg_match("/\.(" . $force_img . ")/i", $url)) {
            $ori_url = $url;
        }
        $arr = preg_split('/ /D', $url);
        if (count($arr) > 1) {
            $url = $arr[0];
        }
        if (empty($alt)) {
            $alt = basename($url);
        }
        $link = HTML::img(array('src' => $url, 'alt' => $alt, 'title' => $alt));
        if (count($arr) > 1) {
            array_shift($arr);
            foreach ($arr as $attr) {
                if (preg_match('/^size=(\d+%)$/', $attr, $m)) {
                    $link->setAttr('width', $m[1]);
                    $link->setAttr('height', $m[1]);
                }
                if (preg_match('/^size=(\d+)x(\d+)$/', $attr, $m)) {
                    $link->setAttr('width', $m[1]);
                    $link->setAttr('height', $m[2]);
                }
                if (preg_match('/^border=(\d+)$/', $attr, $m)) {
                    $link->setAttr('border', $m[1]);
                }
                if (preg_match('/^align=(\w+)$/', $attr, $m)) {
                    $link->setAttr('align', $m[1]);
                }
                if (preg_match('/^hspace=(\d+)$/', $attr, $m)) {
                    $link->setAttr('hspace', $m[1]);
                }
                if (preg_match('/^vspace=(\d+)$/', $attr, $m)) {
                    $link->setAttr('vspace', $m[1]);
                }
            }
        }
        // Check width and height as spam countermeasure
        if (($width  = $link->getAttr('width')) and ($height = $link->getAttr('height'))) {
            //$width  = (int) $width; // px or % or other suffix
            //$height = (int) $height;
            if (($width < 3 and $height < 10) or
                ($height < 3 and $width < 20) or
                ($height < 7 and $width < 7)) {
                trigger_error(_("Invalid image size"), E_USER_WARNING);
                return '';
            }
        } else {
            if (!DISABLE_GETIMAGESIZE and ($size = @getimagesize($url))) {
                $width  = $size[0];
                $height = $size[1];
                if (($width < 3 and $height < 10)
                    or ($height < 3 and $width < 20)
                    or ($height < 7 and $width < 7)) {
                    trigger_error(_("Invalid image size"), E_USER_WARNING);
                    return '';
                }
            }
        }
    }
    $link->setAttr('class', 'inlineimage');

    /* Check for inlined objects. Everything allowed in INLINE_IMAGES besides
     * png|jpg|gif|jpeg|bmp|pl|cgi
     * Note: Allow cgi's (pl,cgi) returning images.
     */
    if (!preg_match("/\.(" . $force_img . ")/i", $url)) {
        //HTML::img(array('src' => $url, 'alt' => $alt, 'title' => $alt));
        // => HTML::object(array('src' => $url)) ...;
        return ImgObject($link, $ori_url);
    }
    return $link;
}

/**
 * <object> / <embed> tags instead of <img> for all non-image extensions allowed via INLINE_IMAGES
 * Called by LinkImage(), not directly.
 * Syntax: [image.svg size=50% border=n align= hspace= vspace= width= height=]
 * $alt may be an alternate img
 * TODO: Need to unify with WikiPluginCached::embedObject()
 *
 * Note that Safari 1.0 will crash with <object>, so use only <embed>
 *   http://www.alleged.org.uk/pdc/2002/svg-object.html
 */
function ImgObject($img, $url)
{
    // get the url args: data="sample.svgz" type="image/svg+xml" width="400" height="300"
    $args = preg_split('/ /D', $url);
    if (count($args) >= 1) {
        $url = array_shift($args);
        foreach ($args as $attr) {
            if (preg_match('/^type=(\S+)$/', $attr, $m)) {
                $img->setAttr('type', $m[1]);
            }
            if (preg_match('/^data=(\S+)$/', $attr, $m)) {
                $img->setAttr('data', $m[1]);
            }
        }
    }
    $type = $img->getAttr('type');
    if (!$type) {
        // TODO: map extension to mime-types if type is not given and php < 4.3
        if (function_exists('mime_content_type')) {
            $type = mime_content_type($url);
        }
    }
    $link = HTML::object(array_merge($img->_attr, array('src' => $url, 'type' => $type)));
    $link->setAttr('class', 'inlineobject');
    if (isBrowserSafari()) {
        return HTML::embed($link->_attr);
    }
    $link->pushContent(HTML::embed($link->_attr));
    return $link;
}


class Stack
{

    // var in php5 deprecated
    public function __construct()
    {
        $this->items = array();
        $this->size = 0;
    }
    public function push($item)
    {
        $this->items[$this->size] = $item;
        $this->size++;
        return true;
    }

    public function pop()
    {
        if ($this->size == 0) {
            return false; // stack is empty
        }
        $this->size--;
        return $this->items[$this->size];
    }

    public function cnt()
    {
        return $this->size;
    }

    public function top()
    {
        if ($this->size) {
            return $this->items[$this->size - 1];
        } else {
            return '';
        }
    }
}
// end class definition

function SplitQueryArgs($query_args = '')
{
    // FIXME: use the arg-seperator which might not be &
    $split_args = preg_split('/&/D', $query_args);
    $args = array();
    foreach ($split_args as $val) {
        if (preg_match('/^ ([^=]+) =? (.*) /x', $val, $m)) {
            $args[$m[1]] = $m[2];
        }
    }
    return $args;
}

function LinkPhpwikiURL($url, $text = '', $basepage = false)
{
    $args = array();

    if (!preg_match('/^ phpwiki: ([^?]*) [?]? (.*) $/x', $url, $m)) {
        return HTML::strong(
            array('class' => 'rawurl'),
            HTML::u(
                array('class' => 'baduri'),
                _("BAD phpwiki: URL")
            )
        );
    }

    if ($m[1]) {
        $pagename = urldecode($m[1]);
    }
    $qargs = $m[2];

    if (empty($pagename) &&
        preg_match('/^(diff|edit|links|info)=([^&]+)$/', $qargs, $m)) {
        // Convert old style links (to not break diff links in
        // RecentChanges).
        $pagename = urldecode($m[2]);
        $args = array("action" => $m[1]);
    } else {
        $args = SplitQueryArgs($qargs);
    }

    if (empty($pagename)) {
        $pagename = $GLOBALS['request']->getArg('pagename');
    }

    if (isset($args['action']) && $args['action'] == 'browse') {
        unset($args['action']);
    }

    /*FIXME:
      if (empty($args['action']))
      $class = 'wikilink';
      else if (is_safe_action($args['action']))
      $class = 'wikiaction';
    */
    if (empty($args['action']) || is_safe_action($args['action'])) {
        $class = 'wikiaction';
    } else {
        // Don't allow administrative links on unlocked pages.
        $dbi = $GLOBALS['request']->getDbh();
        $page = $dbi->getPage($basepage ? $basepage : $pagename);
        if (!$page->get('locked')) {
            return HTML::span(
                array('class' => 'wikiunsafe'),
                HTML::u(_("Lock page to enable link"))
            );
        }
        $class = 'wikiadmin';
    }

    if (!$text) {
        $text = HTML::span(array('class' => 'rawurl'), $url);
    }

    $wikipage = new WikiPageName($pagename);
    if (!$wikipage->isValid()) {
        global $WikiTheme;
        return $WikiTheme->linkBadWikiWord($wikipage, $url);
    }

    return HTML::a(
        array('href'  => WikiURL($pagename, $args),
                         'class' => $class),
        $text
    );
}

/**
 * A class to assist in parsing wiki pagenames.
 *
 * Now with subpages and anchors, parsing and passing around
 * pagenames is more complicated.  This should help.
 */
class WikiPageName
{
    /** Short name for page.
     *
     * This is the value of $name passed to the constructor.
     * (For use, e.g. as a default label for links to the page.)
     */
    //var $shortName;

    /** The full page name.
     *
     * This is the full name of the page (without anchor).
     */
    //var $name;

    /** The anchor.
     *
     * This is the referenced anchor within the page, or the empty string.
     */
    //var $anchor;

    /** Constructor
     *
     * @param mixed $name Page name.
     * WikiDB_Page, WikiDB_PageRevision, or string.
     * This can be a relative subpage name (like '/SubPage'),
     * or can be the empty string to refer to the $basename.
     *
     * @param string $anchor For links to anchors in page.
     *
     * @param mixed $basename Page name from which to interpret
     * relative or other non-fully-specified page names.
     */
    public function __construct($name, $basename = false, $anchor = false)
    {
        if (is_string($name)) {
            $this->shortName = $name;
            if (strstr($name, ':')) {
                list($moniker, $this->shortName) = preg_split("/:/D", $name, 2);
                $map = getInterwikiMap(); // allow overrides to custom maps
                if (isset($map->_map[$moniker])) {
                    $url = $map->_map[$moniker];
                    if (strstr($url, '%s')) {
                        $url = sprintf($url, $this->shortName);
                    } else {
                        $url .= $this->shortName;
                    }
                    // expand Talk or User, but not to absolute urls!
                    if (strstr($url, '//')) {
                        if ($moniker == 'Talk') {
                            $name = $name . SUBPAGE_SEPARATOR . _("Discussion");
                        } elseif ($moniker == 'User') {
                            $name = $name;
                        }
                    } else {
                        $name = $url;
                    }
                    if (strstr($name, '?')) {
                        list($name, $dummy) = explode('?', $name, 2);
                    }
                }
            }
        // FIXME: We should really fix the cause for "/PageName" in the WikiDB
            if ($name == '' or $name[0] == SUBPAGE_SEPARATOR) {
                if ($basename) {
                    $name = $this->_pagename($basename) . $name;
                } else {
                    $name = $this->_normalize_bad_pagename($name);
                    $this->shortName = $name;
                }
            }
        } else {
            $name = $this->_pagename($name);
            $this->shortName = $name;
        }

        $this->name = $this->_check($name);
        $this->anchor = (string) $anchor;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParent()
    {
        $name = $this->name;
        if (!($tail = strrchr($name, SUBPAGE_SEPARATOR))) {
            return false;
        }
        return substr($name, 0, -strlen($tail));
    }

    public function isValid($strict = false)
    {
        if ($strict) {
            return !isset($this->_errors);
        }
        return (is_string($this->name) and $this->name != '');
    }

    public function getWarnings()
    {
        $warnings = array();
        if (isset($this->_warnings)) {
            $warnings = array_merge($warnings, $this->_warnings);
        }
        if (isset($this->_errors)) {
            $warnings = array_merge($warnings, $this->_errors);
        }
        if (!$warnings) {
            return false;
        }

        return sprintf(
            _("'%s': Bad page name: %s"),
            $this->shortName,
            join(', ', $warnings)
        );
    }

    public function _pagename($page)
    {
        if (isa($page, 'WikiDB_Page')) {
            return $page->getName();
        } elseif (isa($page, 'WikiDB_PageRevision')) {
            return $page->getPageName();
        } elseif (isa($page, 'WikiPageName')) {
            return $page->name;
        }
        // '0' or e.g. '1984' should be allowed though
        if (!is_string($page) and !is_integer($page)) {
            trigger_error(
                sprintf(
                    "Non-string pagename '%s' (%s)(%s)",
                    $page,
                    gettype($page),
                    get_class($page)
                ),
                E_USER_NOTICE
            );
        }
    //assert(is_string($page));
        return $page;
    }

    public function _normalize_bad_pagename($name)
    {
        trigger_error("Bad pagename: " . $name, E_USER_WARNING);

        // Punt...  You really shouldn't get here.
        if (empty($name)) {
            global $request;
            return $request->getArg('pagename');
        }
        assert($name[0] == SUBPAGE_SEPARATOR);
        $this->_errors[] = sprintf(_("Leading %s not allowed"), SUBPAGE_SEPARATOR);
        return substr($name, 1);
    }


    public function _check($pagename)
    {
        // Compress internal white-space to single space character.
         //WARNING : MODIFICATIONS FOR CODENDI
        //change for utf-8 encoding
        $pagename = preg_replace('/[\s\xc2\xa0]+/u', ' ', $orig = $pagename);
        //$pagename = preg_replace('/[\s\xa0]+/', ' ', $orig = $pagename);

        if ($pagename != $orig) {
            $this->_warnings[] = _("White space converted to single space");
        }

        // Delete any control characters.
        if (DATABASE_TYPE == 'cvs' or DATABASE_TYPE == 'file') {
            $pagename = preg_replace('/[\x00-\x1f\x7f\x80-\x9f]/', '', $orig = $pagename);
            if ($pagename != $orig) {
                $this->_errors[] = _("Control characters not allowed");
            }
        }

        // Strip leading and trailing white-space.
        $pagename = trim($pagename);

        $orig = $pagename;
        while ($pagename and $pagename[0] == SUBPAGE_SEPARATOR) {
            $pagename = substr($pagename, 1);
        }
        if ($pagename != $orig) {
            $this->_errors[] = sprintf(_("Leading %s not allowed"), SUBPAGE_SEPARATOR);
        }

        // ";" is urlencoded, so safe from php arg-delim problems
        /*if (strstr($pagename, ';')) {
            $this->_warnings[] = _("';' is deprecated");
            $pagename = str_replace(';', '', $pagename);
        }*/

        // not only for SQL, also to restrict url length
        if (strlen($pagename) > MAX_PAGENAME_LENGTH) {
            $pagename = substr($pagename, 0, MAX_PAGENAME_LENGTH);
            $this->_errors[] = _("too long");
        }

        // disallow some chars only on file and cvs
        if ((DATABASE_TYPE == 'cvs' or DATABASE_TYPE == 'file')
            and preg_match('/(:|\.\.)/', $pagename, $m)) {
            $this->_warnings[] = sprintf(_("Illegal chars %s removed"), $m[1]);
            $pagename = str_replace('..', '', $pagename);
            $pagename = str_replace(':', '', $pagename);
        }

        return $pagename;
    }
}

/**
 * Convert old page markup to new-style markup.
 *
 * @param string $text Old-style wiki markup.
 *
 * @param string $markup_type
 * One of: <dl>
 * <dt><code>"block"</code>  <dd>Convert all markup.
 * <dt><code>"inline"</code> <dd>Convert only inline markup.
 * <dt><code>"links"</code>  <dd>Convert only link markup.
 * </dl>
 *
 * @return string New-style wiki markup.
 *
 * @bugs Footnotes don't work quite as before (esp if there are
 *   multiple references to the same footnote.  But close enough,
 *   probably for now....
 * @bugs  Apache2 and IIS crash with OldTextFormattingRules or
 *   AnciennesR%E8glesDeFormatage. (at the 2nd attempt to do the anchored block regex)
 *   It only crashes with CreateToc so far, but other pages (not in pgsrc) are
 *   also known to crash, even with Apache1.
 */
function ConvertOldMarkup($text, $markup_type = "block")
{
    static $subs;
    static $block_re;

    // FIXME:
    // Trying to detect why the 2nd paragraph of OldTextFormattingRules or
    // AnciennesR%E8glesDeFormatage crashes.
    // It only crashes with CreateToc so far, but other pages (not in pgsrc) are
    // also known to crash, even with Apache1.
    $debug_skip = false;
    // I suspect this only to crash with Apache2 and IIS.
    if (in_array(php_sapi_name(), array('apache2handler','apache2filter','isapi'))
        and preg_match("/plugin CreateToc/", $text)) {
        trigger_error(_("The CreateTocPlugin is not yet old markup compatible! ")
                     . _("Please remove the CreateToc line to be able to reformat this page to old markup. ")
                     . _("Skipped."), E_USER_WARNING);
        $debug_skip = true;
        //if (!DEBUG) return $text;
        return $text;
    }

    if (empty($subs)) {
        /*****************************************************************
         * Conversions for inline markup:
         */

        // escape tilde's
        $orig[] = '/~/';
        $repl[] = '~~';

        // escape escaped brackets
        $orig[] = '/\[\[/';
        $repl[] = '~[';

        // change ! escapes to ~'s.
        global $WikiNameRegexp, $request;
        $bang_esc[] = "(?:" . ALLOWED_PROTOCOLS . "):[^\s<>\[\]\"'()]*[^\s<>\[\]\"'(),.?]";
        // before 4.3.9 pcre had a memory release bug, which might hit us here. so be safe.
        $map = getInterwikiMap();
        if ($map_regex = $map->getRegexp()) {
            $bang_esc[] = $map_regex . ":[^\\s.,;?()]+"; // FIXME: is this really needed?
        }
        $bang_esc[] = $WikiNameRegexp;
        $orig[] = '/!((?:' . join(')|(', $bang_esc) . '))/';
        $repl[] = '~\\1';

        $subs["links"] = array($orig, $repl);

        // Temporarily URL-encode pairs of underscores in links to hide
        // them from the re for bold markup.
        $orig[] = '/\[[^\[\]]*?__[^\[\]]*?\]/e';
        $repl[] = 'str_replace(\'__\', \'%5F%5F\', \'\\0\')';

        // Escape '<'s
        //$orig[] = '/<(?!\?plugin)|(?<!^)</m';
        //$repl[] = '~<';

        // Convert footnote references.
        $orig[] = '/(?<=.)(?<!~)\[\s*(\d+)\s*\]/m';
        $repl[] = '#[|ftnt_ref_\\1]<sup>~[[\\1|#ftnt_\\1]~]</sup>';

        // Convert old style emphases to HTML style emphasis.
        $orig[] = '/__(.*?)__/';
        $repl[] = '<strong>\\1</strong>';
        $orig[] = "/''(.*?)''/";
        $repl[] = '<em>\\1</em>';

        // Escape nestled markup.
        $orig[] = '/^(?<=^|\s)[=_](?=\S)|(?<=\S)[=_*](?=\s|$)/m';
        $repl[] = '~\\0';

        // in old markup headings only allowed at beginning of line
        $orig[] = '/!/';
        $repl[] = '~!';

        // Convert URL-encoded pairs of underscores in links back to
        // real underscores after bold markup has been converted.
        $orig = '/\[[^\[\]]*?%5F%5F[^\[\]]*?\]/e';
        $repl = 'str_replace(\'%5F%5F\', \'__\', \'\\0\')';

        $subs["inline"] = array($orig, $repl);

        /*****************************************************************
         * Patterns which match block markup constructs which take
         * special handling...
         */

        // Indented blocks
        $blockpats[] = '[ \t]+\S(?:.*\s*\n[ \t]+\S)*';
        // Tables
        $blockpats[] = '\|(?:.*\n\|)*';

        // List items
        $blockpats[] = '[#*;]*(?:[*#]|;.*?:)';

        // Footnote definitions
        $blockpats[] = '\[\s*(\d+)\s*\]';

        if (!$debug_skip) {
        // Plugins
            $blockpats[] = '<\?plugin(?:-form)?\b.*\?>\s*$';
        }

        // Section Title
        $blockpats[] = '!{1,3}[^!]';
        /*
    removed .|\n in the anchor not to crash on /m because with /m "." already includes \n
    this breaks headings but it doesn't crash anymore (crash on non-cgi, non-cli only)
    */
        $block_re = ( '/\A((?:.|\n)*?)(^(?:'
                      . join("|", $blockpats)
                      . ').*$)\n?/m' );
    }

    if ($markup_type != "block") {
        list ($orig, $repl) = $subs[$markup_type];
        return preg_replace($orig, $repl, $text);
    } else {
        list ($orig, $repl) = $subs['inline'];
        $out = '';
    //FIXME:
    // php crashes here in the 2nd paragraph of OldTextFormattingRules,
    // AnciennesR%E8glesDeFormatage and more
    // See http://www.pcre.org/pcre.txt LIMITATIONS
        while (preg_match($block_re, $text, $m)) {
            $text = substr($text, strlen($m[0]));
            list (,$leading_text, $block) = $m;
            $suffix = "\n";

            if (strchr(" \t", $block[0])) {
                // Indented block
                $prefix = "<pre>\n";
                $suffix = "\n</pre>\n";
            } elseif ($block[0] == '|') {
                // Old-style table
                $prefix = "<?plugin OldStyleTable\n";
                $suffix = "\n?>\n";
            } elseif (strchr("#*;", $block[0])) {
                // Old-style list item
                preg_match('/^([#*;]*)([*#]|;.*?:) */', $block, $m);
                list (,$ind,$bullet) = $m;
                $block = substr($block, strlen($m[0]));

                $indent = str_repeat('     ', strlen($ind));
                if ($bullet[0] == ';') {
                    //$term = ltrim(substr($bullet, 1));
                    //return $indent . $term . "\n" . $indent . '     ';
                    $prefix = $ind . $bullet;
                } else {
                    $prefix = $indent . $bullet . ' ';
                }
            } elseif ($block[0] == '[') {
                // Footnote definition
                preg_match('/^\[\s*(\d+)\s*\]/', $block, $m);
                $footnum = $m[1];
                $block = substr($block, strlen($m[0]));
                $prefix = "#[|ftnt_${footnum}]~[[${footnum}|#ftnt_ref_${footnum}]~] ";
            } elseif ($block[0] == '<') {
                // Plugin.
                // HACK: no inline markup...
                $prefix = $block;
                $block = '';
            } elseif ($block[0] == '!') {
                // Section heading
                preg_match('/^!{1,3}/', $block, $m);
                $prefix = $m[0];
                $block = substr($block, strlen($m[0]));
            } else {
                // AAck!
                assert(0);
            }
            if ($leading_text) {
                $leading_text = preg_replace($orig, $repl, $leading_text);
            }
            if ($block) {
                $block = preg_replace($orig, $repl, $block);
            }
            $out .= $leading_text;
            $out .= $prefix;
            $out .= $block;
            $out .= $suffix;
        }
        return $out . preg_replace($orig, $repl, $text);
    }
}


/**
 * Expand tabs in string.
 *
 * Converts all tabs to (the appropriate number of) spaces.
 *
 * @param string $str
 * @param int $tab_width
 * @return string
 */
function expand_tabs($str, $tab_width = 8)
{
    $split = preg_split("/\t/D", $str);
    $tail = array_pop($split);
    $expanded = "\n";
    foreach ($split as $hunk) {
        $expanded .= $hunk;
        $pos = strlen(strrchr($expanded, "\n")) - 1;
        $expanded .= str_repeat(" ", ($tab_width - $pos % $tab_width));
    }
    return substr($expanded, 1) . $tail;
}

/**
 * Split WikiWords in page names.
 *
 * It has been deemed useful to split WikiWords (into "Wiki Words") in
 * places like page titles. This is rumored to help search engines
 * quite a bit.
 *
 * @param $page string The page name.
 *
 * @return string The split name.
 */
function SplitPagename($page)
{
    if (preg_match("/\s/", $page)) {
        return $page;           // Already split --- don't split any more.
    }

    // This algorithm is specialized for several languages.
    // (Thanks to Pierrick MEIGNEN)
    // Improvements for other languages welcome.
    static $RE;
    if (!isset($RE)) {
        // This mess splits between a lower-case letter followed by
        // either an upper-case or a numeral; except that it wont
        // split the prefixes 'Mc', 'De', or 'Di' off of their tails.
        switch ($GLOBALS['LANG']) {
            case 'en':
            case 'it':
            case 'es':
            case 'de':
                $RE[] = '/([[:lower:]])((?<!Mc|De|Di)[[:upper:]]|\d)/';
                break;
            case 'fr':
                $RE[] = '/([[:lower:]])((?<!Mc|Di)[[:upper:]]|\d)/';
                break;
        }
        $sep = preg_quote(SUBPAGE_SEPARATOR, '/');
        // This the single-letter words 'I' and 'A' from any following
        // capitalized words.
        switch ($GLOBALS['LANG']) {
            case 'en':
                $RE[] = "/(?<= |${sep}|^)([AI])([[:upper:]][[:lower:]])/";
                break;
            case 'fr':
                $RE[] = "/(?<= |${sep}|^)([Á])([[:upper:]][[:lower:]])/";
                break;
        }
        // Split numerals from following letters.
        $RE[] = '/(\d)([[:alpha:]])/';
        // Split at subpage seperators. TBD in Theme.php
        $RE[] = "/([^${sep}]+)(${sep})/";

        foreach ($RE as $key) {
            $RE[$key] = $key;
        }
    }

    foreach ($RE as $regexp) {
        $page = preg_replace($regexp, '\\1 \\2', $page);
    }
    return $page;
}

function NoSuchRevision(&$request, $page, $version)
{
    $html = HTML(
        HTML::h2(_("Revision Not Found")),
        HTML::p(fmt(
            "I'm sorry.  Version %d of %s is not in the database.",
            $version,
            WikiLink($page, 'auto')
        ))
    );
    include_once('lib/Template.php');
    GeneratePage($html, _("Bad Version"), $page->getCurrentRevision());
    $request->finish();
}


/**
 * Get time offset for local time zone.
 *
 * @param $time time_t Get offset for this time. Default: now.
 * @param $no_colon boolean Don't put colon between hours and minutes.
 * @return string Offset as a string in the format +HH:MM.
 */
function TimezoneOffset($time = false, $no_colon = false)
{
    if ($time === false) {
        $time = time();
    }
    $secs = date('Z', $time);

    if ($secs < 0) {
        $sign = '-';
        $secs = -$secs;
    } else {
        $sign = '+';
    }
    $colon = $no_colon ? '' : ':';
    $mins = intval(($secs + 30) / 60);
    return sprintf(
        "%s%02d%s%02d",
        $sign,
        $mins / 60,
        $colon,
        $mins % 60
    );
}


/**
 * Format time in ISO-8601 format.
 *
 * @param $time time_t Time.  Default: now.
 * @return string Date and time in ISO-8601 format.
 */
function Iso8601DateTime($time = false)
{
    if ($time === false) {
        $time = time();
    }
    $tzoff = TimezoneOffset($time);
    $date  = date('Y-m-d', $time);
    $time  = date('H:i:s', $time);
    return $date . 'T' . $time . $tzoff;
}

/**
 * Format time in RFC-2822 format.
 *
 * @param $time time_t Time.  Default: now.
 * @return string Date and time in RFC-2822 format.
 */
function Rfc2822DateTime($time = false)
{
    if ($time === false) {
        $time = time();
    }
    return date('D, j M Y H:i:s ', $time) . TimezoneOffset($time, 'no colon');
}

/**
 * Format time in RFC-1123 format.
 *
 * @param $time time_t Time.  Default: now.
 * @return string Date and time in RFC-1123 format.
 */
function Rfc1123DateTime($time = false)
{
    if ($time === false) {
        $time = time();
    }
    return gmdate('D, d M Y H:i:s \G\M\T', $time);
}

/** Parse date in RFC-1123 format.
 *
 * According to RFC 1123 we must accept dates in the following
 * formats:
 *
 *   Sun, 06 Nov 1994 08:49:37 GMT  ; RFC 822, updated by RFC 1123
 *   Sunday, 06-Nov-94 08:49:37 GMT ; RFC 850, obsoleted by RFC 1036
 *   Sun Nov  6 08:49:37 1994       ; ANSI C's asctime() format
 *
 * (Though we're only allowed to generate dates in the first format.)
 */
function ParseRfc1123DateTime($timestr)
{
    $timestr = trim($timestr);
    if (preg_match(
        '/^ \w{3},\s* (\d{1,2}) \s* (\w{3}) \s* (\d{4}) \s*'
                   . '(\d\d):(\d\d):(\d\d) \s* GMT $/ix',
        $timestr,
        $m
    )) {
        list(, $mday, $mon, $year, $hh, $mm, $ss) = $m;
    } elseif (preg_match(
        '/^ \w+,\s* (\d{1,2})-(\w{3})-(\d{2}|\d{4}) \s*'
                       . '(\d\d):(\d\d):(\d\d) \s* GMT $/ix',
        $timestr,
        $m
    )) {
        list(, $mday, $mon, $year, $hh, $mm, $ss) = $m;
        if ($year < 70) {
            $year += 2000;
        } elseif ($year < 100) {
            $year += 1900;
        }
    } elseif (preg_match(
        '/^\w+\s* (\w{3}) \s* (\d{1,2}) \s*'
                       . '(\d\d):(\d\d):(\d\d) \s* (\d{4})$/ix',
        $timestr,
        $m
    )) {
        list(, $mon, $mday, $hh, $mm, $ss, $year) = $m;
    } else {
        // Parse failed.
        return false;
    }

    $time = strtotime("$mday $mon $year ${hh}:${mm}:${ss} GMT");
    if ($time == -1) {
        return false;           // failed
    }
    return $time;
}

/**
 * Format time to standard 'ctime' format.
 *
 * @param $time time_t Time.  Default: now.
 * @return string Date and time.
 */
function CTime($time = false)
{
    if ($time === false) {
        $time = time();
    }
    return date("D M j H:i:s Y", $time);
}


/**
 * Format number as kilobytes or bytes.
 * Short format is used for PageList
 * Long format is used in PageInfo
 *
 * @param $bytes       int.  Default: 0.
 * @param $longformat  bool. Default: false.
 * @return class FormattedText (XmlElement.php).
 */
function ByteFormatter($bytes = 0, $longformat = false)
{
    if ($bytes < 0) {
        return fmt("-???");
    }
    if ($bytes < 1024) {
        if (! $longformat) {
            $size = fmt("%s b", $bytes);
        } else {
            $size = fmt("%s bytes", $bytes);
        }
    } else {
        $kb = round($bytes / 1024, 1);
        if (! $longformat) {
            $size = fmt("%s k", $kb);
        } else {
            $size = fmt("%s Kb (%s bytes)", $kb, $bytes);
        }
    }
    return $size;
}

/**
 * Internationalized printf.
 *
 * This is essentially the same as PHP's built-in printf
 * with the following exceptions:
 * <ol>
 * <li> It passes the format string through gettext().
 * <li> It supports the argument reordering extensions.
 * </ol>
 *
 * Example:
 *
 * In php code, use:
 * <pre>
 *    __printf("Differences between versions %s and %s of %s",
 *             $new_link, $old_link, $page_link);
 * </pre>
 *
 * Then in locale/po/de.po, one can reorder the printf arguments:
 *
 * <pre>
 *    msgid "Differences between %s and %s of %s."
 *    msgstr "Der Unterschiedsergebnis von %3$s, zwischen %1$s und %2$s."
 * </pre>
 *
 * (Note that while PHP tries to expand $vars within double-quotes,
 * the values in msgstr undergo no such expansion, so the '$'s
 * okay...)
 *
 * One shouldn't use reordered arguments in the default format string.
 * Backslashes in the default string would be necessary to escape the
 * '$'s, and they'll cause all kinds of trouble....
 */
function PHPWikiPrintf($fmt)
{
    $args = func_get_args();
    array_shift($args);
    echo PHPWikiVsprintf($fmt, $args);
}

/**
 * Internationalized sprintf.
 *
 * This is essentially the same as PHP's built-in printf with the
 * following exceptions:
 *
 * <ol>
 * <li> It passes the format string through gettext().
 * <li> It supports the argument reordering extensions.
 * </ol>
 *
 * @see PHPWikiPrintf
 */
function PHPWikiSprintf($fmt)
{
    $args = func_get_args();
    array_shift($args);
    return PHPWikiVsprintf($fmt, $args);
}

/**
 * Internationalized vsprintf.
 *
 * This is essentially the same as PHP's built-in printf with the
 * following exceptions:
 *
 * <ol>
 * <li> It passes the format string through gettext().
 * <li> It supports the argument reordering extensions.
 * </ol>
 *
 * @see PHPWikiPrintf
 */
function PHPWikiVsprintf($fmt, $args)
{
    $fmt = gettext($fmt);
    // PHP's sprintf doesn't support variable with specifiers,
    // like sprintf("%*s", 10, "x"); --- so we won't either.

    if (preg_match_all('/(?<!%)%(\d+)\$/x', $fmt, $m)) {
        // Format string has '%2$s' style argument reordering.
        // PHP doesn't support this.
        if (preg_match('/(?<!%)%[- ]?\d*[^- \d$]/x', $fmt)) {
            // literal variable name substitution only to keep locale
            // strings uncluttered
            trigger_error(sprintf(
                _("Can't mix '%s' with '%s' type format strings"),
                '%1\$s',
                '%s'
            ), E_USER_WARNING); //php+locale error
        }

        $fmt = preg_replace('/(?<!%)%\d+\$/x', '%', $fmt);
        $newargs = array();

        // Reorder arguments appropriately.
        foreach ($m[1] as $argnum) {
            if ($argnum < 1 || $argnum > count($args)) {
                trigger_error(sprintf(
                    _("%s: argument index out of range"),
                    $argnum
                ), E_USER_WARNING);
            }
            $newargs[] = $args[$argnum - 1];
        }
        $args = $newargs;
    }

    // Not all PHP's have vsprintf, so...
    array_unshift($args, $fmt);
    return call_user_func_array('sprintf', $args);
}

function file_mtime($filename)
{
    if ($stat = @stat($filename)) {
        return $stat[9];
    } else {
        return false;
    }
}

function sort_file_mtime($a, $b)
{
    $ma = file_mtime($a);
    $mb = file_mtime($b);
    if (!$ma or !$mb or $ma == $mb) {
        return 0;
    }
    return ($ma > $mb) ? -1 : 1;
}

class fileSet
{
    /**
     * Build an array in $this->_fileList of files from $dirname.
     * Subdirectories are not traversed.
     *
     * (This was a function LoadDir in lib/loadsave.php)
     * See also http://www.php.net/manual/en/function.readdir.php
     */
    public function getFiles($exclude = false, $sortby = false, $limit = false)
    {
        $list = $this->_fileList;

        if ($sortby) {
            require_once('lib/PageList.php');
            switch (Pagelist::sortby($sortby, 'db')) {
                case 'pagename ASC':
                    break;
                case 'pagename DESC':
                    $list = array_reverse($list);
                    break;
                case 'mtime ASC':
                    usort($list, 'sort_file_mtime');
                    break;
                case 'mtime DESC':
                    usort($list, 'sort_file_mtime');
                    $list = array_reverse($list);
                    break;
            }
        }
        if ($limit) {
            return array_splice($list, 0, $limit);
        }
        return $list;
    }

    public function _filenameSelector($filename)
    {
        if (! $this->_pattern) {
            return true;
        } else {
            if (! $this->_pcre_pattern) {
                $this->_pcre_pattern = glob_to_pcre($this->_pattern);
            }
            return preg_match(
                '/' . $this->_pcre_pattern . ($this->_case ? '/' : '/i'),
                $filename
            );
        }
    }

    public function __construct($directory, $filepattern = false)
    {
        $this->_fileList = array();
        $this->_pattern = $filepattern;
        if ($filepattern) {
            $this->_pcre_pattern = glob_to_pcre($this->_pattern);
        }
        $this->_case = !isWindows();
        $this->_pathsep = '/';

        if (empty($directory)) {
            trigger_error(
                sprintf(_("%s is empty."), 'directoryname'),
                E_USER_NOTICE
            );
            return; // early return
        }

        @ $dir_handle = opendir($dir = $directory);
        if (empty($dir_handle)) {
            trigger_error(sprintf(
                _("Unable to open directory '%s' for reading"),
                $dir
            ), E_USER_NOTICE);
            return; // early return
        }

        while ($filename = readdir($dir_handle)) {
            if ($filename[0] == '.' || filetype($dir . $this->_pathsep . $filename) != 'file') {
                continue;
            }
            if ($this->_filenameSelector($filename)) {
                array_push($this->_fileList, "$filename");
                //trigger_error(sprintf(_("found file %s"), $filename),
                //                      E_USER_NOTICE); //debugging
            }
        }
        closedir($dir_handle);
    }
}

// File globbing

// expands a list containing regex's to its matching entries
class ListRegexExpand
{
    //var $match, $list, $index, $case_sensitive;
    public function __construct(&$list, $match, $case_sensitive = true)
    {
        $this->match = $match;
        $this->list = &$list;
        $this->case_sensitive = $case_sensitive;
        //$this->index = false;
    }
    public function listMatchCallback($item, $key)
    {
        $quoted = str_replace('/', '\/', $item);
        if (preg_match(
            '/' . $this->match . ($this->case_sensitive ? '/' : '/i'),
            $quoted
        )) {
            unset($this->list[$this->index]);
            $this->list[] = $item;
        }
    }
    public function expandRegex($index, &$pages)
    {
        $this->index = $index;
        array_walk($pages, array($this, 'listMatchCallback'));
        return $this->list;
    }
}

// Convert fileglob to regex style:
// Convert some wildcards to pcre style, escape the rest
// Escape . \\ + * ? [ ^ ] $ ( ) { } = ! < > | : /
// Fixed bug #994994: "/" in $glob.
function glob_to_pcre($glob)
{
    // check simple case: no need to escape
    $escape = '\[](){}=!<>|:/';
    if (strcspn($glob, $escape . ".+*?^$") == strlen($glob)) {
        return $glob;
    }
    // preg_replace cannot handle "\\\\\\2" so convert \\ to \xff
    $glob = strtr($glob, "\\", "\xff");
    $glob = str_replace("/", '\/', $glob);
    // first convert some unescaped expressions to pcre style: . => \.
    $special = ".^$";
    $re = preg_replace(
        '/([^\xff])?([' . preg_quote($special, '/') . '])/',
        "\\1\xff\\2",
        $glob
    );

    // * => .*, ? => .
    $re = preg_replace('/([^\xff])?\*/', '$1.*', $re);
    $re = preg_replace('/([^\xff])?\?/', '$1.', $re);
    if (!preg_match('/^[\?\*]/', $glob)) {
        $re = '^' . $re;
    }
    if (!preg_match('/[\?\*]$/', $glob)) {
        $re = $re . '$';
    }

    // .*? handled above, now escape the rest
    //while (strcspn($re, $escape) != strlen($re)) // loop strangely needed
    $re = preg_replace(
        '/([^\xff])([' . preg_quote($escape, "/") . '])/',
        "\\1\xff\\2",
        $re
    );
    return strtr($re, "\xff", "\\");
}

function glob_match($glob, $against, $case_sensitive = true)
{
    return preg_match(
        '/' . glob_to_pcre($glob) . ($case_sensitive ? '/' : '/i'),
        $against
    );
}

function explodeList($input, $allnames, $glob_style = true, $case_sensitive = true)
{
    $list = explode(',', $input);
    // expand wildcards from list of $allnames
    if (preg_match('/[\?\*]/', $input)) {
        // Optimizing loop invariants:
        // http://phplens.com/lens/php-book/optimizing-debugging-php.php
        for ($i = 0, $max = sizeof($list); $i < $max; $i++) {
            $f = $list[$i];
            if (preg_match('/[\?\*]/', $f)) {
                reset($allnames);
                $expand = new ListRegexExpand(
                    $list,
                    $glob_style ? glob_to_pcre($f) : $f,
                    $case_sensitive
                );
                $expand->expandRegex($i, $allnames);
            }
        }
    }
    return $list;
}

// echo implode(":",explodeList("Test*",array("xx","Test1","Test2")));
function explodePageList(
    $input,
    $include_empty = false,
    $sortby = 'pagename',
    $limit = false,
    $exclude = false
) {
    include_once("lib/PageList.php");
    return PageList::explodePageList($input, $include_empty, $sortby, $limit, $exclude);
}

// Class introspections

/**
 * Determine whether object is of a specified type.
 * In PHP builtin since 4.2.0 as is_a()
 * is_a() deprecated in PHP 5, in favor of instanceof operator

 * @param $object object An object.
 * @param $class string Class name.
 * @return bool True iff $object is a $class
 * or a sub-type of $class.
 */
function isa($object, $class)
{
    return is_object($object)
        && ( strtolower(get_class($object)) == strtolower($class)
             || is_subclass_of($object, $class) );
}

/** Determine whether (possible) object has method.
 *
 * @param $object mixed Object
 * @param $method string Method name
 * @return bool True iff $object is an object with has method $method.
 */
function can($object, $method)
{
    return is_object($object) && method_exists($object, strtolower($method));
}

/** Determine whether a function is okay to use.
 *
 * Some providers (e.g. Lycos) disable some of PHP functions for
 * "security reasons."  This makes those functions, of course,
 * unusable, despite the fact the function_exists() says they
 * exist.
 *
 * This function test to see if a function exists and is not
 * disallowed by PHP's disable_functions config setting.
 *
 * @param string $function_name  Function name
 * @return bool  True iff function can be used.
 */
function function_usable($function_name)
{
    static $disabled;
    if (!is_array($disabled)) {
        $disabled = array();
        // Use get_cfg_var since ini_get() is one of the disabled functions
        // (on Lycos, at least.)
        $split = preg_split('/\s*,\s*/', trim(get_cfg_var('disable_functions')));
        foreach ($split as $f) {
            $disabled[strtolower($f)] = true;
        }
    }

    return ( function_exists($function_name)
             and ! isset($disabled[strtolower($function_name)])
             );
}


/** Hash a value.
 *
 * This is used for generating ETags.
 */
function wikihash($x)
{
    if (is_scalar($x)) {
        return $x;
    } elseif (is_array($x)) {
        ksort($x);
        return md5(serialize($x));
    } elseif (is_object($x)) {
        return $x->hash();
    }
    trigger_error("Can't hash $x", E_USER_ERROR);
}


/**
 * Seed the random number generator.
 *
 * better_srand() ensures the randomizer is seeded only once.
 *
 * How random do you want it? See:
 * http://www.php.net/manual/en/function.srand.php
 * http://www.php.net/manual/en/function.mt-srand.php
 */
function better_srand($seed = '')
{
    static $wascalled = false;
    if (!$wascalled) {
        $seed = $seed === '' ? (double) microtime() * 1000000 : $seed;
        mt_srand($seed);
        $wascalled = true;
        //trigger_error("new random seed", E_USER_NOTICE); //debugging
    }
}

function rand_ascii($length = 1)
{
    better_srand();
    $s = "";
    for ($i = 1; $i <= $length; $i++) {
        // return only typeable 7 bit ascii, avoid quotes
        $s .= chr(mt_rand(40, 126));
    }
    return $s;
}

/**
 * Recursively count all non-empty elements
 * in array of any dimension or mixed - i.e.
 * array('1' => 2, '2' => array('1' => 3, '2' => 4))
 * See http://www.php.net/manual/en/function.count.php
 */
function count_all($arg)
{
    // skip if argument is empty
    if ($arg) {
        //print_r($arg); //debugging
        $count = 0;
        // not an array, return 1 (base case)
        if (!is_array($arg)) {
            return 1;
        }
        // else call recursively for all elements $arg
        foreach ($arg as $key => $val) {
            $count += count_all($val);
        }
        return $count;
    }
}

function isSubPage($pagename)
{
    return (strstr($pagename, SUBPAGE_SEPARATOR));
}

function subPageSlice($pagename, $pos)
{
    $pages = explode(SUBPAGE_SEPARATOR, $pagename);
    $pages = array_slice($pages, $pos, 1);
    return $pages[0];
}

/**
 * Alert
 *
 * Class for "popping up" and alert box.  (Except that right now, it doesn't
 * pop up...)
 *
 * FIXME:
 * This is a hackish and needs to be refactored.  However it would be nice to
 * unify all the different methods we use for showing Alerts and Dialogs.
 * (E.g. "Page deleted", login form, ...)
 */
class Alert
{
    /** Constructor
     *
     * @param object $request
     * @param mixed $head  Header ("title") for alert box.
     * @param mixed $body  The text in the alert box.
     * @param hash $buttons  An array mapping button labels to URLs.
     *    The default is a single "Okay" button pointing to $request->getURLtoSelf().
     */
    public function __construct($head, $body, $buttons = false)
    {
        if ($buttons === false) {
            $buttons = array();
        }

        $this->_tokens = array('HEADER' => $head, 'CONTENT' => $body);
        $this->_buttons = $buttons;
    }

    /**
     * Show the alert box.
     */
    public function show()
    {
        global $request;

        $tokens = $this->_tokens;
        $tokens['BUTTONS'] = $this->_getButtons();

        $request->discardOutput();
        $tmpl = new Template('dialog', $request, $tokens);
        $tmpl->printXML();
        $request->finish();
    }


    public function _getButtons()
    {
        global $request;

        $buttons = $this->_buttons;
        if (!$buttons) {
            $buttons = array(_("Okay") => $request->getURLtoSelf());
        }

        global $WikiTheme;
        foreach ($buttons as $label => $url) {
            print "$label $url\n";
        }
            $out[] = $WikiTheme->makeButton($label, $url, 'wikiaction');
        return new XmlContent($out);
    }
}

// 1.3.8     => 1030.08
// 1.3.9-p1  => 1030.091
// 1.3.10pre => 1030.099
// 1.3.11pre-20041120 => 1030.1120041120
// 1.3.12-rc1 => 1030.119
function phpwiki_version()
{
    static $PHPWIKI_VERSION;
    if (!isset($PHPWIKI_VERSION)) {
        $arr = explode('.', preg_replace('/\D+$/', '', PHPWIKI_VERSION)); // remove the pre
        $arr[2] = preg_replace('/\.+/', '.', preg_replace('/\D/', '.', $arr[2]));
        $PHPWIKI_VERSION = $arr[0] * 1000 + $arr[1] * 10 + 0.01 * $arr[2];
        if (strstr(PHPWIKI_VERSION, 'pre') or strstr(PHPWIKI_VERSION, 'rc')) {
            $PHPWIKI_VERSION -= 0.01;
        }
    }
    return $PHPWIKI_VERSION;
}

function phpwiki_gzhandler($ob)
{
    if (function_exists('gzencode')) {
        $ob = gzencode($ob);
    }
        $GLOBALS['request']->_ob_get_length = strlen($ob);
    if (!headers_sent()) {
        header(sprintf("Content-Length: %d", $GLOBALS['request']->_ob_get_length));
    }
    return $ob;
}

function isWikiWord($word)
{
    global $WikiNameRegexp;
    //or preg_match('/\A' . $WikiNameRegexp . '\z/', $word) ??
    return preg_match("/^$WikiNameRegexp\$/", $word);
}

// needed to store serialized objects-values only (perm, pref)
function obj2hash($obj, $exclude = false, $fields = false)
{
    $a = array();
    if (! $fields) {
        $fields = get_object_vars($obj);
    }
    foreach ($fields as $key => $val) {
        if (is_array($exclude)) {
            if (in_array($key, $exclude)) {
                continue;
            }
        }
        $a[$key] = $val;
    }
    return $a;
}

/**
 * isUtf8String($string) - cheap utf-8 detection
 *
 * segfaults for strings longer than 10kb!
 * Use http://www.phpdiscuss.com/article.php?id=565&group=php.i18n or
 * checkTitleEncoding() at http://cvs.sourceforge.net/viewcvs.py/wikipedia/phase3/languages/Language.php
 */
function isUtf8String($s)
{
    $ptrASCII  = '[\x00-\x7F]';
    $ptr2Octet = '[\xC2-\xDF][\x80-\xBF]';
    $ptr3Octet = '[\xE0-\xEF][\x80-\xBF]{2}';
    $ptr4Octet = '[\xF0-\xF4][\x80-\xBF]{3}';
    $ptr5Octet = '[\xF8-\xFB][\x80-\xBF]{4}';
    $ptr6Octet = '[\xFC-\xFD][\x80-\xBF]{5}';
    return preg_match("/^($ptrASCII|$ptr2Octet|$ptr3Octet|$ptr4Octet|$ptr5Octet|$ptr6Octet)*$/s", $s);
}

/**
 * Check for UTF-8 URLs; Internet Explorer produces these if you
 * type non-ASCII chars in the URL bar or follow unescaped links.
 * Requires urldecoded pagename.
 * Fixes sf.net bug #953949
 *
 * src: languages/Language.php:checkTitleEncoding() from mediawiki
 */
function fixTitleEncoding($s)
{
    global $charset;

    $s = trim($s);
    // print a warning?
    if (empty($s)) {
        return $s;
    }

    $ishigh = preg_match('/[\x80-\xff]/', $s);
    /*
    $isutf = ($ishigh ? preg_match( '/^([\x00-\x7f]|[\xc0-\xdf][\x80-\xbf]|' .
                                    '[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xf7][\x80-\xbf]{3})+$/', $s ) : true );
    */
    $isutf = ($ishigh ? isUtf8String($s) : true);
    $locharset = strtolower($charset);

    if ($locharset != "utf-8" and $ishigh and $isutf) {
        // if charset == 'iso-8859-1' then simply use utf8_decode()
        if ($locharset == 'iso-8859-1') {
            return utf8_decode($s);
        } else { // TODO: check for iconv support
            return iconv("UTF-8", $charset, $s);
        }
    }

    if ($locharset == "utf-8" and $ishigh and !$isutf) {
        return utf8_encode($s);
    }

    // Other languages can safely leave this function, or replace
    // it with one to detect and convert another legacy encoding.
    return $s;
}

/**
 * MySQL fulltext index doesn't grok utf-8, so we
 * need to fold cases and convert to hex.
 * src: languages/Language.php:stripForSearch() from mediawiki
 */
/*
function stripForSearch( $string ) {
    global $wikiLowerChars;
    // '/(?:[a-z]|\xc3[\x9f-\xbf]|\xc4[\x81\x83\x85\x87])/' => "a-z\xdf-\xf6\xf8-\xff"
    return preg_replace(
                        "/([\\xc0-\\xff][\\x80-\\xbf]*)/e",
                        "'U8' . bin2hex( strtr( \"\$1\", \$wikiLowerChars ) )",
                        $string );
}
*/

/**
 * Workaround for allow_url_fopen, to get the content of an external URI.
 * It returns the contents in one slurp. Parsers might want to check for allow_url_fopen
 * and use fopen, fread chunkwise. (see lib/XmlParser.php)
 */
function url_get_contents($uri)
{
    $client          = \Tuleap\Http\HttpClientFactory::createClient();
    $request_factory = \Tuleap\Http\HTTPFactoryBuilder::requestFactory();
    try {
        $response = $client->sendRequest(
            $request_factory->createRequest('GET', $uri)
        );
    } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
        return false;
    }

    $content = $response->getBody()->getContents();

    if ($content === '') {
        return false;
    }

    return $content;
}

/**
 * Generate consecutively named strings:
 *   Name, Name2, Name3, ...
 */
function GenerateId($name)
{
    static $ids = array();
    if (empty($ids[$name])) {
        $ids[$name] = 1;
        return $name;
    } else {
        $ids[$name]++;
        return $name . $ids[$name];
    }
}

// from IncludePage. To be of general use.
// content: string or array of strings
function firstNWordsOfContent($n, $content)
{
    if ($content and $n > 0) {
        if (is_array($content)) {
            // fixme: return a list of lines then?
            //$content = join("\n", $content);
            //$return_array = true;
            $wordcount = 0;
            foreach ($content as $line) {
                $words = explode(' ', $line);
                if ($wordcount + count($words) > $n) {
                    $new[] = implode(' ', array_slice($words, 0, $n - $wordcount))
                           . sprintf(_("... (first %s words)"), $n);
                    return $new;
                } else {
                    $wordcount += count($words);
                    $new[] = $line;
                }
            }
            return $new;
        } else {
            // fixme: use better whitespace/word seperators
            $words = explode(' ', $content);
            if (count($words) > $n) {
                return join(' ', array_slice($words, 0, $n))
                       . sprintf(_("... (first %s words)"), $n);
            } else {
                return $content;
            }
        }
    } else {
        return '';
    }
}

// moved from lib/plugin/IncludePage.php
function extractSection($section, $content, $page, $quiet = false, $sectionhead = false)
{
    $qsection = preg_replace('/\s+/', '\s+', preg_quote($section, '/'));

    if (preg_match(
        "/ ^(!{1,})\\s*$qsection" // section header
                   . "  \\s*$\\n?"           // possible blank lines
                   . "  ( (?: ^.*\\n? )*? )" // some lines
                   . "  (?= ^\\1 | \\Z)/xm", // sec header (same or higher level) (or EOF)
        implode("\n", $content),
        $match
    )) {
        // Strip trailing blanks lines and ---- <hr>s
        $text = preg_replace("/\\s*^-{4,}\\s*$/m", "", $match[2]);
        if ($sectionhead) {
            $text = $match[1] . $section . "\n" . $text;
        }
        return explode("\n", $text);
    }
    if ($quiet) {
        $mesg = $page . " " . $section;
    } else {
        $mesg = $section;
    }
    return array(sprintf(_("<%s: no such section>"), $mesg));
}

// use this faster version: only load ExternalReferrer if we came from an external referrer
function isExternalReferrer(&$request)
{
    if ($referrer = $request->get('HTTP_REFERER')) {
        $home = SERVER_URL; // SERVER_URL or SCRIPT_NAME, if we want to check sister wiki's also
        if (string_starts_with(strtolower($referrer), strtolower($home))) {
            return false;
        }
        require_once("lib/ExternalReferrer.php");
        $se = new SearchEngines();
        return $se->parseSearchQuery($referrer);
    }
    return false;
}

/**
 * Useful for PECL overrides: cvsclient, ldap, soap, xmlrpc, pdo, pdo_<driver>
 */
function loadPhpExtension($extension)
{
    return extension_loaded($extension);
}

function string_starts_with($string, $prefix)
{
    return (substr($string, 0, strlen($prefix)) == $prefix);
}
function string_ends_with($string, $suffix)
{
    return (substr($string, -strlen($suffix)) == $suffix);
}

/**
 * Ensure that the script will have another $secs time left.
 * Works only if safe_mode is off.
 * For example not to timeout on waiting socket connections.
 *   Use the socket timeout as arg.
 */
function longer_timeout($secs = 30)
{
    $timeout = @ini_get("max_execution_time") ? ini_get("max_execution_time") : 30;
    $timeleft = $timeout - $GLOBALS['RUNTIMER']->getTime();
    if ($timeleft < $secs) {
        @set_time_limit(max($timeout, (integer) ($secs + $timeleft)));
    }
}

function printSimpleTrace($bt)
{
    //print_r($bt);
    echo "Traceback:\n";
    foreach ($bt as $i => $elem) {
        if (!array_key_exists('file', $elem)) {
            continue;
        }
        echo join(" ", array_values($elem)),"\n";
        //print "  " . $elem['file'] . ':' . $elem['line'] . " " .$elem['function']"\n";
    }
}

/**
 * Return the used process memory (in byte?)
 * Enable the section which will work for you. (They are very slow)
 * Special quirks for Windows: Requires cygwin.
 */
function getMemoryUsage()
{
    return memory_get_usage();
}

// $Log: stdlib.php,v $
// Revision 1.249  2005/10/30 14:24:33  rurban
// move rand_ascii_readable from Captcha to stdlib
//
// Revision 1.248  2005/10/29 14:18:30  uckelman
// Added is_a() deprecation note.
//
// Revision 1.247  2005/10/10 20:31:21  rurban
// fix win32ps call
//
// Revision 1.246  2005/10/10 19:38:48  rurban
// add win32ps
//
// Revision 1.245  2005/09/18 16:01:09  rurban
// trick to send the correct gzipped Content-Length
//
// Revision 1.244  2005/09/11 13:24:33  rurban
// fix shortname, dont quote twice in ListRegexExpand
//
// Revision 1.243  2005/08/06 15:01:38  rurban
// workaround php VBASIC alike limitation: allow integer pagenames
//
// Revision 1.242  2005/08/06 13:07:04  rurban
// quote paths correctly (not the best method though)
//
// Revision 1.241  2005/05/06 16:54:19  rurban
// support optional EXTERNAL_LINK_TARGET, default: _blank
//
// Revision 1.240  2005/04/23 11:15:49  rurban
// handle allowed inlined objects within INLINE_IMAGES
//
// Revision 1.239  2005/04/01 16:11:42  rurban
// just whitespace
//
// Revision 1.238  2005/03/04 16:29:14  rurban
// Fixed bug #994994 (escape / in glob)
// Optimized glob_to_pcre within fileSet() matching.
//
// Revision 1.237  2005/02/12 17:22:18  rurban
// locale update: missing . : fixed. unified strings
// proper linebreaks
//
// Revision 1.236  2005/02/08 13:41:32  rurban
// add rand_ascii
//
// Revision 1.235  2005/02/04 11:54:48  rurban
// fix Talk: names
//
// Revision 1.234  2005/02/03 05:09:25  rurban
// Talk: + User: fix
//
// Revision 1.233  2005/02/02 20:40:12  rurban
// fix Talk: and User: names and links
//
// Revision 1.232  2005/02/02 19:34:09  rurban
// more maps: Talk, User
//
// Revision 1.231  2005/01/30 19:48:52  rurban
// enable ps memory on unix
//
// Revision 1.230  2005/01/25 07:10:51  rurban
// add getMemoryUsage to stdlib
//
// Revision 1.229  2005/01/21 11:51:22  rurban
// changed (c)
//
// Revision 1.228  2005/01/17 20:28:30  rurban
// Allow more pagename chars: Limit only on certain backends.
// Re-Allow : and ; and control chars on non-file backends.
//
// Revision 1.227  2005/01/14 18:32:08  uckelman
// ConvertOldMarkup did not properly handle links containing pairs of pairs
// of underscores. (E.g., [http://example.com/foo__bar__.html] would be
// munged by the regex for bold text.) Now '__' in links are hidden prior to
// conversion of '__' into '<strong>', and then unhidden afterwards.
//
// Revision 1.226  2004/12/26 17:12:06  rurban
// avoid stdargs in url, php5 fixes
//
// Revision 1.225  2004/12/22 19:02:29  rurban
// fix glob for starting * or ?
//
// Revision 1.224  2004/12/20 12:11:50  rurban
// fix "lib/stdlib.php:1348: Warning[2]: Compilation failed: unmatched parentheses at offset 2"
//   not reproducable other than on sf.net, but this seems to fix it.
//
// Revision 1.223  2004/12/18 16:49:29  rurban
// fix RPC for !USE_PATH_INFO, add debugging helper
//
// Revision 1.222  2004/12/17 16:40:45  rurban
// add not yet used url helper
//
// Revision 1.221  2004/12/06 19:49:58  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.220  2004/11/30 17:47:41  rurban
// added mt_srand, check for native isa
//
// Revision 1.219  2004/11/26 18:39:02  rurban
// new regex search parser and SQL backends (90% complete, glob and pcre backends missing)
//
// Revision 1.218  2004/11/25 08:28:48  rurban
// support exclude
//
// Revision 1.217  2004/11/16 17:31:03  rurban
// re-enable old block markup conversion
//
// Revision 1.216  2004/11/11 18:31:26  rurban
// add simple backtrace on such general failures to get at least an idea where
//
// Revision 1.215  2004/11/11 14:34:12  rurban
// minor clarifications
//
// Revision 1.214  2004/11/11 11:01:20  rurban
// fix loadPhpExtension
//
// Revision 1.213  2004/11/01 10:43:57  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.212  2004/10/22 09:15:39  rurban
// Alert::show has no arg anymore
//
// Revision 1.211  2004/10/22 09:05:11  rurban
// added longer_timeout (HttpClient)
// fixed warning
//
// Revision 1.210  2004/10/14 21:06:02  rurban
// fix dumphtml with USE_PATH_INFO (again). fix some PageList refs
//
// Revision 1.209  2004/10/14 19:19:34  rurban
// loadsave: check if the dumped file will be accessible from outside.
// and some other minor fixes. (cvsclient native not yet ready)
//
// Revision 1.208  2004/10/12 13:13:20  rurban
// php5 compatibility (5.0.1 ok)
//
// Revision 1.207  2004/09/26 12:21:40  rurban
// removed old log entries.
// added persistent start_debug on internal links and DEBUG
// added isExternalReferrer (not yet used)
//
// Revision 1.206  2004/09/25 16:28:36  rurban
// added to TOC, firstNWordsOfContent is now plugin compatible, added extractSection
//
// Revision 1.205  2004/09/23 13:59:35  rurban
// Before removing a page display a sample of 100 words.
//
// Revision 1.204  2004/09/17 13:19:15  rurban
// fix LinkPhpwikiURL bug reported in http://phpwiki.sourceforge.net/phpwiki/KnownBugs
// by SteveBennett.
//
// Revision 1.203  2004/09/16 08:00:52  rurban
// just some comments
//
// Revision 1.202  2004/09/14 10:11:44  rurban
// start 2nd Id with ...Plugin2
//
// Revision 1.201  2004/09/14 10:06:42  rurban
// generate iterated plugin ids, set plugin span id also
//
// Revision 1.200  2004/08/05 17:34:26  rurban
// move require to sortby branch
//
// Revision 1.199  2004/08/05 10:38:15  rurban
// fix Bug #993692:  Making Snapshots or Backups doesn't work anymore
// in CVS version.
//
// Revision 1.198  2004/07/02 10:30:36  rurban
// always disable getimagesize for < php-4.3 with external png's
//
// Revision 1.197  2004/07/02 09:55:58  rurban
// more stability fixes: new DISABLE_GETIMAGESIZE if your php crashes when loading LinkIcons: failing getimagesize in old phps; blockparser stabilized
//
// Revision 1.196  2004/07/01 08:51:22  rurban
// dumphtml: added exclude, print pagename before processing
//
// Revision 1.195  2004/06/29 08:52:22  rurban
// Use ...version() $need_content argument in WikiDB also:
// To reduce the memory footprint for larger sets of pagelists,
// we don't cache the content (only true or false) and
// we purge the pagedata (_cached_html) also.
// _cached_html is only cached for the current pagename.
// => Vastly improved page existance check, ACL check, ...
//
// Now only PagedList info=content or size needs the whole content, esp. if sortable.
//
// Revision 1.194  2004/06/29 06:48:04  rurban
// Improve LDAP auth and GROUP_LDAP membership:
//   no error message on false password,
//   added two new config vars: LDAP_OU_USERS and LDAP_OU_GROUP with GROUP_METHOD=LDAP
//   fixed two group queries (this -> user)
// stdlib: ConvertOldMarkup still flawed
//
// Revision 1.193  2004/06/28 13:27:03  rurban
// CreateToc disabled for old markup and Apache2 only
//
// Revision 1.192  2004/06/28 12:47:43  rurban
// skip if non-DEBUG and old markup with CreateToc
//
// Revision 1.191  2004/06/25 14:31:56  rurban
// avoid debug_skip warning
//
// Revision 1.190  2004/06/25 14:29:20  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.189  2004/06/20 09:45:35  rurban
// php5 isa fix (wrong strtolower)
//
// Revision 1.188  2004/06/16 10:38:58  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.187  2004/06/14 11:31:37  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.186  2004/06/13 13:54:25  rurban
// Catch fatals on the four dump calls (as file and zip, as html and mimified)
// FoafViewer: Check against external requirements, instead of fatal.
// Change output for xhtmldumps: using file:// urls to the local fs.
// Catch SOAP fatal by checking for GOOGLE_LICENSE_KEY
// Import GOOGLE_LICENSE_KEY and FORTUNE_DIR from config.ini.
//
// Revision 1.185  2004/06/11 09:07:30  rurban
// support theme-specific LinkIconAttr: front or after or none
//
// Revision 1.184  2004/06/04 20:32:53  rurban
// Several locale related improvements suggested by Pierrick Meignen
// LDAP fix by John Cole
// reanable admin check without ENABLE_PAGEPERM in the admin plugins
//
// Revision 1.183  2004/06/01 10:22:56  rurban
// added url_get_contents() used in XmlParser and elsewhere
//
// Revision 1.182  2004/05/25 12:40:48  rurban
// trim the pagename
//
// Revision 1.181  2004/05/25 10:18:44  rurban
// Check for UTF-8 URLs; Internet Explorer produces these if you
// type non-ASCII chars in the URL bar or follow unescaped links.
// Fixes sf.net bug #953949
// src: languages/Language.php:checkTitleEncoding() from mediawiki
//
// Revision 1.180  2004/05/18 16:23:39  rurban
// rename split_pagename to SplitPagename
//
// Revision 1.179  2004/05/18 16:18:37  rurban
// AutoSplit at subpage seperators
// RssFeed stability fix for empty feeds or broken connections
//
// Revision 1.178  2004/05/12 10:49:55  rurban
// require_once fix for those libs which are loaded before FileFinder and
//   its automatic include_path fix, and where require_once doesn't grok
//   dirname(__FILE__) != './lib'
// upgrade fix with PearDB
// navbar.tmpl: remove spaces for IE &nbsp; button alignment
//
// Revision 1.177  2004/05/08 14:06:12  rurban
// new support for inlined image attributes: [image.jpg size=50x30 align=right]
// minor stability and portability fixes
//
// Revision 1.176  2004/05/08 11:25:15  rurban
// php-4.0.4 fixes
//
// Revision 1.175  2004/05/06 17:30:38  rurban
// CategoryGroup: oops, dos2unix eol
// improved phpwiki_version:
//   pre -= .0001 (1.3.10pre: 1030.099)
//   -p1 += .001 (1.3.9-p1: 1030.091)
// improved InstallTable for mysql and generic SQL versions and all newer tables so far.
// abstracted more ADODB/PearDB methods for action=upgrade stuff:
//   backend->backendType(), backend->database(),
//   backend->listOfFields(),
//   backend->listOfTables(),
//
// Revision 1.174  2004/05/06 12:02:05  rurban
// fix sf.net bug#949002: [ Link | ] assertion
//
// Revision 1.173  2004/05/03 15:00:31  rurban
// added more database upgrading: session.sess_ip, page.id autp_increment
//
// Revision 1.172  2004/04/26 20:44:34  rurban
// locking table specific for better databases
//
// Revision 1.171  2004/04/19 23:13:03  zorloc
// Connect the rest of PhpWiki to the IniConfig system.  Also the keyword regular expression is not a config setting
//
// Revision 1.170  2004/04/19 18:27:45  rurban
// Prevent from some PHP5 warnings (ref args, no :: object init)
//   php5 runs now through, just one wrong XmlElement object init missing
// Removed unneccesary UpgradeUser lines
// Changed WikiLink to omit version if current (RecentChanges)
//
// Revision 1.169  2004/04/15 21:29:48  rurban
// allow [0] with new markup: link to page "0"
//
// Revision 1.168  2004/04/10 02:30:49  rurban
// Fixed gettext problem with VIRTUAL_PATH scripts (Windows only probably)
// Fixed "cannot setlocale..." (sf.net problem)
//
// Revision 1.167  2004/04/02 15:06:55  rurban
// fixed a nasty ADODB_mysql session update bug
// improved UserPreferences layout (tabled hints)
// fixed UserPreferences auth handling
// improved auth stability
// improved old cookie handling: fixed deletion of old cookies with paths
//
// Revision 1.166  2004/04/01 15:57:10  rurban
// simplified Sidebar theme: table, not absolute css positioning
// added the new box methods.
// remaining problems: large left margin, how to override _autosplitWikiWords in Template only
//
// Revision 1.165  2004/03/24 19:39:03  rurban
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
// Revision 1.164  2004/03/18 21:41:09  rurban
// fixed sqlite support
// WikiUserNew: PHP5 fixes: don't assign $this (untested)
//
// Revision 1.163  2004/03/17 18:41:49  rurban
// just reformatting
//
// Revision 1.162  2004/03/16 15:43:08  rurban
// make fileSet sortable to please PageList
//
// Revision 1.161  2004/03/12 15:48:07  rurban
// fixed explodePageList: wrong sortby argument order in UnfoldSubpages
// simplified lib/stdlib.php:explodePageList
//
// Revision 1.160  2004/02/28 21:14:08  rurban
// generally more PHPDOC docs
//   see http://xarch.tu-graz.ac.at/home/rurban/phpwiki/xref/
// fxied WikiUserNew pref handling: empty theme not stored, save only
//   changed prefs, sql prefs improved, fixed password update,
//   removed REPLACE sql (dangerous)
// moved gettext init after the locale was guessed
// + some minor changes
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
