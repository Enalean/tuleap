<?php
// -*-php-*-
rcs_id('$Id: XmlParser.php,v 1.6 2004/11/03 16:34:11 rurban Exp $');
/**
 * Base XmlParser Class.
 * Requires the expat.so/.dll, usually enabled by default.
 * Used by HtmlParser and RssParser.
 *
 * @author: Reini Urban
 *
 * TODO: Convert more perl Html::Element style to our XmlElement style
 * Needed additions to XmlElement:
 *   Html::Element::parent() <=> XmlElement::parent
 *   Html::Element::attr()   <=> XmlElement::getAttr()
 *   Html::Element::tag      <=> XmlElement::_tag
 *   Html::Element::content_list() <=> ->getContent() ??? or ->_children[]
 *   all_external_attr_names() <=>
 *
 * Problems:
 * The HtmlParser object set by xml_parse() doesn't keep its parameters,
 * esp. $this->root is lost. So we have to this into a global.
 */

/*
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

/**
 * class XmlParser - Parse into a tree of XmlElement nodes.
 *
 * PHP Problems:
 *   inside the handlers no globals are transported, only class vars.
 *   when leaving the handler class all class vars are destroyed, so we
 *   have to copy the root to a global.
 *
 */
class XmlParser
{

    public $_parser;
    public $root;
    public $current;

    public function __construct($encoding = '')
    {
 //  "ISO-8859-1"
        if ($encoding) {
            $this->_parser = xml_parser_create($encoding);
        } else {
            $this->_parser = xml_parser_create();
        }
        xml_parser_set_option($this->_parser, XML_OPTION_TARGET_ENCODING, $GLOBALS['charset']);
        //xml_set_object($this->_parser, &$this);
        xml_set_element_handler(
            $this->_parser,
            array(&$this, 'tag_open'),
            array(&$this, 'tag_close' )
        );
        xml_set_character_data_handler(
            $this->_parser,
            array(&$this, 'cdata')
        );
        //xml_set_element_handler($this->_parser, "tag_open", "tag_close");
        //xml_set_character_data_handler($this->_parser, "cdata");

        // Hack: workaround php OO bug
        unset($GLOBALS['xml_parser_root']);
    }

    public function __destruct()
    {
        global $xml_parser_root, $xml_parser_current;

        if (!empty($this->_parser)) {
            xml_parser_free($this->_parser);
        }
        unset($this->_parser);

        if (isset($xml_parser_root)) {
            $xml_parser_root->_destruct();
            unset($xml_parser_root); // nested parsing forbidden!
        }
        unset($xml_parser_current);
    }

    public function tag_open($parser, $name, $attrs = '')
    {
        $this->_tag = strtolower($name);
        $node = new XmlElement($this->_tag);
        if (is_string($attrs) and !empty($attrs)) {
            // lowercase attr names
            foreach (preg_split('/ /D', $attrs) as $pair) {
                if (strstr($pair, "=")) {
                    list($key,$val) = preg_split('/=/D', $pair);
                    $key = strtolower(trim($key));
                    $val = str_replace(array('"',"'"), '', trim($val));
                    $node->_attr[$key] = $val;
                } else {
                    $key = str_replace(array('"',"'"), '', strtolower(trim($pair)));
                    $node->_attr[$key] = $key;
                }
            }
        } elseif (!empty($attrs) and is_array($attrs)) {
            foreach ($attrs as $key => $val) {
                $key = strtolower(trim($key));
                $val = str_replace(array('"',"'"), '', trim($val));
                $node->_attr[$key] = $val;
            }
        }
        if (!is_null($this->current)) {
            $this->current->_content[] = $node;    // copy or ref?
            $node->parent = $this->current;       // ref
        }
        $this->current = $node;              // ref
        if (empty($this->root)) {
            $this->root = $node;              // ref for === test below
            $GLOBALS['xml_parser_root'] = $this->root;  // copy
        }
    }

    public function tag_close($parser, $name, $attrs = '')
    {
        //$this->parent = $this->current;   // copy!
        //unset($this->current);
    }

    public function cdata($parser, $data)
    {
        if (isset($this->current)) {
            $this->current->_content[] = $data;
        } else {
            trigger_error(sprintf("unparsed content outside tags: %s", $data), E_USER_WARNING);
        }
        if ($this->current === $this->root) {   // workaround php OO bug: ref => copy
            $GLOBALS['xml_parser_root'] = $this->root; // copy!
            //$this->root = $this->current;       // copy?
        }
    }

    public function parse($content, $is_final = true)
    {
        xml_parse($this->_parser, $content, $is_final) or
            trigger_error(
                sprintf(
                    "XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($this->_parser)),
                    xml_get_current_line_number($this->_parser)
                ),
                E_USER_WARNING
            );
    }

    public function parse_url($file, $debug = false)
    {
        if (get_cfg_var('allow_url_fopen')) {
            if (!($fp = fopen("$file", "r"))) {
                trigger_error("Error parse url $file");
                return;
            }
            while ($data = fread($fp, 4096)) {
                $this->parse($data, feof($fp));
            }
            fclose($fp);
        } else {
            // other url_fopen workarounds: curl, socket (http 80 only)
            $data = url_get_contents($file);
            if (empty($data)) {
                trigger_error("Error parse url $file");
                return;
            }
            $this->parse($data);
        }
    }
}

// $Log: XmlParser.php,v $
// Revision 1.6  2004/11/03 16:34:11  rurban
// proper msg if rss connection is broken or no items found
//
// Revision 1.5  2004/06/20 14:42:54  rurban
// various php5 fixes (still broken at blockparser)
//
// Revision 1.4  2004/06/08 21:03:20  rurban
// updated RssParser for XmlParser quirks (store parser object params in globals)
//
// Revision 1.3  2004/06/03 18:06:29  rurban
// fix file locking issues (only needed on write)
// fixed immediate LANG and THEME in-session updates if not stored in prefs
// advanced editpage toolbars (search & replace broken)
//
// Revision 1.2  2004/06/01 15:28:00  rurban
// AdminUser only ADMIN_USER not member of Administrators
// some RateIt improvements by dfrankow
// edit_toolbar buttons
//
// Revision 1.1  2004/05/24 17:31:31  rurban
// new XmlParser and HtmlParser, RssParser based on that.
//
//
// 2004-04-09 16:30:50 rurban:
//  added fsockopen allow_url_fopen = Off workaround

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
