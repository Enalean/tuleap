<?php
/* Copyright (C) 2002 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * Copyright (C) 2004, 2005 $ThePhpWikiProgrammingTeam
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

class CacheableMarkup extends XmlContent
{

    public function __construct($content, $basepage)
    {
        $this->_basepage = $basepage;
        $this->_buf = '';
        $this->_content = array();
        $this->_append($content);
        if ($this->_buf != '') {
            $this->_content[] = $this->_buf;
        }
        unset($this->_buf);
    }

    public function pack()
    {
        if (function_exists('gzcompress')) {
            return gzcompress(serialize($this), 9);
        }
        return serialize($this);

        // FIXME: probably should implement some sort of "compression"
        //   when no gzcompress is available.
    }

    public function unpack($packed)
    {
        if (!$packed) {
            return false;
        }

        // ZLIB format has a five bit checksum in it's header.
        // Lets check for sanity.
        if (
            ((ord($packed[0]) * 256 + ord($packed[1])) % 31 == 0)
             and (substr($packed, 0, 2) == "\037\213")
                  or (substr($packed, 0, 2) == "x\332")
        ) {   // 120, 218
            if (function_exists('gzuncompress')) {
                // Looks like ZLIB.
                $data = gzuncompress($packed);
                return unserialize($data);
            } else {
                // user our php lib. TESTME
                include_once("ziplib.php");
                $zip = new ZipReader($packed);
                list(,$data,$attrib) = $zip->readFile();
                return unserialize($data);
            }
        }
        if (substr($packed, 0, 2) == "O:") {
            // Looks like a serialized object
            return unserialize($packed);
        }
        if (preg_match("/^\w+$/", $packed)) {
            return $packed;
        }
        // happened with _BackendInfo problem also.
        trigger_error(
            "Can't unpack bad cached markup. Probably php_zlib extension not loaded.",
            E_USER_WARNING
        );
        return false;
    }

    /** Get names of wikipages linked to.
     *
     * @return array
     * A list of wiki page names (strings).
     */
    public function getWikiPageLinks()
    {
        include_once('lib/WikiPlugin.php');
        $ploader = new WikiPluginLoader();

        $links = array();
        foreach ($this->_content as $item) {
            if (!isa($item, 'Cached_DynamicContent')) {
                continue;
            }

            if (!($item_links = $item->getWikiPageLinks($this->_basepage))) {
                continue;
            }
            foreach ($item_links as $pagename) {
                if (is_string($pagename) and $pagename != '') {
                    $links[] = $pagename;
                }
            }
        }

        return array_unique($links);
    }

    public function _append($item)
    {
        if (is_array($item)) {
            foreach ($item as $subitem) {
                $this->_append($subitem);
            }
        } elseif (!is_object($item)) {
            $purifier    = Codendi_HTMLPurifier::instance();
            $this->_buf .= $purifier->purify((string) $item, CODENDI_PURIFIER_BASIC_NOBR, GROUP_ID);
        } elseif (isa($item, 'Cached_DynamicContent')) {
            if ($this->_buf) {
                $this->_content[] = $this->_buf;
                $this->_buf = '';
            }
            $this->_content[] = $item;
        } elseif (isa($item, 'XmlElement')) {
            if ($item->isEmpty()) {
                $this->_buf .= $item->emptyTag();
            } else {
                $this->_buf .= $item->startTag();
                foreach ($item->getContent() as $subitem) {
                    $this->_append($subitem);
                }
                $this->_buf .= "</$item->_tag>";

                if (!isset($this->_description) and $item->getTag() == 'p') {
                    $this->_glean_description($item->asString());
                }
            }
            if (!$item->isInlineElement()) {
                $this->_buf .= "\n";
            }
        } elseif (isa($item, 'XmlContent')) {
            foreach ($item->getContent() as $item) {
                $this->_append($item);
            }
        } elseif (method_exists($item, 'asXML')) {
            $this->_buf .= $item->asXML();
        } elseif (method_exists($item, 'asString')) {
            $this->_buf .= $this->_quote($item->asString());
        } else {
            $this->_buf .= sprintf("==Object(%s)==", get_class($item));
        }
    }

    public function _glean_description($text)
    {
        static $two_sentences;
        if (!$two_sentences) {
            $two_sentences = "[.?!][\")]*\s+[\"(]*[[:upper:])]"
                             . ".*"
                             . "[.?!][\")]*\s*[\"(]*([[:upper:])]|$)";
        }

        if (!isset($this->_description) and preg_match("/$two_sentences/sx", $text)) {
            $this->_description = preg_replace("/\s*\n\s*/", " ", trim($text));
        }
    }

    /**
     * Guess a short description of the page.
     *
     * Algorithm:
     *
     * This algorithm was suggested on MeatballWiki by
     * Alex Schroeder <kensanata@yahoo.com>.
     *
     * Use the first paragraph in the page which contains at least two
     * sentences.
     *
     * @see http://www.usemod.com/cgi-bin/mb.pl?MeatballWikiSuggestions
     *
     * @return string
     */
    public function getDescription()
    {
        return isset($this->_description) ? $this->_description : '';
    }

    public function asXML()
    {
        $xml = '';
        $basepage = $this->_basepage;

        foreach ($this->_content as $item) {
            if (is_string($item)) {
                $xml .= $item;
            } elseif ($item instanceof \Cached_DynamicContent) {
                $val = $item->expand($basepage, $this);
                $xml .= $val->asXML();
            } else {
                $xml .= $item->asXML();
            }
        }
        return $xml;
    }

    public function printXML()
    {
        $basepage = $this->_basepage;
        // _content might be changed from a plugin (CreateToc)
        for ($i = 0; $i < count($this->_content); $i++) {
            $item = $this->_content[$i];
            if (is_string($item)) {
                print $item;
            } elseif ($item instanceof \Cached_DynamicContent) {      // give the content the chance to know about itself or even
                // to change itself
                $val = $item->expand($basepage, $this);
                $val->printXML();
            } else {
                $item->printXML();
            }
        }
    }
}

