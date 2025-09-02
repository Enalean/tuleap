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

namespace Tuleap\BotMattermost\SenderServices\MarkdownEngine;

use MustacheRenderer;
use Override;
use Tuleap\Templating\Mustache\MustacheEngine;
use Tuleap\Templating\TemplateCacheInterface;

class MarkdownMustacheRenderer extends MustacheRenderer
{
    private static $markdown_special_characters = [
        '|'  => '&#124;',
        '\\' => '&#92;',
        '*'  => '&#42;',
        '_'  => '&#95;',
        '{'  => '&#123;',
        '}'  => '&#125;',
        '+'  => '&#43;',
        '-'  => '&#45;',
        '.'  => '&#46;',
        '`'  => '&#96;',
        '>'  => '&#62;',
        '$'  => '&#36;',
        '~'  => '&#126;',
        '['  => '&#91;',
        ']'  => '&#93;',
        '('  => '&#40;',
        ')'  => '&#41;',
        '!'  => '&#33;',
    ];


    #[Override]
    protected function getEngine(\Mustache_Loader $loader, TemplateCacheInterface $template_cache): MustacheEngine
    {
        $special_characters = self::$markdown_special_characters;

        return new MustacheEngine(
            $loader,
            $template_cache,
            function ($value) use ($special_characters) {
                return strtr($value, $special_characters);
            }
        );
    }
}
