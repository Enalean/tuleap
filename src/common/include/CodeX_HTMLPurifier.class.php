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

define('CODEX_HP_CONVERT_HTML', 0);
define('CODEX_HP_STRIP_HTML', 1);
define('CODEX_HP_BASIC',      5);
define('CODEX_HP_LIGHT',     10);
define('CODEX_HP_FULL',      15);
define('CODEX_HP_DISABLED', 100);

class CodeX_HTMLPurifier {
    var $hpInstance;

    /**
     * Constructor
     */
    function CodeX_HTMLPurifier() {
        $this->hpInstance = null;
    }

    /**
     * Singleton access.
     *
     * @access: static
     */
    function &getInstance() {
        static $purifier;
        if(!$purifier) {
            $purifier = new CodeX_HtmlPurifier();
        }
        return $purifier;
    }

    /**
     * Base configuration of HTML Purifier for codex.
     */
    function getCodeXConfig() {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core', 'Encoding', 'ISO-8859-1');
        // $config->set('HTML', 'Doctype', 'XHTML 1.0 Strict');
        $config->set('Cache', 'SerializerPath', $GLOBALS['codex_cache_dir']);
        return $config;
    }

    /**
     * Allow basic formatting markups.
     *
     * This function defines the markups allowed for a light
     * formatting. This includes markups for lists, for paragraphs, hypertext
     * links, and content-based text.
     * Allowed makups:
     * - 'p', 'br'
     * - 'a[href]'
     * - 'ul', 'ol', 'li'
     * - 'cite', 'code', 'blockquote', 'strong', 'em', 'pre'
     */
    function getLightConfig() {
        $config = $this->getCodeXConfig();
        
        $eParagraph       = array('p', 'br');
        $eLinks           = array('a[href]');
        $eList            = array('ul', 'ol', 'li');
        $eContentBasedTxt = array('cite', 'code', 'blockquote', 'strong', 'em',
                                  'pre');
        
        $aa = array_merge($eParagraph, $eLinks, $eList, $eContentBasedTxt);
        $allowed = implode(',', $aa);

        $config->set('HTML', 'Allowed', $allowed);
        return $config;
    }

    /**
     *
     */
    function getStripConfig() {
        $config = $this->getCodeXConfig();
        $config->set('HTML', 'Allowed', '');
        return $config;
    }

    /**
     * HTML Purifier configuration factory
     */
    function getHPConfig($level) {
        $config = null;
        switch($level) {
        case CODEX_HP_LIGHT:
            $config = $this->getLightConfig();
            break;

        case CODEX_HP_FULL:
            $config = $this->getCodeXConfig();
            break;

        case CODEX_HP_STRIP_HTML:
            $config = $this->getStripConfig();
            break;
        }
        return $config;
    }

    /**
     * Perform HTML purification depending of level purification required.
     *
     * There are 5 level of purification, from the most restrictive to most
     * permissive:
     * - CODEX_HP_CONVERT_HTML (default)
     *   Transform HTML markups it in entities.
     *
     * - CODEX_HP_STRIP_HTML
     *   Removes all HTML markups. Note: as we relly on HTML Purifier to
     *   perform this operation this option is not considered as secure as
     *   CONVERT_HTML. If you are looking for the most secure option please
     *   consider CONVERT_HTML.
     *
     * - CODEX_HP_BASIC (need $groupId to be set for automagic links)
     *   Removes all user submitted HTML markups but: 
     *    - transform typed URLs into clickable URLs.
     *    - transform autmagic links.
     *    - transform carrige return into HTML br markup.
     *
     * - CODEX_HP_LIGHT
     *   First set of HTML formatting (@see getLightConfig() for allowed
     *   markups) plus all what is allowed by CODEX_HP_BASIC.
     *
     * - CODEX_HP_FULL
     *   Clean-up plain HTML using HTML Purifier rules (remove forms,
     *   javascript, ...). Warning: there is no longer codex facilities
     *   (neither automagic links nor carrige return to br transformation).
     *
     * - CODEX_HP_DISABLED
     *   No filter at all.
     */
    function purify($html, $level=0, $groupId=0) {
        $clean = '';
        switch($level) {
        case CODEX_HP_DISABLED:
            $clean = $html;
            break;

        case CODEX_HP_LIGHT:
            $html = nl2br(util_make_links($html, $groupId));
        case CODEX_HP_STRIP_HTML:
        case CODEX_HP_FULL:
            $hp =& HTMLPurifier::getInstance();
            $config = $this->getHPConfig($level);
            $clean = $hp->purify($html, $config);
            // Quite big object, it's better to unset it (memory).
            unset($config);
            break;

        case CODEX_HP_BASIC:
            $clean = nl2br(util_make_links(htmlentities($html), $groupId));
            break;

        case CODEX_HP_CONVERT_HTML:
        default:
            $clean = htmlentities($html);
            break;
        }
        return $clean;
    }

}

?>
