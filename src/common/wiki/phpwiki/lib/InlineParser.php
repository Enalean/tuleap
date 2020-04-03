<?php
/* Copyright (C) 2002 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * Copyright (C) 2004,2005 Reini Urban
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
 * This is the code which deals with the inline part of the (new-style)
 * wiki-markup.
 *
 */

/**
 * This is the character used in wiki markup to escape characters with
 * special meaning.
 */
define('ESCAPE_CHAR', '~');

require_once(dirname(__FILE__) . '/HtmlElement.php');
require_once('lib/CachedMarkup.php');
require_once(dirname(__FILE__) . '/stdlib.php');


function WikiEscape($text)
{
    return str_replace('#', ESCAPE_CHAR . '#', $text);
}

function UnWikiEscape($text)
{
    return preg_replace('/' . ESCAPE_CHAR . '(.)/', '\1', $text);
}

/**
 * Return type from RegexpSet::match and RegexpSet::nextMatch.
 *
 * @see RegexpSet
 */
class RegexpSet_match
{
    /**
     * The text leading up the the next match.
     */
    public $prematch;
    /**
     * The matched text.
     */
    public $match;
    /**
     * The text following the matched text.
     */
    public $postmatch;
    /**
     * Index of the regular expression which matched.
     */
    public $regexp_ind;
}

/**
 * A set of regular expressions.
 *
 * This class is probably only useful for InlineTransformer.
 */
class RegexpSet
{
    /** Constructor
     *
     * @param array $regexps A list of regular expressions.  The
     * regular expressions should not include any sub-pattern groups
     * "(...)".  (Anonymous groups, like "(?:...)", as well as
     * look-ahead and look-behind assertions are okay.)
     */
    public function __construct($regexps)
    {
        assert($regexps);
        $this->_regexps = array_unique($regexps);
        if (!defined('_INLINE_OPTIMIZATION')) {
            define('_INLINE_OPTIMIZATION', 0);
        }
    }

    /**
     * Search text for the next matching regexp from the Regexp Set.
     *
     * @param string $text The text to search.
     *
     * @return RegexpSet_match  A RegexpSet_match object, or false if no match.
     */
    public function match($text)
    {
        return $this->_match($text, $this->_regexps, '*?');
    }

    /**
     * Search for next matching regexp.
     *
     * Here, 'next' has two meanings:
     *
     * Match the next regexp(s) in the set, at the same position as the last match.
     *
     * If that fails, match the whole RegexpSet, starting after the position of the
     * previous match.
     *
     * @param string $text Text to search.
     *
     * @param RegexpSet_match $prevMatch A RegexpSet_match object.
     * $prevMatch should be a match object obtained by a previous
     * match upon the same value of $text.
     *
     * @return RegexpSet_match A RegexpSet_match object, or false if no match.
     */
    public function nextMatch($text, $prevMatch)
    {
        // Try to find match at same position.
        $pos = strlen($prevMatch->prematch);
        $regexps = array_slice($this->_regexps, $prevMatch->regexp_ind + 1);
        if ($regexps) {
            $repeat = sprintf('{%d}', $pos);
            if (($match = $this->_match($text, $regexps, $repeat))) {
                $match->regexp_ind += $prevMatch->regexp_ind + 1;
                return $match;
            }
        }

        // Failed.  Look for match after current position.
        $repeat = sprintf('{%d,}?', $pos + 1);
        return $this->_match($text, $this->_regexps, $repeat);
    }