/**
 * The base class for all dynamic content.
 *
 * Dynamic content is anything that can change even when the original
 * wiki-text from which it was parsed is unchanged.
 */
class Cached_DynamicContent
{

    public function cache(&$cache)
    {
        $cache[] = $this;
    }

    public function expand($basepage, &$obj)
    {
        trigger_error("Pure virtual", E_USER_ERROR);
    }

    public function getWikiPageLinks($basepage)
    {
        return false;
    }
}

class Cached_Link extends Cached_DynamicContent
{

    public function isInlineElement()
    {
        return true;
    }

    public function _getURL($basepage)
    {
        return $this->_url;
    }
}

class Cached_WikiLink extends Cached_Link
{

    public function __construct($page, $label = false, $anchor = false)
    {
        $this->_page = $page;
        if ($anchor) {
            $this->_anchor = $anchor;
        }
        if ($label and $label != $page) {
            $this->_label = $label;
        }
    }

    public function _getType()
    {
        return 'internal';
    }

    public function getPagename($basepage)
    {
        $page = new WikiPageName($this->_page, $basepage);
        if ($page->isValid()) {
            return $page->name;
        } else {
            return false;
        }
    }

    public function getWikiPageLinks($basepage)
    {
        if ($basepage == '') {
            return false;
        }
        if ($link = $this->getPagename($basepage)) {
            return array($link);
        } else {
            return false;
        }
    }

    public function _getName($basepage)
    {
        return $this->getPagename($basepage);
    }

    public function _getURL($basepage)
    {
        return WikiURL($this->getPagename($basepage));
    //return WikiURL($this->getPagename($basepage), false, 'abs_url');
    }

    public function expand($basepage, &$markup)
    {
        $label = isset($this->_label) ? $this->_label : false;
        $anchor = isset($this->_anchor) ? (string) $this->_anchor : '';
        $page = new WikiPageName($this->_page, $basepage, $anchor);
        if ($page->isValid()) {
            return WikiLink($page, 'auto', $label);
        } else {
            return HTML($label);
        }
    }

    public function asXml()
    {
        $label = isset($this->_label) ? $this->_label : false;
        $anchor = isset($this->_anchor) ? (string) $this->_anchor : '';
        $page = new WikiPageName($this->_page, false, $anchor);
        $link = WikiLink($page, 'auto', $label);
        return $link->asXml();
    }

    public function asString()
    {
        if (isset($this->_label)) {
            return $this->_label;
        }
        return $this->_page;
    }
}

class Cached_WikiLinkIfKnown extends Cached_WikiLink
{
    public function __construct($moniker)
    {
        $this->_page = $moniker;
    }

    public function expand($basepage, &$markup)
    {
        return WikiLink($this->_page, 'if_known');
    }
}

class Cached_PhpwikiURL extends Cached_DynamicContent
{
    public function __construct($url, $label)
    {
        $this->_url = $url;
        if ($label) {
            $this->_label = $label;
        }
    }

    public function isInlineElement()
    {
        return true;
    }

    public function expand($basepage, &$markup)
    {
        $label = isset($this->_label) ? $this->_label : false;
        return LinkPhpwikiURL($this->_url, $label, $basepage);
    }

    public function asXml()
    {
        $label = isset($this->_label) ? $this->_label : false;
        $link = LinkPhpwikiURL($this->_url, $label);
        return $link->asXml();
    }

