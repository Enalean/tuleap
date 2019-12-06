<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
    public const GETTEXT   = 'gettext';
    public const NGETTEXT  = 'ngettext';
    public const DGETTEXT  = 'dgettext';
    public const DNGETTEXT = 'dngettext';

    private static $gettext_cache = [];

    /**
     * @var GettextSectionContentTransformer
     */
    private $transformer;

    public function __construct(GettextSectionContentTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function gettext($text)
    {
        $translated_formatted_text = $this->getGettextValueFromCache($text);
        if ($translated_formatted_text === null) {
            $parts                     = $this->splitTextInParts($text);
            $string                    = $this->shift($text, $parts);

            $translated_text           = gettext($string);
            $translated_formatted_text = $this->getFormattedText($translated_text, $parts);
            $this->cacheGettextValue($text, $translated_formatted_text);
        }
        return $translated_formatted_text;
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
        $translated_formatted_text = $this->getGettextValueFromCache($text);
        if ($translated_formatted_text === null) {
            $parts  = $this->splitTextInParts($text);
            $domain = $this->shift($text, $parts);
            $string = $this->shift($text, $parts);

            $translated_text           = dgettext($domain, $string);
            $translated_formatted_text = $this->getFormattedText($translated_text, $parts);
            $this->cacheGettextValue($text, $translated_formatted_text);
        }

        return $translated_formatted_text;
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
        return $this->transformer->splitTextInParts($text);
    }

    private function shift($text, array &$parts)
    {
        return $this->transformer->shift($text, $parts);
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

    private function getGettextValueFromCache(string $text): ?string
    {
        return self::$gettext_cache[setlocale(LC_MESSAGES, '0')][$text] ?? null;
    }

    private function cacheGettextValue(string $text, string $value): void
    {
        self::$gettext_cache[setlocale(LC_MESSAGES, '0')][$text] = $value;
    }
}