    // Syntax: http://www.pcre.org/pcre.txt
    //   x - EXTENDED, ignore whitespace
    //   s - DOTALL
    //   A - ANCHORED
    //   S - STUDY
    public function _match($text, $regexps, $repeat)
    {
        // If one of the regexps is an empty string, php will crash here:
        // sf.net: Fatal error: Allowed memory size of 8388608 bytes exhausted
        //         (tried to allocate 634 bytes)
        if (_INLINE_OPTIMIZATION) { // disabled, wrong
        // So we try to minize memory usage, by looping explicitly,
        // and storing only those regexp which actually match.
        // There may be more than one, so we have to find the longest,
        // and match inside until the shortest is empty.
            $matched = array();
            $matched_ind = array();
            for ($i = 0; $i < count($regexps); $i++) {
                if (!trim($regexps[$i])) {
                    trigger_error("empty regexp $i", E_USER_WARNING);
                    continue;
                }
                $pat = "/ ( . $repeat ) ( " . $regexps[$i] . " ) /x";
                if (preg_match($pat, $text, $_m)) {
                    $m = $_m; // FIXME: prematch, postmatch is wrong
                    $matched[] = $regexps[$i];
                    $matched_ind[] = $i;
                    $regexp_ind = $i;
                }
            }
        // To overcome ANCHORED:
        // We could sort by longest match and iterate over these.
            if (empty($matched)) {
                return false;
            }
        }
        $match = new RegexpSet_match();

        // Optimization: if the matches are only "$" and another, then omit "$"
        if (! _INLINE_OPTIMIZATION or count($matched) > 2) {
            assert(!empty($repeat));
            assert(!empty($regexps));
            for ($i = 0; $i < count($regexps); $i++) {
                if (!trim($regexps[$i])) {
                    trigger_error("empty regexp $i", E_USER_WARNING);
                    $regexps[$i] = '\Wxxxx\w\W\w\W\w\W\w\W\w\W\w'; // some placeholder
                }
            }
            // We could do much better, if we would know the matching markup for the
            // longest regexp match:
            $hugepat = "/ ( . $repeat ) ( (" . join(')|(', $regexps) . ") ) /Asx";
            // Proposed premature optimization 1:
            //$hugepat= "/ ( . $repeat ) ( (" . join(')|(', array_values($matched)) . ") ) /Asx";
            if (! preg_match($hugepat, $text, $m)) {
                return false;
            }
            // Proposed premature optimization 1:
            //$match->regexp_ind = $matched_ind[count($m) - 4];
            $match->regexp_ind = count($m) - 4;
        } else {
            $match->regexp_ind = $regexp_ind;
        }

        $match->postmatch = substr($text, strlen($m[0]));
        $match->prematch = $m[1];
        $match->match = $m[2];

        /* DEBUGGING */
        /*
        if (DEBUG & 4) {
          var_dump($regexps); var_dump($matched); var_dump($matched_inc);
        PrintXML(HTML::dl(HTML::dt("input"),
                          HTML::dd(HTML::pre($text)),
                          HTML::dt("regexp"),
                          HTML::dd(HTML::pre($match->regexp_ind, ":", $regexps[$match->regexp_ind])),
                          HTML::dt("prematch"),
                          HTML::dd(HTML::pre($match->prematch)),
                          HTML::dt("match"),
                          HTML::dd(HTML::pre($match->match)),
                          HTML::dt("postmatch"),
                          HTML::dd(HTML::pre($match->postmatch))
                          ));
        }
        */
        return $match;
    }
}



/**
 * A simple markup rule (i.e. terminal token).
 *
 * These are defined by a regexp.
 *
 * When a match is found for the regexp, the matching text is replaced.
 * The replacement content is obtained by calling the SimpleMarkup::markup method.
 */
class SimpleMarkup
{
    public $_match_regexp;

    /** Get regexp.
     *
     * @return string Regexp which matches this token.
     */
    public function getMatchRegexp()
    {
        return $this->_match_regexp;
    }

    /** Markup matching text.
     *
     * @param string $match The text which matched the regexp
     * (obtained from getMatchRegexp).
     *
     * @return mixed The expansion of the matched text.
     */
    public function markup($match /*, $body */)
    {
        trigger_error("pure virtual", E_USER_ERROR);
    }
}

/**
 * A balanced markup rule.
 *
 * These are defined by a start regexp, and an end regexp.
 */
class BalancedMarkup
{
    public $_start_regexp;

    /** Get the starting regexp for this rule.
     *
     * @return string The starting regexp.
     */
    public function getStartRegexp()
    {
        return $this->_start_regexp;
    }

    /** Get the ending regexp for this rule.
     *
     * @param string $match The text which matched the starting regexp.
     *
     * @return string The ending regexp.
     */
    public function getEndRegexp($match)
    {
        return $this->_end_regexp;
    }

    /** Get expansion for matching input.
     *
     * @param string $match The text which matched the starting regexp.
     *
     * @param mixed $body Transformed text found between the starting
     * and ending regexps.
     *
     * @return mixed The expansion of the matched text.
     */
    public function markup($match, $body)
    {
        trigger_error("pure virtual", E_USER_ERROR);
    }
}

