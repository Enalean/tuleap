<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once('Mail/mimeDecode.php');

class ForumML_mimeDecode extends Mail_mimeDecode {

    /**
     * Redfined here just to avoid breakage on isStatic test (first line).
     * It's just a copy/paste of parent's method
     */
    function decode($params = null)
    {
        // determine if this method has been called statically
        $isStatic = !(isset($this) && get_class($this) == __CLASS__);

        // Have we been called statically?
	// If so, create an object and pass details to that.
        if ($isStatic AND isset($params['input'])) {

            $obj = new Mail_mimeDecode($params['input']);
            $structure = $obj->decode($params);

        // Called statically but no input
        } elseif ($isStatic) {
            return PEAR::raiseError('Called statically and no input given');

        // Called via an object
        } else {
            $this->_include_bodies = isset($params['include_bodies']) ?
	                             $params['include_bodies'] : false;
            $this->_decode_bodies  = isset($params['decode_bodies']) ?
	                             $params['decode_bodies']  : false;
            $this->_decode_headers = isset($params['decode_headers']) ?
	                             $params['decode_headers'] : false;

            $structure = $this->_decode($this->_header, $this->_body);
            if ($structure === false) {
                $structure = $this->raiseError($this->_error);
            }
        }

        return $structure;
    }

    /**
     * Redefined to convert headers to utf8 automatically. Same method than
     * parent except code between // +++ Codendi: UTF8
     */
    function _decodeHeader($input)
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);

        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {

            $encoded  = $matches[1];
            $charset  = $matches[2];
            $encoding = $matches[3];
            $text     = $matches[4];

            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }

            // +++ Codendi: UTF8
            if (function_exists('mb_convert_encoding')) {
                $text = mb_convert_encoding($text, 'UTF-8', $charset);
            }
            // --- Codendi: UTF8

            $input = str_replace($encoded, $text, $input);
        }

        return $input;
    }
}

?>
