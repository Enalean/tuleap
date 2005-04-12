<?php // -*-php-*-
rcs_id('$Id$');
/**
  RichTablePlugin
  A PhpWiki plugin that allows insertion of tables using a richer syntax
  http://www.it.iitb.ac.in/~sameerds/phpwiki/index.php/RichTablePlugin
*/
/* 
  Copyright (C) 2003 Sameer D. Sahasrabuddhe
  
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

// error_reporting (E_ALL & ~E_NOTICE);

class WikiPlugin_RichTable
extends WikiPlugin
{
    function getName() {
        return _("RichTable");
    }

    function getDescription() {
      return _("Layout tables using a very rich markup style.");
    }

    function getDefaultArguments() {
        return array();
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function run($dbi, $argstr, &$request, $basepage) {
    	global $Theme;
        include_once("lib/BlockParser.php");

        $lines = preg_split('/\n/', $argstr);
        $table = HTML::table();
 
        if ($lines[0][0] == '*') {
            $line = substr(array_shift($lines),1);
            $attrs = $this->_parse_attr($line);
            foreach ($attrs as $key => $value) {
                if (in_array ($key, array("id", "class", "title", "style",
                                          "bgcolor", "frame", "rules", "border",
                                          "cellspacing", "cellpadding",
                                          "summary", "align", "width"))) {
                    $table->setAttr($key, $value);
                }
            }
        }
        
        foreach ($lines as $line){
            if (substr($line,0,1) == "-") {
                if (isset($row)) {
                    if (isset($cell)) {
                        if (isset($content)) {
                            $cell->pushContent(TransformText($content));
                            unset($content);
                        }
                        $row->pushContent($cell);
                        unset($cell);
                    }
                    $table->pushContent($row);
                }	
                $row = HTML::tr();
                $attrs = $this->_parse_attr(substr($line,1));
                foreach ($attrs as $key => $value) {
                    if (in_array ($key, array("id", "class", "title", "style",
                                              "bgcolor", "align", "valign"))) {
                        $row->setAttr($key, $value);
                    }
                }
                continue;
            }
            if (substr($line,0,1) == "|" and isset($row)) {
                if (isset($cell)) {
                    if (isset ($content)) {
                        $cell->pushContent(TransformText($content));
                        unset($content);
                    }
                    $row->pushContent($cell);
                }
                $cell = HTML::td();
                $line = substr($line, 1);
                if ($line[0] == "*" ) {
                    $attrs = $this->_parse_attr(substr($line,1));
                    foreach ($attrs as $key => $value) {
                        if (in_array ($key, array("id", "class", "title", "style",
                                                  "colspan", "rowspan", "width", "height",
                                                  "bgcolor", "align", "valign"))) {
                            $cell->setAttr($key, $value);
                        }
                    }
                    continue;
                } 
            } 
            if (isset($row) and isset($cell)) {
                $line = str_replace("?\>", "?>", $line);
                $line = str_replace("\~", "~", $line);
                if (empty($content)) $content = '';
                $content .= $line . "\n";
            }
        }
        if (isset($row)) {
            if (isset($cell)) {
                if (isset($content))
                    $cell->pushContent(TransformText($content));
                $row->pushContent($cell);
            }
            $table->pushContent($row);
        }
        return $table;
    }

    function _parse_attr($line) {
        $attr_chunks = preg_split("/\s*,\s*/", strtolower($line));
        $options = array();
        foreach ($attr_chunks as $attr_pair) {
            if (empty($attr_pair)) continue;
            $key_val = preg_split("/\s*=\s*/", $attr_pair);
            $options[trim($key_val[0])] = trim($key_val[1]);
        }
        return $options;
    }
}

// $Log$
// Revision 1.1  2005/04/12 13:33:33  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.4  2004/03/09 13:08:40  rurban
// fix undefined TransformText error: load BlockParser,
// get rid of warnings
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