class Markup_escape extends SimpleMarkup
{
    public function getMatchRegexp()
    {
        return ESCAPE_CHAR . '(?: [[:alnum:]]+ | .)';
    }

    public function markup($match)
    {
        assert(strlen($match) >= 2);
        return substr($match, 1);
    }
}

/**
 * [image.jpg size=50% border=5], [image.jpg size=50x30]
 * Support for the following attributes: see stdlib.php:LinkImage()
 *   size=<precent>%, size=<width>x<height>
 *   border=n, align=\w+, hspace=n, vspace=n
 */
function isImageLink($link)
{
    if (!$link) {
        return false;
    }
    assert(defined('INLINE_IMAGES'));
    return preg_match("/\\.(" . INLINE_IMAGES . ")$/i", $link)
        or preg_match("/\\.(" . INLINE_IMAGES . ")\s+(size|border|align|hspace|vspace)=/i", $link);
}

function LinkBracketLink($bracketlink)
{
    // $bracketlink will start and end with brackets; in between will
    // be either a page name, a URL or both separated by a pipe.

    // strip brackets and leading space
    // FIXME: \n inside [] will lead to errors
    preg_match(
        '/(\#?) \[\s* (?: (.*?) \s* (?<!' . ESCAPE_CHAR . ')(\|) )? \s* (.+?) \s*\]/x',
        $bracketlink,
        $matches
    );
    if (count($matches) < 4) {
        trigger_error(_("Invalid [] syntax ignored") . ": " . $bracketlink, E_USER_WARNING);
        return new Cached_Link();
    }
    list (, $hash, $label, $bar, $rawlink) = $matches;

    $label = UnWikiEscape($label);
    /*
     * Check if the user has typed a explicit URL. This solves the
     * problem where the URLs have a ~ character, which would be stripped away.
     *   "[http:/server/~name/]" will work as expected
     *   "http:/server/~name/"   will NOT work as expected, will remove the ~
     */
    if (strstr($rawlink, "http://") or strstr($rawlink, "https://")) {
        $link = $rawlink;
        // Mozilla Browser URI Obfuscation Weakness 2004-06-14
        //   http://www.securityfocus.com/bid/10532/
        //   goodurl+"%2F%20%20%20."+badurl
        if (preg_match("/%2F(%20)+\./i", $rawlink)) {
            $rawlink = preg_replace("/%2F(%20)+\./i", "%2F.", $rawlink);
        }
    } else {
        $link  = UnWikiEscape($rawlink);
    }

    /* Relatives links by Joel Schaubert.
     * Recognize [../bla] or [/bla] as relative links, without needing http://
     */
   /* if (preg_match('/^(\.\.\/|\/)/', $link)) {
        return new Cached_ExternalLink($link, $label);
    }*/
    // [label|link]
    // if label looks like a url to an image, we want an image link.
    if (isImageLink($label)) {
        $imgurl = $label;
        $intermap = getInterwikiMap();
        if (preg_match("/^" . $intermap->getRegexp() . ":/", $label)) {
            $imgurl = $intermap->link($label);
            $imgurl = $imgurl->getAttr('href');
        } elseif (! preg_match("#^(" . ALLOWED_PROTOCOLS . "):#", $imgurl)) {
            // local theme linkname like 'images/next.gif'.
            global $WikiTheme;
            $imgurl = $WikiTheme->getImageURL($imgurl);
        }
        $label = LinkImage($imgurl, $link);
    }

    if ($hash) {
        // It's an anchor, not a link...
        $id = MangleXmlIdentifier($link);
        return HTML::a(
            array('name' => $id, 'id' => $id),
            $bar ? $label : $link
        );
    }

    if (preg_match("#^(" . ALLOWED_PROTOCOLS . "):#", $link)) {
        // if it's an image, embed it; otherwise, it's a regular link
        if (isImageLink($link)) {
            return LinkImage($link, $label);
        } else {
            return new Cached_ExternalLink($link, $label);
        }
    } elseif (preg_match("/^phpwiki:/", $link)) {
        return new Cached_PhpwikiURL($link, $label);
    } elseif (
        strstr($link, ':')
            and ($intermap = getInterwikiMap())
            and preg_match("/^" . $intermap->getRegexp() . ":/", $link)
    ) {
        /*
         * Inline images in Interwiki urls's:
         * [File:my_image.gif] inlines the image,
         * File:my_image.gif shows a plain inter-wiki link,
         * [what a pic|File:my_image.gif] shows a named inter-wiki link to the gif
         * [File:my_image.gif|what a pic] shows a inlimed image linked to the page "what a pic"
         *
         * Note that for simplicity we will accept embedded object tags (non-images)
         * here also, and seperate them later in LinkImage()
         */
        // trigger_error("label: $label link: $link", E_USER_WARNING);
        if (empty($label) and isImageLink($link)) {
            // if without label => inlined image [File:xx.gif]
            $imgurl = $intermap->link($link);
            return LinkImage($imgurl->getAttr('href'), $label);
        }
        return new Cached_InterwikiLink($link, $label);
    } else {
        // Split anchor off end of pagename.
        if (preg_match('/\A(.*)(?<!' . ESCAPE_CHAR . ')#(.*?)\Z/', $rawlink, $m)) {
            list(,$rawlink,$anchor) = $m;
            $pagename = UnWikiEscape($rawlink);
            $anchor = UnWikiEscape($anchor);
            if (!$label) {
                $label = $link;
            }
        } else {
            $pagename = $link;
            $anchor = false;
        }
        return new Cached_WikiLink($pagename, $label, $anchor);
    }
}

