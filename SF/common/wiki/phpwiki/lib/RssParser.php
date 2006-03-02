<?php // -*-php-*-
rcs_id('$Id$');
/**
 * RSSParser Class, requires the expat extension
 * Based on Duncan Gough RSSParser class
 * Copyleft Arnaud Fontaine
 * Licence : GPL

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
 * 2004-04-09 16:30:50 rurban: 
 *   added fsockopen allow_url_fopen = Off workaround
 * 2004-04-12 20:04:12 rurban: 
 *   fixes for IMAGE element (sf.net)
 */
class RSSParser {

    var $title = "";
    var $link  = "";
    var $description = "";
    var $inside_item = false;
    var $item  = array();
    var $items = array();
    var $channel = array();
    var $divers = "";
    var $date = "";

    function startElement($parser, $name, $attrs=''){
        global $current_tag;

        $current_tag = $name;
        if ($name == "ITEM")
            $this->inside_item = true;
        elseif ($name == "IMAGE")
            $this->inside_item = true;
    } // startElement

    function endElement($parser, $tagName, $attrs=''){
        global $current_tag;

        if ($tagName == "ITEM") {
            $this->items[] = array("title"       => $this->item['TITLE'],
                                   "description" => $this->item['DESCRIPTION'],
                                   "link"        => $this->item['LINK']);
            $this->item['TITLE']       = "";
            $this->item['DESCRIPTION'] = "";
            $this->item['LINK']        = "";
            $this->inside_item = false;
        } elseif ($tagName == "IMAGE") {
            $this->item['TITLE']       = "";
            $this->item['DESCRIPTION'] = "";
            $this->item['LINK']        = "";
            $this->inside_item = false;
        } elseif ($tagName == "CHANNEL") {
            $this->channel = array("title" => $this->title,
                                   "description" => $this->description,
                                   "link" => $this->link,
                                   "date" => $this->date,
                                   "divers" => $this->divers);
            $this->title       = "";
            $this->description = "";
            $this->link        = "";
            $this->divers      = "";
            $this->date        = "";
        }
    }

    function characterData($parser, $data){
        global $current_tag;

        if ($this->inside_item) {
            if (empty($this->item[$current_tag]))
                $this->item[$current_tag] = '';
            if ($current_tag == 'LINK') {
            	if (trim($data))
            	    $this->item[$current_tag] = trim($data);
            } else {
                $this->item[$current_tag] .= trim($data);
            }
        } else {
            switch ($current_tag) {
            case "TITLE":
                if (trim($data))
                    $this->title .= " " . trim($data);
                break;
            case "DESCRIPTION":
                if (trim($data))
                    $this->description .= trim($data);
                break;
            case "LINK":
                if (trim($data))
                    $this->link = trim($data);
                break;
            case "DC:DATE":
                if (trim($data))
                    $this->date .= " " . trim($data);
            default:
                if (trim($data))
                    $this->divers .= " " . $current_tag."/".$data;
                break;
            }
        }
    } // characterData

    function parse_results($xml_parser, $rss_parser, $file, $debug=false)   {
        xml_set_object($xml_parser, &$rss_parser);
        xml_set_element_handler($xml_parser, "startElement", "endElement");
        xml_set_character_data_handler($xml_parser, "characterData");

        if (ini_get('allow_url_fopen')) {
            $fp = fopen("$file","r") or die("Error reading XML file, $file");
            while ($data = fread($fp, 4096))  {
                xml_parse($xml_parser, $data, feof($fp)) or 
                    trigger_error(sprintf("XML error: %s at line %d", 
                                          xml_error_string(xml_get_error_code($xml_parser)), 
                                          xml_get_current_line_number($xml_parser)),
                                  E_USER_WARNING);
            }
            fclose($fp);
        } else {
            // other url_fopen workarounds: curl, socket (http 80 only)
            require_once("lib/HttpClient.php");
            $bits = parse_url($file);
            $host = $bits['host'];
            $port = isset($bits['port']) ? $bits['port'] : 80;
            $path = isset($bits['path']) ? $bits['path'] : '/';
            if (isset($bits['query'])) {
                $path .= '?'.$bits['query'];
            }
            $client = new HttpClient($host, $port);
            $client->use_gzip = false;
            if ($debug) $client->debug = true;
            if (!$client->get($path)) {
                $data = false;
            } else {
                $data = $client->getContent();
            }
            xml_parse($xml_parser, $data, true) or 
                trigger_error(sprintf("XML error: %s at line %d", 
                                      xml_error_string(xml_get_error_code($xml_parser)), 
                                      xml_get_current_line_number($xml_parser)),
                              E_USER_WARNING);
        }
        xml_parser_free($xml_parser);
    }
}

// $Log$
// Revision 1.5  2004/04/26 20:44:34  rurban
// locking table specific for better databases
//
// Revision 1.4  2004/04/18 01:11:51  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>