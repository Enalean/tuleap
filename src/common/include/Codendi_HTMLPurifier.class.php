<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2007.
 * 
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Clean-up HTML code for user output.
 *
 * This class aims to purify the HTML code provided by a user for beeing
 * displayed saftly (remove XSS and make the HTML std compliant).
 * How to use it:
 * <pre>
 * require_once('pre.php');
 * require_once('common/include/Codendi_HTMLPurifier.class.php');
 * $crapy = '<a href="" onmouseover="alert(1);">testé</a>';
 * $hp =& Codendi_HTMLPurifier::instance();
 * $clean = $hp->purify($crapy);
 * </pre>
 */

define('CODENDI_PURIFIER_CONVERT_HTML', 0);
define('CODENDI_PURIFIER_STRIP_HTML', 1);
define('CODENDI_PURIFIER_BASIC',      5);
define('CODENDI_PURIFIER_BASIC_NOBR',      6);
define('CODENDI_PURIFIER_LIGHT',     10);
define('CODENDI_PURIFIER_FULL',      15);
define('CODENDI_PURIFIER_JS_QUOTE', 20);
define('CODENDI_PURIFIER_JS_DQUOTE', 25);
define('CODENDI_PURIFIER_DISABLED', 100);

class Codendi_HTMLPurifier {
    /**
     * Hold an instance of the class
     */
    private static $Codendi_HTMLPurifier_instance;
    
    /**
     * Constructor
     */
    private function __construct() {
    }

    /**
     * Singleton access.
     *
     * @access: static
     */
    public static function instance() {
        if (!isset(self::$Codendi_HTMLPurifier_instance)) {
            $c = __CLASS__;
            self::$Codendi_HTMLPurifier_instance = new $c;
        }
        return self::$Codendi_HTMLPurifier_instance;
    }

    /**
     * Base configuration of HTML Purifier for codendi.
     */
    protected function getCodendiConfig() {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core', 'Encoding', 'UTF-8');
        // $config->set('HTML', 'Doctype', 'XHTML 1.0 Strict');
        $config->set('Cache', 'SerializerPath', $GLOBALS['codendi_cache_dir']);
        return $config;
    }

    /**
     * Allow basic formatting markups.
     *
     */
    function getLightConfig() {
        $config = $this->getCodendiConfig();
        $config->set('HTML', 'Allowed', $this->getLightConfigMarkups());
        return $config;
    }
    
    /**
     * Get allowed markups for light config
     *
     * This function defines the markups allowed for a light
     * formatting. This includes markups for lists, for paragraphs, hypertext
     * links, and content-based text.
     * Allowed makups:
     * - 'p', 'br'
     * - 'a[href|title|class]'
     * - 'ul', 'ol', 'li'
     * - 'cite', 'code', 'blockquote', 'strong', 'em', 'pre', 'b', 'i'
     */
    function getLightConfigMarkups() {
        $eParagraph       = array('p', 'br');
        $eLinks           = array('a[href|title|class]', 'img[src|alt]');
        $eList            = array('ul', 'ol', 'li');
        $eContentBasedTxt = array('cite', 'code', 'blockquote', 'strong', 'em',
                                  'pre', 'b', 'i');
        $eCharacters     = array('&amp;' , '&gt;', '&lt;', '&quot;');
        
        $aa = array_merge($eParagraph, $eLinks, $eList, $eContentBasedTxt, $eCharacters);
        $allowed = implode(',', $aa);
        
        return $allowed;
    }

    /**
     *
     */
    function getStripConfig() {
        $config = $this->getCodendiConfig();
        $config->set('HTML', 'Allowed', '');
        return $config;
    }

    /**
     * HTML Purifier configuration factory
     */
    function getHPConfig($level) {
        $config = null;
        switch($level) {
        case CODENDI_PURIFIER_LIGHT:
            $config = $this->getLightConfig();
            break;

        case CODENDI_PURIFIER_FULL:
            $config = $this->getCodendiConfig();
            break;

        case CODENDI_PURIFIER_STRIP_HTML:
            $config = $this->getStripConfig();
            break;
        }
        return $config;
    }