class Markup_bracketlink extends SimpleMarkup
{
    public $_match_regexp = "\\#? \\[ .*? [^]\\s] .*? \\]";

    public function markup($match)
    {
        $link = LinkBracketLink($match);
        assert($link->isInlineElement());
        return $link;
    }
}

class Markup_url extends SimpleMarkup
{
    public function getMatchRegexp()
    {
        return "(?<![[:alnum:]]) (?:" . ALLOWED_PROTOCOLS . ") : [^\s<>\"']+ (?<![ ,.?; \] \) ])";
    }

    public function markup($match)
    {
        return new Cached_ExternalLink(UnWikiEscape($match));
    }
}


class Markup_interwiki extends SimpleMarkup
{
    public function getMatchRegexp()
    {
        global $request;
        $map = getInterwikiMap();
        return "(?<! [[:alnum:]])" . $map->getRegexp() . ": \S+ (?<![ ,.?;! \] \) \" \' ])";
    }

    public function markup($match)
    {
        //$map = getInterwikiMap();
        return new Cached_InterwikiLink(UnWikiEscape($match));
    }
}

class Markup_wikiword extends SimpleMarkup
{
    public function getMatchRegexp()
    {
        global $WikiNameRegexp;
        if (!trim($WikiNameRegexp)) {
            return " " . WIKI_NAME_REGEXP;
        }
        return " $WikiNameRegexp";
    }

    public function markup($match)
    {
        if (!$match) {
            return false;
        }
        if ($this->_isWikiUserPage($match)) {
            return new Cached_UserLink($match); //$this->_UserLink($match);
        } else {
            return new Cached_WikiLink($match);
        }
    }

    // FIXME: there's probably a more useful place to put these two functions
    public function _isWikiUserPage($page)
    {
        global $request;
        $dbi = $request->getDbh();
        $page_handle = $dbi->getPage($page);
        if ($page_handle and $page_handle->get('pref')) {
            return true;
        } else {
            return false;
        }
    }

    public function _UserLink($PageName)
    {
        $link = HTML::a(array('href' => $PageName));
        $link->pushContent(PossiblyGlueIconToText('wikiuser', $PageName));
        $link->setAttr('class', 'wikiuser');
        return $link;
    }
}

class Markup_linebreak extends SimpleMarkup
{
    //var $_match_regexp = "(?: (?<! %) %%% (?! %) | <(?:br|BR)> | <(?:br|BR) \/> )";
    public $_match_regexp = "(?: (?<! %) %%% (?! %) | <(?:br|BR)> )";

    public function markup($match)
    {
        return HTML::br();
    }
}

class Markup_old_emphasis extends BalancedMarkup
{
    public $_start_regexp = "''|__";

    public function getEndRegexp($match)
    {
        return $match;
    }