    public function asString()
    {
        if (isset($this->_label)) {
            return $this->_label;
        }
        return $this->_url;
    }
}

class Cached_ExternalLink extends Cached_Link
{

    public function __construct($url, $label = false)
    {
        $this->_url = $url;
        if ($label && $label != $url) {
            $this->_label = $label;
        }
    }

    public function _getType()
    {
        return 'external';
    }

    public function _getName($basepage)
    {
        $label = isset($this->_label) ? $this->_label : false;
        return ($label and is_string($label)) ? $label : $this->_url;
    }

    public function expand($basepage, &$markup)
    {
        global $request;

        $label = isset($this->_label) ? $this->_label : false;
        $link = LinkURL($this->_url, $label);

        if (GOOGLE_LINKS_NOFOLLOW) {
            // Ignores nofollow when the user who saved the page was authenticated.
            $page = $request->getPage($basepage);
            $current = $page->getCurrentRevision();
            if (!$current->get('author_id')) {
                $link->setAttr('rel', 'nofollow');
            }
        }
        return $link;
    }

    public function asString()
    {
        if (isset($this->_label)) {
            return $this->_label;
        }
        return $this->_url;
    }
}

class Cached_InterwikiLink extends Cached_ExternalLink
{

    public function __construct($link, $label = false)
    {
        $this->_link = $link;
        if ($label) {
            $this->_label = $label;
        }
    }

    public function _getName($basepage)
    {
        $label = isset($this->_label) ? $this->_label : false;
        return ($label and is_string($label)) ? $label : $link;
    }

    public function _getURL($basepage)
    {
        $link = $this->expand($basepage, $this);
        return $link->getAttr('href');
    }

    public function expand($basepage, &$markup)
    {
        $intermap = getInterwikiMap();
        $label = isset($this->_label) ? $this->_label : false;
        return $intermap->link($this->_link, $label);
    }

    public function asString()
    {
        if (isset($this->_label)) {
            return $this->_label;
        }
        return $this->_link;
    }
}

// Needed to put UserPages to backlinks. Special method to markup userpages with icons
// Thanks to PhpWiki:DanFr for finding this bug.
// Fixed since 1.3.8, prev. versions had no userpages in backlinks
class Cached_UserLink extends Cached_WikiLink
{
    public function expand($basepage, &$markup)
    {
        $label = isset($this->_label) ? $this->_label : false;
        $anchor = isset($this->_anchor) ? (string) $this->_anchor : '';
        $page = new WikiPageName($this->_page, $basepage, $anchor);
        $link = WikiLink($page, 'auto', $label);
        // $link = HTML::a(array('href' => $PageName));
        $link->setContent(PossiblyGlueIconToText('wikiuser', $this->_page));
        $link->setAttr('class', 'wikiuser');
        return $link;
    }
}

class Cached_PluginInvocation extends Cached_DynamicContent
{

    public function __construct($pi)
    {
        $this->_pi = $pi;
    }

    public function setTightness($top, $bottom)
    {
        $this->_tightenable = 0;
        if ($top) {
            $this->_tightenable |= 1;
        }
        if ($bottom) {
            $this->_tightenable |= 2;
        }
    }

    public function isInlineElement()
    {
        return false;
    }

    public function expand($basepage, &$markup)
    {
        $loader = $this->_getLoader();

        $xml = $loader->expandPI($this->_pi, $GLOBALS['request'], $markup, $basepage);
        $div = HTML::div(array('class' => 'plugin'));
        if (is_array($plugin_cmdline = $loader->parsePI($this->_pi)) and $plugin_cmdline[1]) {
            $id = GenerateId($plugin_cmdline[1]->getName() . 'Plugin');
        }

        if (isset($this->_tightenable)) {
            if ($this->_tightenable == 3) {
                $span = HTML::span(array('class' => 'plugin'), $xml);
                if (!empty($id)) {
                    $span->setAttr('id', $id);
                }
                return $span;
            }
            $div->setInClass('tightenable');
            $div->setInClass('top', ($this->_tightenable & 1) != 0);
            $div->setInClass('bottom', ($this->_tightenable & 2) != 0);
        }
        if (!empty($id)) {
            $div->setAttr('id', $id);
        }
        $div->pushContent($xml);
        return $div;
    }

    public function asString()
    {
        return $this->_pi;
    }


    public function getWikiPageLinks($basepage)
    {
        $loader = $this->_getLoader();

        return $loader->getWikiPageLinks($this->_pi, $basepage);
    }

    public function & _getLoader()
    {
        static $loader = false;

        if (!$loader) {
            include_once('lib/WikiPlugin.php');
            $loader = new WikiPluginLoader();
        }
        return $loader;
    }
}

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
