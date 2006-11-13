<?php 
rcs_id('$Id$');
/* Copyright (C) 2002, Geoffrey T. Dairiki <dairiki@dairiki.org>
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

class CacheableMarkup extends XmlContent {

    function CacheableMarkup($content, $basepage) {
        $this->_basepage = $basepage;
	$this->_buf = '';
	$this->_content = array();
	$this->_append($content);
	if ($this->_buf != '')
	    $this->_content[] = $this->_buf;
	unset($this->_buf);
    }

    function pack() {
        if (function_exists('gzcompress'))
            return gzcompress(serialize($this), 9);
        return serialize($this);

        // FIXME: probably should implement some sort of "compression"
        //   when no gzcompress is available.
    }

    function unpack($packed) {
        if (!$packed)
            return false;

        if (function_exists('gzcompress')) {
            // ZLIB format has a five bit checksum in it's header.
            // Lets check for sanity.
            if ((ord($packed[0]) * 256 + ord($packed[1])) % 31 == 0) {
                // Looks like ZLIB.
                return unserialize(gzuncompress($packed));
            }
        }
        if (substr($packed,0,2) == "O:") {
            // Looks like a serialized object
            return unserialize($packed);
        }
        trigger_error("Can't unpack bad cached markup. Probably php_zlib extension not loaded.", E_USER_WARNING);
        return false;
    }
    
    /** Get names of wikipages linked to.
     *
     * @return array
     * A list of wiki page names (strings).
     */
    function getWikiPageLinks() {
        include_once('lib/WikiPlugin.php');
        $ploader = new WikiPluginLoader();
        
	$links = array();
	foreach ($this->_content as $item) {
	    if (!isa($item, 'Cached_DynamicContent'))
                continue;

            if (!($item_links = $item->getWikiPageLinks($this->_basepage)))
                continue;
            foreach ($item_links as $pagename)
                if (is_string($pagename) and $pagename != '')
                    $links[] = $pagename;
        }

	return array_unique($links);
    }

    /** Get link info.
     *
     * This is here to support the XML-RPC listLinks() method.
     *
     * @return array
     * Returns an array of hashes.
     */
    function getLinkInfo() {
	$link = array();
	foreach ($this->_content as $link) {
	    if (! isa($link, 'Cached_Link'))
		continue;
	    $info = $link->getLinkInfo($this->_basepage);
	    $links[$info->href] = $info;
	}
	return array_values($links);
    }

    function _append($item) {
	if (is_array($item)) {
	    foreach ($item as $subitem)
		$this->_append($subitem);
	}
	elseif (!is_object($item)) {
	    $this->_buf .= util_make_links($this->_quote((string) $item), $GLOBALS['group_id']);
	}
	elseif (isa($item, 'Cached_DynamicContent')) {
	    if ($this->_buf) {
		$this->_content[] = $this->_buf;
		$this->_buf = '';
	    }
	    $this->_content[] = $item;
	}
	elseif (isa($item, 'XmlElement')) {
	    if ($item->isEmpty()) {
		$this->_buf .= $item->emptyTag();
	    }
	    else {
		$this->_buf .= $item->startTag();
		foreach ($item->getContent() as $subitem)
		    $this->_append($subitem);
		$this->_buf .= "</$item->_tag>";

                if (!isset($this->_description) and $item->getTag() == 'p')
                    $this->_glean_description($item->asString());
	    }
	    if (!$item->isInlineElement())
		$this->_buf .= "\n";
	}
	elseif (isa($item, 'XmlContent')) {
	    foreach ($item->getContent() as $item)
		$this->_append($item);
	}
	elseif (method_exists($item, 'asxml')) {
	    $this->_buf .= $item->asXML();
	}
	elseif (method_exists($item, 'asstring')) {
	    $this->_buf .= $this->_quote($item->asString());
	}
	else {
	    $this->_buf .= sprintf("==Object(%s)==", get_class($item));
	}
    }

    function _glean_description($text) {
        static $two_sentences;
        if (!$two_sentences) {
            $two_sentences = pcre_fix_posix_classes("[.?!][\")]*\s+[\"(]*[[:upper:])]"
                                                    . ".*"
                                                    . "[.?!][\")]*\s*[\"(]*([[:upper:])]|$)");
        }
        
        if (!isset($this->_description) and preg_match("/$two_sentences/sx", $text))
            $this->_description = preg_replace("/\s*\n\s*/", " ", trim($text));
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
    function getDescription () {
        return isset($this->_description) ? $this->_description : '';
    }
    
    function asXML () {
	$xml = '';
        $basepage = $this->_basepage;
        
	foreach ($this->_content as $item) {
            if (is_string($item)) {
                $xml .= $item;
            }
            elseif (is_subclass_of($item, 'cached_dynamiccontent')) {
                $val = $item->expand($basepage, &$this);
                $xml .= $val->asXML();
            }
            else {
                $xml .= $item->asXML();
            }
	}
	return $xml;
    }

    function printXML () {
        $basepage = $this->_basepage;
        // _content might be changed from a plugin (CreateToc)
	for ($i=0; $i < count($this->_content); $i++) {
	    $item = $this->_content[$i];
            if (is_string($item)) {
                print $item;
            }
            elseif (is_subclass_of($item, 'cached_dynamiccontent')) {
            	// give the content the chance to know about itself or even 
            	// to change itself itself
                $val = $item->expand($basepage, &$this);
                $val->printXML();
            }
            else {
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
class Cached_DynamicContent {

    function cache(&$cache) {
	$cache[] = $this;
    }

    function expand($basepage, $obj) {
        trigger_error("Pure virtual", E_USER_ERROR);
    }

    function getWikiPageLinks($basepage) {
        return false;
    }
}

class XmlRpc_LinkInfo {
    function XmlRpc_LinkInfo($page, $type, $href) {
	$this->page = $page;
	$this->type = $type;
	$this->href = $href;
    }
}

class Cached_Link extends Cached_DynamicContent {

    function isInlineElement() {
	return true;
    }

    /** Get link info (for XML-RPC support)
     *
     * This is here to support the XML-RPC listLinks method.
     * (See http://www.ecyrd.com/JSPWiki/Wiki.jsp?page=WikiRPCInterface)
     */
    function getLinkInfo($basepage) {
	return new XmlRpc_LinkInfo($this->_getName($basepage),
                                   $this->_getType(),
                                   $this->_getURL($basepage));
    }
    
    function _getURL($basepage) {
	return $this->_url;
    }
}

class Cached_WikiLink extends Cached_Link {

    function Cached_WikiLink ($page, $label = false, $anchor = false) {
	$this->_page = $page;
        if ($anchor)
            $this->_anchor = $anchor;
        if ($label and $label != $page)
            $this->_label = $label;
    }

    function _getType() {
        return 'internal';
    }
    
    function getPagename($basepage) {
	$page = new WikiPageName($this->_page, $basepage);
	if ($page->isValid()) return $page->name;
	else return false;
    }

    function getWikiPageLinks($basepage) {
        if ($basepage == '') return false;
        if ($link = $this->getPagename($basepage)) return array($link);
        else return false;
    }

    function _getName($basepage) {
	return $this->getPagename($basepage);
    }

    function _getURL($basepage) {
	return WikiURL($this->getPagename($basepage), false, 'abs_url');
    }

    function expand($basepage, &$markup) {
	$label = isset($this->_label) ? $this->_label : false;
	$anchor = isset($this->_anchor) ? (string)$this->_anchor : '';
        $page = new WikiPageName($this->_page, $basepage, $anchor);
        if ($page->isValid()) return WikiLink($page, 'auto', $label);
	else return HTML($label);
    }

    function asXml() {
	$label = isset($this->_label) ? $this->_label : false;
	$anchor = isset($this->_anchor) ? (string)$this->_anchor : '';
        $page = new WikiPageName($this->_page, false, $anchor);
	$link = WikiLink($page, 'auto', $label);
        return $link->asXml();
    }

    function asString() {
        if (isset($this->_label))
            return $this->_label;
        return $this->_page;
    }
}

class Cached_WikiLinkIfKnown extends Cached_WikiLink
{
    function Cached_WikiLinkIfKnown ($moniker) {
	$this->_page = $moniker;
    }

    function expand($basepage, &$markup) {
        return WikiLink($this->_page, 'if_known');
    }
}    
    
class Cached_PhpwikiURL extends Cached_DynamicContent
{
    function Cached_PhpwikiURL ($url, $label) {
	$this->_url = $url;
        if ($label)
            $this->_label = $label;
    }

    function isInlineElement() {
	return true;
    }

    function expand($basepage, &$markup) {
        $label = isset($this->_label) ? $this->_label : false;
        return LinkPhpwikiURL($this->_url, $label, $basepage);
    }

    function asXml() {
        $label = isset($this->_label) ? $this->_label : false;
        $link = LinkPhpwikiURL($this->_url, $label);
        return $link->asXml();
    }

    function asString() {
        if (isset($this->_label))
            return $this->_label;
        return $this->_url;
    }
}    
    
class Cached_ExternalLink extends Cached_Link {

    function Cached_ExternalLink($url, $label=false) {
	$this->_url = $url;
        if ($label && $label != $url)
            $this->_label = $label;
    }

    function _getType() {
        return 'external';
    }
    
    function _getName($basepage) {
	$label = isset($this->_label) ? $this->_label : false;
	return ($label and is_string($label)) ? $label : $this->_url;
    }

    function expand($basepage, &$markup) {
	$label = isset($this->_label) ? $this->_label : false;
	return LinkURL($this->_url, $label);
    }

    function asString() {
        if (isset($this->_label))
            return $this->_label;
        return $this->_url;
    }
}

class Cached_InterwikiLink extends Cached_ExternalLink {
    
    function Cached_InterwikiLink($link, $label=false) {
	$this->_link = $link;
        if ($label)
            $this->_label = $label;
    }

    function _getName($basepage) {
	$label = isset($this->_label) ? $this->_label : false;
	return ($label and is_string($label)) ? $label : $link;
    }
    
    function _getURL($basepage) {
	$link = $this->expand($basepage, &$this);
	return $link->getAttr('href');
    }

    function expand($basepage, &$markup) {
        //include_once('lib/interwiki.php');
	$intermap = getInterwikiMap($GLOBALS['request']);
	$label = isset($this->_label) ? $this->_label : false;
	return $intermap->link($this->_link, $label);
    }

    function asString() {
        if (isset($this->_label))
            return $this->_label;
        return $this->_link;
    }
}

// Needed to put UserPages to backlinks. Special method to markup userpages with icons
// Thanks to PhpWiki:DanFr for finding this bug. 
// Fixed since 1.3.8, prev. versions had no userpages in backlinks
class Cached_UserLink extends Cached_WikiLink {
    function expand($basepage, &$markup) {
        $label = isset($this->_label) ? $this->_label : false;
	$anchor = isset($this->_anchor) ? (string)$this->_anchor : '';
        $page = new WikiPageName($this->_page, $basepage, $anchor);
	$link = WikiLink($page, 'auto', $label);
        // $link = HTML::a(array('href' => $PageName));
        $link->setContent(PossiblyGlueIconToText('wikiuser', $this->_page));
        $link->setAttr('class', 'wikiuser');
        return $link;
    }
}

class Cached_PluginInvocation extends Cached_DynamicContent {
    function Cached_PluginInvocation ($pi) {
	$this->_pi = $pi;
    }

    function setTightness($top, $bottom) {
        $this->_tightenable = 0;
        if ($top) $this->_tightenable |= 1;
        if ($bottom) $this->_tightenable |= 2;
    }
    
    function isInlineElement() {
	return false;
    }

    function expand($basepage, &$markup) {
        $loader = &$this->_getLoader();

        $xml = $loader->expandPI($this->_pi, $GLOBALS['request'], &$markup, $basepage);
        $div = HTML::div(array('class' => 'plugin'));
        
	if (isset($this->_tightenable)) {
	    if ($this->_tightenable == 3)
	        return HTML::span(array('class' => 'plugin'), $xml);
	    $div->setInClass('tightenable');
	    $div->setInClass('top', ($this->_tightenable & 1) != 0);
	    $div->setInClass('bottom', ($this->_tightenable & 2) != 0);
	}
	$div->pushContent($xml);
	return $div;
    }

    function asString() {
        return $this->_pi;
    }


    function getWikiPageLinks($basepage) {
        $loader = &$this->_getLoader();

        return $loader->getWikiPageLinks($this->_pi, $basepage);
    }

    function _getLoader() {
        static $loader = false;

	if (!$loader) {
            include_once('lib/WikiPlugin.php');
	    $loader = new WikiPluginLoader;
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
?>
