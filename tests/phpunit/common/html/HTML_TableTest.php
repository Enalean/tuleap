<?php
/**
 * Copyright Enalean (c) 2011-present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class HTML_TableTest extends TestCase
{

    /**
     * @var HTML_Table
     */
    protected $html_table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->html_table = new HTML_Table();
    }

    public function testItBuildsATable(): void
    {
        $this->assertMatchesRegularExpression('/<table>\s*<\/table>/', $this->html_table->render());
    }

    public function testItBuildsTableWithTitles(): void
    {
        $this->assertMatchesRegularExpression('/<th>Bla<\/th>/', $this->html_table->setColumnsTitle(array('Bla'))->render());
    }

    public function testItBuildsTableWithBody(): void
    {
        $this->assertMatchesRegularExpression('/\s*<tbody>Bla<\/tbody>\s*/', $this->html_table->setBody('Bla')->render());
    }

    public function testItHasNoTableHeadIfNoTitles(): void
    {
        $this->assertDoesNotMatchRegularExpression('/<thead>\s*<\/thead>/', $this->html_table->render());
    }

    public function testItHasColumnTitleWhenWeAddTitles(): void
    {
        $this->assertMatchesRegularExpression('/<th>foo<\/th><th>bar<\/th>/', $this->html_table->addColumnTitle('foo')->addColumnTitle('bar')->render());
    }

    public function testItHasNoTableBodyIfNoBoby(): void
    {
        $this->assertDoesNotMatchRegularExpression('/<tbody>\s*<\/tbody>/', $this->html_table->render());
    }

    public function testItHasTableClasses(): void
    {
        $this->assertMatchesRegularExpression('/<table class="bla">/', $this->html_table->addTableClass('bla')->render());
    }

    public function testItHasAnId(): void
    {
        $this->assertMatchesRegularExpression('/<table.*id="bla".*>/', $this->html_table->setId('bla')->render());
    }
}
