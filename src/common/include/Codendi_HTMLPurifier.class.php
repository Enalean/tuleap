<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
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

class Codendi_HTMLPurifier
{
    public const CONFIG_CONVERT_HTML                  = 0;
    public const CONFIG_STRIP_HTML                    = 1;
    public const CONFIG_BASIC                         = 5;
    public const CONFIG_BASIC_NOBR                    = 6;
    public const CONFIG_LIGHT                         = 10;
    public const CONFIG_FULL                          = 15;
    public const CONFIG_JS_QUOTE                      = 20;
    public const CONFIG_JS_DQUOTE                     = 25;
    public const CONFIG_MINIMAL_FORMATTING_NO_NEWLINE = 35;
    public const CONFIG_DISABLED                      = 100;

    private static $allowed_schemes = [
        'http'   => true,
        'https'  => true,
        'mailto' => true,
        'ftp'    => true,
        'nntp'   => true,
        'news'   => true,
        'tel'    => true,
    ];

    /**
     * Hold an instance of the class
     *
     * @var self|null
     */
    private static $Codendi_HTMLPurifier_instance;
    private $config = [];

    /**
     * Constructor
     */
    protected function __construct()
    {
    }

    /**
     * Singleton access.
     *
     * @return Codendi_HTMLPurifier
     */
    public static function instance()
    {
        if (! isset(self::$Codendi_HTMLPurifier_instance)) {
            self::$Codendi_HTMLPurifier_instance = new Codendi_HTMLPurifier();
        }
        return self::$Codendi_HTMLPurifier_instance;
    }

    private function setConfigAttribute(HTMLPurifier_Config $config, $key, $subkey, $value)
    {
        if (version_compare($config->version, '4.0.0') >= 0) {
            $config->set("$key.$subkey", $value);
        } else {
            $config->set($key, $subkey, $value);
        }
    }

    /**
     * Base configuration of HTML Purifier for codendi.
     */
    protected function getCodendiConfig()
    {
        $config = HTMLPurifier_Config::createDefault();
        $this->setConfigAttribute($config, 'Core', 'Encoding', 'UTF-8');
        if (posix_getpwuid(posix_geteuid())['name'] === ForgeConfig::getApplicationUserLogin()) {
            $this->setConfigAttribute($config, 'Cache', 'SerializerPath', ForgeConfig::getCacheDir());
        } else {
            $this->setConfigAttribute($config, 'Cache', 'DefinitionImpl', null);
        }
        $this->setConfigAttribute($config, 'Core', 'AllowHostnameUnderscore', true);
        $this->setConfigAttribute($config, 'URI', 'AllowedSchemes', self::$allowed_schemes);
        return $config;
    }

    private function getFullConfig(): HTMLPurifier_Config
    {
        $config = $this->getCodendiConfig();

        $config->set('HTML.DefinitionID', 'Tuleap custom definition');
        $config->set('HTML.DefinitionRev', 3);
        $definition = $config->maybeGetRawHTMLDefinition();
        if ($definition) {
            $definition->addElement('tlp-mermaid-diagram', 'Block', 'Optional: pre', 'Common', []);
            $definition->addElement('tlp-syntax-highlighting', 'Block', 'Optional: pre', 'Common', []);
        }

        return $config;
    }

