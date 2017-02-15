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

namespace Tuleap\BotMattermost\SenderServices\MarkdownEngine;

use Mustache;

class MarkdownMustache extends Mustache
{
    private $markdown_special_characters = array(
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
        '~'  => '&#126;'
    );

    protected function _renderEscaped($tag_name, $leading, $trailing)
    {
        $rendered = strtr($this->_renderUnescaped($tag_name, '', ''), $this->markdown_special_characters);

        return $leading.$rendered.$trailing;
    }
}