    public function markup($match, $body)
    {
        $tag = $match == "''" ? 'em' : 'strong';
        return new HtmlElement($tag, $body);
    }
}

class Markup_nestled_emphasis extends BalancedMarkup
{
    public function getStartRegexp()
    {
        static $start_regexp = false;

        if (!$start_regexp) {
            // The three possible delimiters
            // (none of which can be followed by itself.)
            $i = "_ (?! _)";
            $b = "\\* (?! \\*)";
            $tt = "= (?! =)";

            $any = "(?: ${i}|${b}|${tt})"; // any of the three.

            // Any of [_*=] is okay if preceded by space or one of [-"'/:]
            $start[] = "(?<= \\s|^|[-\"'\\/:]) ${any}";

            // _ or * is okay after = as long as not immediately followed by =
            $start[] = "(?<= =) (?: ${i}|${b}) (?! =)";
            // etc...
            $start[] = "(?<= _) (?: ${b}|${tt}) (?! _)";
            $start[] = "(?<= \\*) (?: ${i}|${tt}) (?! \\*)";

            // any delimiter okay after an opening brace ( [{<(] )
            // as long as it's not immediately followed by the matching closing
            // brace.
            $start[] = "(?<= { ) ${any} (?! } )";
            $start[] = "(?<= < ) ${any} (?! > )";
            $start[] = "(?<= \\( ) ${any} (?! \\) )";

            $start = "(?:" . join('|', $start) . ")";

            // Any of the above must be immediately followed by non-whitespace.
            $start_regexp = $start . "(?= \S)";
        }

        return $start_regexp;
    }

    public function getEndRegexp($match)
    {
        $chr = preg_quote($match);
        return "(?<= \S | ^ ) (?<! $chr) $chr (?! $chr) (?= \s | [-)}>\"'\\/:.,;!? _*=] | $)";
    }

    public function markup($match, $body)
    {
        switch ($match) {
            case '*':
                return new HtmlElement('b', $body);
            case '=':
                return new HtmlElement('tt', $body);
            case '_':
                return new HtmlElement('i', $body);
        }
    }
}

class Markup_html_emphasis extends BalancedMarkup
{
    public $_start_regexp =
        "<(?: b|big|i|small|tt|em|strong|cite|code|dfn|kbd|samp|var|sup|sub )>";

    public function getEndRegexp($match)
    {
        return "<\\/" . substr($match, 1);
    }

    public function markup($match, $body)
    {
        $tag = substr($match, 1, -1);
        return new HtmlElement($tag, $body);
    }
}

class Markup_html_abbr extends BalancedMarkup
{
    //rurban: abbr|acronym need an optional title tag.
    //sf.net bug #728595
    public $_start_regexp = "<(?: abbr|acronym )(?: \stitle=[^>]*)?>";

    public function getEndRegexp($match)
    {
        if (substr($match, 1, 4) == 'abbr') {
            $tag = 'abbr';
        } else {
            $tag = 'acronym';
        }
        return "<\\/" . $tag . '>';
    }

    public function markup($match, $body)
    {
        if (substr($match, 1, 4) == 'abbr') {
            $tag = 'abbr';
        } else {
            $tag = 'acronym';
        }
        $rest = substr($match, 1 + strlen($tag), -1);
        if (!empty($rest)) {
            list($key,$val) = explode("=", $rest);
            $args = array($key => $val);
        } else {
            $args = array();
        }
        return new HtmlElement($tag, $args, $body);
    }
}

/** ENABLE_MARKUP_COLOR
 *  See http://www.pmwiki.org/wiki/PmWiki/WikiStyles and
 *      http://www.flexwiki.com/default.aspx/FlexWiki/FormattingRules.html
 */
class Markup_color extends BalancedMarkup
{
    // %color=blue% blue text %% and back to normal
    public $_start_regexp = "%color=(?: [^%]*)%";
    public $_end_regexp = "%%";

    public function markup($match, $body)
    {
        $color = substr($match, 7, -1);
        if (
            strlen($color) != 7
            and in_array($color, array('red', 'blue', 'grey', 'black'))
        ) {
            // must be a name
            return new HtmlElement('font', array('color' => $color), $body);
        } elseif (
            (substr($color, 0, 1) == '#')
                  and (strspn(substr($color, 1), '0123456789ABCDEFabcdef') == strlen($color) - 1)
        ) {
            return new HtmlElement('font', array('color' => $color), $body);
        } else {
            trigger_error(sprintf(_("unknown color %s ignored"), $color), E_USER_WARNING);
        }
    }
}