    /**
     * Allow basic formatting markups and enable some Autoformat attributes
     * @see http://htmlpurifier.org/live/configdoc/plain.html#AutoFormat
     *
     */
    public function getLightConfig()
    {
        $config = $this->getCodendiConfig();
        $this->setConfigAttribute($config, 'HTML', 'Allowed', $this->getLightConfigMarkups());
        $this->setConfigAttribute($config, 'AutoFormat', 'Linkify', true);
        HTMLPurifier_URISchemeRegistry::instance()->register('ssh', new \Tuleap\HTMLPurifierSSHScheme());
        $allowed_schemes        = self::$allowed_schemes;
        $allowed_schemes['ssh'] = true;
        $this->setConfigAttribute($config, 'URI', 'AllowedSchemes', $allowed_schemes);
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
    public function getLightConfigMarkups()
    {
        $allowed = 'p,br,' .
                   'a[href|title|class],img[src|alt],' .
                   'ul,ol,li,' .
                   'cite,code,blockquote,strong,em,pre,b,i';
        return $allowed;
    }

    public function getStripConfig()
    {
        $config = $this->getCodendiConfig();
        $this->setConfigAttribute($config, 'HTML', 'Allowed', '');
        return $config;
    }

    private function getMinimalFormattingNoNewlineConfig(): HTMLPurifier_Config
    {
        $config = $this->getCodendiConfig();
        $this->setConfigAttribute($config, 'HTML', 'Allowed', 'a[href],strong,em,b,i');
        return $config;
    }

    /**
     * HTML Purifier configuration factory
     */
    public function getHPConfig($level)
    {
        if (isset($this->config[$level])) {
            return $this->config[$level];
        }
        switch ($level) {
            case self::CONFIG_LIGHT:
                $this->config[self::CONFIG_LIGHT] = $this->getLightConfig();
                break;

            case self::CONFIG_FULL:
                $this->config[self::CONFIG_FULL] = $this->getFullConfig();
                break;

            case self::CONFIG_STRIP_HTML:
                $this->config[self::CONFIG_STRIP_HTML] = $this->getStripConfig();
                break;
            case self::CONFIG_MINIMAL_FORMATTING_NO_NEWLINE:
                $this->config[self::CONFIG_MINIMAL_FORMATTING_NO_NEWLINE] = $this->getMinimalFormattingNoNewlineConfig();
                break;
        }
        return $this->config[$level];
    }

    /**
     * @return string
     */
    private function linkifyMails($data)
    {
        // john.doe@yahoo.com => <a href="mailto:...">...</a>
        $mailto_pattern = '
          (?<=\W|^)  # email must be at the beginning of the string or be preceded by a non word
          (?<!\/)    # … and not by a / to avoid ssh://gitolite@tuleap.net matching
          (
            ([a-z0-9_]|\-|\.)+@([^[:space:]<&>]*)([[:alnum:]-])   # really basic email pattern
          )';
        return preg_replace("`$mailto_pattern`ix", "<a href=\"mailto:\\1\">\\1</a>", $data);
    }

    /**
     * @return string
     */
    private function dealWithSpecialCasesWithFramingURLCharacters($data)
    {
        // Special case for urls between brackets or double quotes
        // e.g. <https://www.example.com> or "https://www.example.com"
        // In some places (e.g. tracker follow-ups) the text is already encoded, so the brackets are replaced by &lt; and &gt; See SR #652.
        $url_pattern = '([[:alnum:]]+)://([^[:space:]<]*)([[:alnum:]#?/&=])';
        $matching    = '\1://\2\3';

        $data = preg_replace("`$url_pattern&quot;`i", "$matching\"", $data);
        $data = preg_replace("`$url_pattern&#039;`i", "$matching'", $data);
        return preg_replace("`$url_pattern&gt;`i", "$matching>", $data);
    }

    /**
     * Perform HTML purification depending of level purification required.
     *
     * There are 5 level of purification, from the most restrictive to most
     * permissive:
     * - CONFIG_CONVERT_HTML (default)
     *   Transform HTML markups it in entities.
     *
     * - CONFIG_STRIP_HTML
     *   Removes all HTML markups. Note: as we rely on HTML Purifier to
     *   perform this operation this option is not considered as secure as
     *   CONVERT_HTML. If you are looking for the most secure option please
     *   consider CONVERT_HTML.
     *
     * - CONFIG_BASIC (need $groupId to be set for automagic links)
     *   Removes all user submitted HTML markups but:
     *    - transform typed URLs into clickable URLs.
     *    - transform automagic links.
     *    - transform carriage return into HTML br markup.
     *
     * - CONFIG_LIGHT
     *   First set of HTML formatting (@see getLightConfig() for allowed
     *   markups) plus all what is allowed by CONFIG_BASIC.
     *
     * - CONFIG_FULL
     *   Clean-up plain HTML using HTML Purifier rules (remove forms,
     *   javascript, ...). Warning: there is no longer Tuleap facilities
     *   (neither automagic links nor carriage return to br transformation).
     *
     * - CONFIG_DISABLED
     *   No filter at all.
     *
     *
     * @psalm-taint-specialize
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
    public function purify($html, $level = 0, $groupId = 0)
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }
        switch ($level) {
            case self::CONFIG_DISABLED:
                $clean = $html;
                break;

            case self::CONFIG_LIGHT:
            case self::CONFIG_STRIP_HTML:
            case self::CONFIG_FULL:
            case self::CONFIG_MINIMAL_FORMATTING_NO_NEWLINE:
                $hp = HTMLPurifier::getInstance();

                $config = $this->getHPConfig($level);
                $clean  = $hp->purify($html, $config);
                $this->insertReferences($clean, $groupId);
                break;

            case self::CONFIG_BASIC:
                $data  = $this->linkifyMails(htmlentities($html, ENT_QUOTES, 'UTF-8'));
                $data  = $this->dealWithSpecialCasesWithFramingURLCharacters($data);
                $clean = $this->purify(nl2br($data), CODENDI_PURIFIER_LIGHT, $groupId);
                break;
            case self::CONFIG_BASIC_NOBR:
                $data  = $this->linkifyMails(htmlentities($html, ENT_QUOTES, 'UTF-8'));
                $data  = $this->dealWithSpecialCasesWithFramingURLCharacters($data);
                $clean = $this->purify($data, CODENDI_PURIFIER_LIGHT, $groupId);
                break;

            case self::CONFIG_JS_QUOTE:
                $clean = $this->js_string_purifier($html, JSON_HEX_APOS);
                break;
            case self::CONFIG_JS_DQUOTE:
                $clean = $this->js_string_purifier($html, JSON_HEX_QUOT);
                break;
            case self::CONFIG_CONVERT_HTML:
            default:
                $clean = htmlentities($html, ENT_QUOTES, 'UTF-8');
                break;
        }
        return $clean;
    }

    /**
     * @return string
     */
    private function js_string_purifier($str, $options)
    {
        $clean_quoted = json_encode(strval($str), JSON_HEX_TAG | JSON_HEX_AMP | $options);
        $clean        = mb_substr($clean_quoted, 1, -1);
        return $clean;
    }

    /**
     * Purify HTML and insert references
     *
     * @param String  $html Content to filter
     * @param int $group_id
     *
     * @return String
     */
    public function purifyHTMLWithReferences($html, $group_id)
    {
        $this->insertReferences($html, $group_id);

        return $this->purify($html, CODENDI_PURIFIER_FULL);
    }

    /**
     * @return string
     */
    public function purifyTextWithReferences($html, $group_id)
    {
        return $this->purify($html, CODENDI_PURIFIER_BASIC, $group_id);
    }

    public function purifyMap($array, $level = 0, $groupId = 0)
    {
        return array_map([&$this, "purify"], $array, [$level], [$groupId]);
    }

    /**
     * Returns an instance of ReferenceManager
     *
     * @return ReferenceManager
     */
    public function getReferenceManager()
    {
        return ReferenceManager::instance();
    }

    private function insertReferences(&$html, $group_id = 0)
    {
        if (! $group_id) {
            return;
        }

        $reference_manager = $this->getReferenceManager();
        $reference_manager->insertReferences($html, $group_id);
    }
}
