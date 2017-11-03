<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Templating\Mustache;

class GettextHelper
{

    public function gettext($text)
    {
        $parts  = $this->splitTextInParts($text);
        $string = $this->shift($text, $parts);

        $translated_text = gettext($string);

        return $this->getFormattedText($translated_text, $parts);
    }

    public function ngettext($text, \Mustache_LambdaHelper $helper)
    {
        $parts  = $this->splitTextInParts($text);
        $msgid1 = $this->shift($text, $parts);
        $msgid2 = $this->shift($text, $parts);
        $n      = (int) $helper->render($this->shift($text, $parts));

        $translated_text = ngettext($msgid1, $msgid2, $n);

        return $this->getFormattedText($translated_text, $parts, array($n));
    }

    public function dgettext($text)
    {
        $parts  = $this->splitTextInParts($text);
        $domain = $this->shift($text, $parts);
        $string = $this->shift($text, $parts);

        $translated_text = dgettext($domain, $string);

        return $this->getFormattedText($translated_text, $parts);
    }

    public function dngettext($text, \Mustache_LambdaHelper $helper)
    {
        $parts  = $this->splitTextInParts($text);
        $domain = $this->shift($text, $parts);
        $msgid1 = $this->shift($text, $parts);
        $msgid2 = $this->shift($text, $parts);
        $n      = (int) $helper->render($this->shift($text, $parts));

        $translated_text = dngettext($domain, $msgid1, $msgid2, $n);

        return $this->getFormattedText($translated_text, $parts, array($n));
    }

    private function splitTextInParts($text)
    {
        return explode('|', $text);
    }

    private function shift($text, array &$parts)
    {
        if (! $parts) {
            throw new InvalidGettextStringException($text);
        }

        $string = trim(array_shift($parts));
        if ($string === '') {
            throw new InvalidGettextStringException($text);
        }

        return $string;
    }

    private function getVsprintfArgumentsFromRemainingParts($parts)
    {
        return array_map(
            function ($text) {
                return trim($text);
            },
            $parts
        );
    }

    private function getFormattedText($translated_text, $parts, $default_vsprintf_args = array())
    {
        $args = $default_vsprintf_args;
        if ($parts) {
            $args = $this->getVsprintfArgumentsFromRemainingParts($parts);
        }

        return vsprintf($translated_text, $args);
    }
}