// Special version for single-line plugins formatting,
//  like: '<small>< ?plugin PopularNearby ? ></small>'
class Markup_plugin extends SimpleMarkup
{
    public $_match_regexp = '<\?plugin(?:-form)?\s[^\n]+?\?>';

    public function markup($match)
    {
    //$xml = new Cached_PluginInvocation($match);
    //$xml->setTightness(true,true);
        return new Cached_PluginInvocation($match);
    }
}

/** ENABLE_MARKUP_TEMPLATE
 *  Template syntax similar to mediawiki
 *  {{template}}
 * => <?plugin Template page=template?>
 *  {{template|var=value|...}}
 * => <?plugin Template page=template vars="var=value&..."?>
 */
class Markup_template_plugin extends SimpleMarkup
{
    public $_match_regexp = '\{\{\w[^\n]+\}\}';

    public function markup($match)
    {
        $page = substr($match, 2, -2);
        $vars = '';
        if (preg_match('/^(\S+)\|(.*)$/', $page, $_m)) {
            $page = $_m[1];
            $vars = str_replace('|', '&', $_m[2]);
        }
        if ($vars) {
            $s = '<?plugin Template page=' . $page . ' vars="' . $vars . '"?>';
        } else {
            $s = '<?plugin Template page=' . $page . '?>';
        }
        return new Cached_PluginInvocation($s);
    }
}

// TODO: "..." => "&#133;"  browser specific display (not cached?)
// TODO: "--" => "&emdash;" browser specific display (not cached?)
// TODO: Support more HTML::Entities: (C) for copy, --- for mdash, -- for ndash

class Markup_html_entities extends SimpleMarkup
{
    public $_match_regexp = '(: \.\.\.|\-\-|\-\-\-|\(C\) )';

    public function markup($match)
    {
        static $entities = array('...'  => '&#133;',
                                 '--'   => '&ndash;',
                                 '---'  => '&mdash;',
                                 '(C)'  => '&copy;',
                                 );
        return HTML::Raw($entities[$match]);
    }
}

class Markup_isonumchars extends SimpleMarkup
{
    public $_match_regexp = '\&\#\d{2,5};';

    public function markup($match)
    {
        return HTML::Raw($match);
    }
}

class Markup_isohexchars extends SimpleMarkup
{
    // hexnums, like &#x00A4; <=> &curren;
    public $_match_regexp = '\&\#x[0-9a-fA-F]{2,4};';

    public function markup($match)
    {
        return HTML::Raw($match);
    }
}

// FIXME: Do away with magic phpwiki forms.  (Maybe phpwiki: links too?)
// FIXME: Do away with plugin-links.  They seem not to be used.
//Plugin link

class InlineTransformer
{
    public $_regexps = array();
    public $_markup = array();

    public function __construct($markup_types = false)
    {
        if (!$markup_types) {
            $non_default = false;
            $markup_types = array('escape', 'bracketlink', 'url',
                                  'interwiki', 'wikiword', 'linebreak',
                                  'old_emphasis', 'nestled_emphasis',
                                  'html_emphasis', 'html_abbr', 'plugin',
                                  'isonumchars', 'isohexchars', /*'html_entities',*/
                                  );
        } else {
            $non_default = true;
        }
        foreach ($markup_types as $mtype) {
            $class = "Markup_$mtype";
            $this->_addMarkup(new $class());
        }
        if (ENABLE_MARKUP_COLOR and !$non_default) {
            $this->_addMarkup(new Markup_color());
        }
        if (ENABLE_MARKUP_TEMPLATE and !$non_default) {
            $this->_addMarkup(new Markup_template_plugin());
        }
    }

    public function _addMarkup($markup)
    {
        if (isa($markup, 'SimpleMarkup')) {
            $regexp = $markup->getMatchRegexp();
        } else {
            $regexp = $markup->getStartRegexp();
        }

        assert(!isset($this->_markup[$regexp]));
        $this->_regexps[] = $regexp;
        $this->_markup[] = $markup;
    }

