<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2007.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 */

require_once($GLOBALS['htmlpurifier_dir'].'/HTMLPurifier.auto.php');

/**
 * Clean-up HTML code for user output.
 *
 * This class aims to purify the HTML code provided by a user for beeing
 * displayed saftly (remove XSS and make the HTML std compliant).
 * How to use it:
 * <pre>
 * require_once('pre.php');
 * require_once('common/include/CodeX_HTMLPurifier.class.php');
 * $crapy = '<a href="" onmouseover="alert(1);>testé</a>';
 * $hp =& CodeX_HTMLPurifier::getInstance();
 * $clean = $hp->purify($crapy);
 * </pre>
 */
class CodeX_HTMLPurifier 
extends HTMLPurifier {

    function CodeX_HTMLPurifier() {
        $config = $this->getCodeXConfig();
        parent::HTMLPurifier($config);
    }

    /**
     * @access: static
     */
    function getCodeXConfig() {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core', 'Encoding', 'ISO-8859-1');
        $config->set('HTML', 'Doctype', 'HTML 4.01 Transitional');
        //$config->set('Cache', 'SerializerPath', $GLOBALS['codex_cache_dir'].'/htmlpurifier');
        return $config;
    }

    /**
     * @access: static
     */
    function &getInstance($prototype = null) {
        $purifier = null;
        if($prototype === null) {
            $config = CodeX_HtmlPurifier::getCodeXConfig();
            $purifier =& parent::getInstance($config);
        } else {
            $purifier =& parent::getInstance($prototype);
        }
        return $purifier;
    }

}

?>
