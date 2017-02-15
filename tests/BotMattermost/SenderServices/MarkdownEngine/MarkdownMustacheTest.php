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

require_once dirname(__FILE__).'/../../../bootstrap.php';

use TuleapTestCase;

class MarkdownMustacheTest extends TuleapTestCase
{

    private $markdown_mustache;
    private $renderer;

    public function setUp()
    {
        parent::setUp();
        $this->markdown_mustache = new MarkdownMustache();
        $this->renderer          = new MarkdownMustacheRenderer(dirname(__FILE__).'/templates');
    }

    public function itVerifiesThatMarkdownEngineConvertSpecialCharactersIntoHtmlEntities()
    {
        $text   = 'my|text{~}\*+-._$>`';
        $result = $this->renderer->renderToString(
            'simple-text',
            array('text' => $text)
        );

        $this->assertEqual(
            $result,
            'my&#124;text&#123;&#126;&#125;&#92;&#42;&#43;&#45;&#46;&#95;&#36;&#62;&#96;'
        );
    }
}
