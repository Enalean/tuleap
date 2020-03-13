<?php
/**
 * MIME.php, provides functions for determining MIME types and getting info about MIME types
 * Copyright (C) 2003 Arend van Beelen, Auton Rijnsburg. arend@auton.nl
 *
 * Updated for Codendi by Nicolas Terray 2008
 *
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
class MIME
{

    /**
     * Hold an instance of the class
     */
    private static $_instance;

    /**
     * A private constructor; prevents direct creation of object
     */
    private function __construct()
    {
        $this->XDG_DATA_DIRS = explode(':', (isset($_ENV['XDG_DATA_DIRS']) ? $_ENV['XDG_DATA_DIRS'] : '/usr/local/share/:/usr/share/'));
    }

    /**
     * Singleton
     * @return MIME
     */
    public static function instance()
    {
        if (!isset(self::$_instance)) {
            $c = self::class;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    /**
     * tries to determine the mimetype of the given file
     * if the second variable is false, the file won't be opened and magic checking will be skipped
     */
    public function type($filename, $openfile = true)
    {
        $mimetype = '';
        $matchlen = 0;

        $basename = basename($filename);

        // load the glob files if they haven't been loaded already
        if (!isset($this->globFileLines)) {
            $this->globFileLines = array();

            // go through the data dirs to search for the globbing files
            foreach ($this->XDG_DATA_DIRS as $dir) {
                // read the file
                if (file_exists("$dir/mime/globs") && ($lines = file("$dir/mime/globs")) !== false) {
                    $this->globFileLines = array_merge($this->globFileLines, $lines);
                }
            }
        }

        // check the globs twice (both case sensitive and insensitive)
        for ($i = 0; $i < 2; $i++) {
            // walk through the file line by line
            foreach ($this->globFileLines as $line) {
                // check whether the line is a comment
                if ($line[0] == '#') {
                    continue;
                }

                // strip the newline character, but leave any spaces
                $line = substr($line, 0, strlen($line) - 1);

                list($mime, $glob) = explode(':', $line, 2);

                // check for a possible direct match
                if ($basename == $glob) {
                    return $mime;
                }

                // match the globs
                $flag = ($i > 0 ? FNM_CASEFOLD : 0);
                if (fnmatch($glob, $basename, $flag) == true && strlen($glob) > $matchlen) {
                    $mimetype = $mime;
                    $matchlen = strlen($glob);
                }
            }
        }

        // check for hits
        if ($mimetype != '') {
            return $mimetype;
        }

        // if globbing didn't return any results we're going to do some magic
        // quit now if we may not or cannot open the file
        if ($openfile == false || ($fp = fopen($filename, 'r')) == false) {
            return '';
        }

        // load the magic files if they weren't loaded yet
        if (!isset($this->magicRules)) {
            $this->magicRules = array();

            // go through the data dirs to search for the magic files
            foreach (array_reverse($this->XDG_DATA_DIRS) as $dir) {
                // read the file
                if (!file_exists("$dir/mime/magic") || ($buffer = file_get_contents("$dir/mime/magic")) === false) {
                    continue;
                }

                // check the file type
                if (substr($buffer, 0, 12) != "MIME-Magic\0\n") {
                    continue;
                }

                $buffer = substr($buffer, 12);

                // go through the entire file
                while ($buffer != '') {
                    if ($buffer[0] != '[' && $buffer[0] != '>' && ($buffer[0] < '0' || $buffer[0] > '9')) {
                        break;
                    }

                    switch ($buffer[0]) {
                        // create an entry for a new mimetype
                        case '[':
                            $mime = substr($buffer, 1, strpos($buffer, ']') - 1);
                            $this->magicRules[$mime] = array();
                            $parents[0] =& $this->magicRules[$mime];
                            $buffer = substr($buffer, strlen($mime) + 3);
                            break;

                        // add a new rule to the current mimetype
                        case '>':
                        default:
                            $indent = ($buffer[0] == '>' ? 0 : intval($buffer));
                            $buffer = substr($buffer, strpos($buffer, '>') + 1);
                            $parents[$indent][] = new MIME_MagicRule;
                            $rulenum = sizeof($parents[$indent]) - 1;
                            $parents[$indent][$rulenum]->start_offset = intval($buffer);
                            $buffer = substr($buffer, strpos($buffer, '=') + 1);
                            $value_length = 256 * ord($buffer[0]) + ord($buffer[1]);
                            $buffer = substr($buffer, 2);
                            $parents[$indent][$rulenum]->value = substr($buffer, 0, $value_length);
                            $buffer = substr($buffer, $value_length);
                            $parents[$indent][$rulenum]->mask = ($buffer[0] != '&' ? str_repeat("\xff", $value_length) : substr($buffer, 1, $value_length));
                            if ($buffer[0] == '&') {
                                $buffer = substr($buffer, $value_length + 1);
                            }
                            $parents[$indent][$rulenum]->word_size = ($buffer[0] != '~' ? 1 : intval(substr($buffer, 1)));
                            while ($buffer[0] != '+' && $buffer[0] != "\n" && $buffer != '') {
                                $buffer = substr($buffer, 1);
                            }
                            $parents[$indent][$rulenum]->range_length = ($buffer[0] != '+' ? 1 : intval($buffer));
                            $buffer = substr($buffer, strpos($buffer, "\n") + 1);
                            $parents[$indent][$rulenum]->children = array();
                            $parents[$indent + 1] =& $parents[$indent][$rulenum]->children;
                            break;
                    }
                }
            }

            // sort the array so items with high priority will get on top
            ksort($this->magicRules);
            $magicRules = array_reverse($this->magicRules);
            reset($this->magicRules);
        }

        // call the recursive function for all mime types
        foreach ($this->magicRules as $mime => $rules) {
            foreach ($rules as $rule) {
                if ($this->applyRecursiveMagic($rule, $fp) == true) {
                    list($priority, $mimetype) = explode(':', $mime, 2);
                    fclose($fp);
                    return $mimetype;
                }
            }
        }

        // nothing worked, I will now only determine whether the file is binary or text
        fseek($fp, 0);
        $length = (filesize($filename) > 50 ? 50 : filesize($filename));
        $data = fread($fp, $length);
        fclose($fp);
        for ($i = 0; $i < $length; $i++) {
            if (!isset($data[$i]) || ($data[$i] < "\x20" && $data[$i] != "\x09" && $data[$i] != "\x0a" && $data[$i] != "\x0d")) {
                return 'application/octet-stream';
            }
        }
        return 'text/plain';
    }

    /**
     * apply the magic rules recursivily -- helper function for type()
     */
    private function applyRecursiveMagic(MIME_MagicRule $rule, $fp)
    {
        fseek($fp, $rule->start_offset);
        $data = fread($fp, strlen($rule->value) + $rule->range_length);
        if (strstr($data, $rule->value) !== false) {
            if (sizeof($rule->children) == 0) {
                return true;
            } else {
                foreach ($rule->children as $child) {
                    if ($this->applyRecursiveMagic($child, $fp) == true) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * gets the textual description of the mimetype, optionally in the specified language
     */
    public function description($mimetype, $language = 'en')
    {
        $this->description = '';
        $this->lang = $language;
        $this->read = false;

        // go through the data dirs to search for the XML file for the specified mime type
        foreach ($this->XDG_DATA_DIRS as $dir) {
            $filename = "$dir/mime/$mimetype.xml";

            // open the XML file
            if (!file_exists($filename) || ($fp = fopen($filename, 'r')) == false) {
                continue;
            }

            // initialize XML parser
            $xml_parser = xml_parser_create();
            xml_set_element_handler(
                $xml_parser,
                function ($parser, $name, $attrs) {
                    $this->read = false;
                    if ($name == 'COMMENT') {
                        if (!isset($attrs['XML:LANG']) || $attrs['XML:LANG'] == $this->lang) {
                            $this->read = true;
                        }
                    }
                },
                function ($parser, $name) {
                    $this->read = false;
                }
            );
            xml_set_character_data_handler(
                $xml_parser,
                function ($parser, $data) {
                    /** @psalm-suppress TypeDoesNotContainType */
                    if ($this->read == true) {
                        $this->description = $data;
                    }
                }
            );

            // read the file and parse
            while ($data = str_replace("\n", "", fread($fp, 4096))) {
                if (!xml_parse($xml_parser, $data, feof($fp))) {
                    error_log("ERROR: Couldn't parse $filename: " .
                              xml_error_string(xml_get_error_code($xml_parser)));
                    break;
                }
            }
            fclose($fp);
        }

        return $this->description;
    }

    private $XDG_DATA_DIRS;
    private $globFileLines;
    private $magicRules;
    private $description;
    private $lang;
    private $read;
}

/**
 * helper class for MIME::type()
 */
class MIME_MagicRule
{
    public $start_offset;
    public $value;
    public $mask;
    public $word_size;
    public $range_length;
    public $children;
}
