<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\BotMattermostAgileDashboard\SenderServices;

require_once dirname(__FILE__).'/../../bootstrap.php';

use TuleapTestCase;

class MarkdownFormatterTest extends TuleapTestCase
{
    private $markdown_formatter;

    public function setUp()
    {
        parent::setUp();
        $this->markdown_formatter = new MarkdownFormatter();
    }

    public function itVerifiesThatAddMarkdownTitleOfCorrectLevel()
    {
        $title = 'title';

        $result = $this->markdown_formatter->addTitleOfLevel($title, 5);
        $this->assertEqual($result, '##### title'.PHP_EOL);
        $result = $this->markdown_formatter->addTitleOfLevel($title, 4);
        $this->assertEqual($result, '#### title'.PHP_EOL);
        $result = $this->markdown_formatter->addTitleOfLevel($title, 3);
        $this->assertEqual($result, '### title'.PHP_EOL);
    }

    public function itVerifiesThatCreateSimpleMarkdownTable()
    {
        $infos  = array(
            'info name 1' => 'info value 1',
            'info name 2' => 'info value 2',
            'info name 3' => 'info value 3'
        );
        $result = $this->markdown_formatter->createSimpleTableText($infos);

        $this->assertEqual(
            $result,
            '| info name 1 | info name 2 | info name 3 |'.PHP_EOL.
            '| :--| :--| :--|'.PHP_EOL.
            '| info value 1 | info value 2 | info value 3 |'.PHP_EOL
        );
    }

    public function itVerifiesThatAddLineOfText()
    {
        $text   = 'text';
        $result = $this->markdown_formatter->addLineOfText($text);

        $this->assertEqual($result, 'text'.PHP_EOL);
    }

    public function itVerifiesThatCreateMarkdownTable()
    {
        $table_header = array('column name 1', 'column name 2', 'column name 3');
        $element1     = array('value 1 of element 1', 'value 2 of element 1', 'value 3 of element 1');
        $element2     = array('value 1 of element 2', 'value 2 of element 2', 'value 3 of element 2');
        $element3     = array('value 1 of element 3', 'value 2 of element 3', 'value 3 of element 3');
        $elements     = array($element1, $element2, $element3);

        $result = $this->markdown_formatter->createTableText($table_header, $elements);

        $this->assertEqual(
            $result,
            '| column name 1 | column name 2 | column name 3 |'.PHP_EOL.
            '| :--| :--| :--|'.PHP_EOL.
            '| value 1 of element 1 | value 2 of element 1 | value 3 of element 1 |'.PHP_EOL.
            '| value 1 of element 2 | value 2 of element 2 | value 3 of element 2 |'.PHP_EOL.
            '| value 1 of element 3 | value 2 of element 3 | value 3 of element 3 |'.PHP_EOL
        );
    }

    public function itVerifiesThatAddMarkdownSeparationLine()
    {
        $result = $this->markdown_formatter->addSeparationLine();

        $this->assertEqual($result, '***'.PHP_EOL);
    }

    public function itVerifiesThatCreateMarkdownLink()
    {
        $link_name = 'link name';
        $url       = 'https://my-url.com';

        $result = $this->markdown_formatter->createLink($link_name, $url);

        $this->assertEqual($result, '[link name](https://my-url.com)');
    }
}