    /**
     * Wrap call to util_make_links (for testing purpose).
     */
    function _makeLinks($str, $groupId) {
        return util_make_links($str, $groupId);
    }

    /**
     * Perform HTML purification depending of level purification required.
     *
     * There are 5 level of purification, from the most restrictive to most
     * permissive:
     * - CODENDI_PURIFIER_CONVERT_HTML (default)
     *   Transform HTML markups it in entities.
     *
     * - CODENDI_PURIFIER_STRIP_HTML
     *   Removes all HTML markups. Note: as we relly on HTML Purifier to
     *   perform this operation this option is not considered as secure as
     *   CONVERT_HTML. If you are looking for the most secure option please
     *   consider CONVERT_HTML.
     *
     * - CODENDI_PURIFIER_BASIC (need $groupId to be set for automagic links)
     *   Removes all user submitted HTML markups but: 
     *    - transform typed URLs into clickable URLs.
     *    - transform autmagic links.
     *    - transform carrige return into HTML br markup.
     *
     * - CODENDI_PURIFIER_LIGHT
     *   First set of HTML formatting (@see getLightConfig() for allowed
     *   markups) plus all what is allowed by CODENDI_PURIFIER_BASIC.
     *
     * - CODENDI_PURIFIER_FULL
     *   Clean-up plain HTML using HTML Purifier rules (remove forms,
     *   javascript, ...). Warning: there is no longer codendi facilities
     *   (neither automagic links nor carrige return to br transformation).
     *
     * - CODENDI_PURIFIER_DISABLED
     *   No filter at all.
     */
    function purify($html, $level=0, $groupId=0) {
        $clean = '';
        switch($level) {
        case CODENDI_PURIFIER_DISABLED:
            $clean = $html;
            break;

        case CODENDI_PURIFIER_LIGHT:
            if (empty($html)) {
                $clean = $html;
                break;
            }
            if ($groupId) {
                $referenceManager = $this->getReferenceManager();
                $referenceManager->insertReferences($html,$groupId);
            }
            $clean = nl2br($html);
            break;
        case CODENDI_PURIFIER_STRIP_HTML:
        case CODENDI_PURIFIER_FULL:
            require_once($GLOBALS['htmlpurifier_dir'].'/HTMLPurifier.auto.php');
            $hp =& HTMLPurifier::getInstance();
            $config = $this->getHPConfig($level);
            $clean = $hp->purify($html, $config);
            // Quite big object, it's better to unset it (memory).
            unset($config);
            break;

        case CODENDI_PURIFIER_BASIC:
            $clean = nl2br($this->_makeLinks(htmlentities($html, ENT_QUOTES, 'UTF-8'), $groupId));
            break;
        case CODENDI_PURIFIER_BASIC_NOBR:
            $clean = $this->_makeLinks(htmlentities($html, ENT_QUOTES, 'UTF-8'), $groupId);
            break;

        case CODENDI_PURIFIER_JS_QUOTE:
            $clean = preg_replace('/\<\/script\>/umsi', "</'+'script>", addslashes(preg_replace('/\\\n/ums', "
", $html)));
            break;
        case CODENDI_PURIFIER_JS_DQUOTE:
            $clean = preg_replace('/\<\/script\>/umsi', '</"+"script>', addslashes(preg_replace('/\\\n/ums', '
', $html)));
            break;
        case CODENDI_PURIFIER_CONVERT_HTML:
        default:
            $clean = htmlentities($html, ENT_QUOTES, 'UTF-8');
            break;
        }
        return $clean;
    }

    function purifyMap($array, $level=0, $groupId=0) {
        return array_map(array(&$this, "purify"), $array, array($level), array($groupId));
    }
    
    /**
     * Returns an instance of ReferenceManager
     *
     * @return ReferenceManager
     */
    public function getReferenceManager() {
        return ReferenceManager::instance();
    }

}

?>