    public function parse(&$text, $end_regexps = array('$'))
    {
        $regexps = $this->_regexps;

        // $end_re takes precedence: "favor reduce over shift"
        array_unshift($regexps, $end_regexps[0]);
        //array_push($regexps, $end_regexps[0]);
        $regexps = new RegexpSet($regexps);

        $input = $text;
        $output = new XmlContent();

        $match = $regexps->match($input);

        while ($match) {
            if ($match->regexp_ind == 0) {
                // No start pattern found before end pattern.
                // We're all done!
                if (isset($markup) and is_object($markup) and isa($markup, 'Markup_plugin')) {
                    $current = $output->_content[count($output->_content) - 1];
                    $current->setTightness(true, true);
                }
                $output->pushContent($match->prematch);
                $text = $match->postmatch;
                return $output;
            }

            $markup = $this->_markup[$match->regexp_ind - 1];
            $body = $this->_parse_markup_body($markup, $match->match, $match->postmatch, $end_regexps);
            if (!$body) {
                // Couldn't match balanced expression.
                // Ignore and look for next matching start regexp.
                $match = $regexps->nextMatch($input, $match);
                continue;
            }

            // Matched markup.  Eat input, push output.
            // FIXME: combine adjacent strings.
            if (isa($markup, 'SimpleMarkup')) {
                $current = $markup->markup($match->match);
            } else {
                $current = $markup->markup($match->match, $body);
            }
            $input = $match->postmatch;
            if (isset($markup) and is_object($markup) and isa($markup, 'Markup_plugin')) {
                $current->setTightness(true, true);
            }
            $output->pushContent($match->prematch, $current);

            $match = $regexps->match($input);
        }

        // No pattern matched, not even the end pattern.
        // Parse fails.
        return false;
    }

    public function _parse_markup_body($markup, $match, &$text, $end_regexps)
    {
        if (isa($markup, 'SimpleMarkup')) {
            return true;        // Done. SimpleMarkup is simple.
        }

        if (!is_object($markup)) {
            return false; // Some error: Should assert
        }
        array_unshift($end_regexps, $markup->getEndRegexp($match));

        // Optimization: if no end pattern in text, we know the
        // parse will fail.  This is an important optimization,
        // e.g. when text is "*lots *of *start *delims *with
        // *no *matching *end *delims".
        $ends_pat = "/(?:" . join(").*(?:", $end_regexps) . ")/xs";
        if (!preg_match($ends_pat, $text)) {
            return false;
        }
        return $this->parse($text, $end_regexps);
    }
}

class LinkTransformer extends InlineTransformer
{
    public function __construct()
    {
        parent::__construct(array('escape', 'bracketlink', 'url',
                                       'interwiki', 'wikiword'));
    }
}

function TransformInline($text, $markup = 2.0, $basepage = false)
{
    static $trfm;

    if (empty($trfm)) {
        $trfm = new InlineTransformer();
    }

    if ($markup < 2.0) {
        $text = ConvertOldMarkup($text, 'inline');
    }

    if ($basepage) {
        return new CacheableMarkup($trfm->parse($text), $basepage);
    }
    return $trfm->parse($text);
}

function TransformLinks($text, $markup = 2.0, $basepage = false)
{
    static $trfm;

    if (empty($trfm)) {
        $trfm = new LinkTransformer();
    }

    if ($markup < 2.0) {
        $text = ConvertOldMarkup($text, 'links');
    }

    if ($basepage) {
        return new CacheableMarkup($trfm->parse($text), $basepage);
    }
    return $trfm->parse($text);
}

// $Log: InlineParser.php,v $
// Revision 1.70  2005/10/31 16:45:23  rurban
// added cfg-able markups only for default TextTransformation, not for links and others
//
// Revision 1.69  2005/09/14 05:57:19  rurban
// make ENABLE_MARKUP_TEMPLATE optional
//
// Revision 1.68  2005/09/10 21:24:32  rurban
// optionally support {{Template|vars}} syntax
//
// Revision 1.67  2005/06/06 17:41:20  rurban
// support new ENABLE_MARKUP_COLOR
//
// Revision 1.66  2005/04/23 11:15:49  rurban
// handle allowed inlined objects within INLINE_IMAGES
//
// Revision 1.65  2005/03/27 18:24:17  rurban
// add Log
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
