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
class HTML_Table_BoostrapTest extends TestCase
{

    /**
     * @var HTML_Table_Bootstrap
     */
    protected $html_table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->html_table = new HTML_Table_Bootstrap();
    }

    public function testItBuildsATable(): void
    {
        $this->assertMatchesRegularExpression('/<table class="table">\s*<\/table>/', $this->html_table->render());
    }

    public function testItHasTableClasses(): void
    {
        $this->assertMatchesRegularExpression('/<table class="table bla">/', $this->html_table->addTableClass('bla')->render());
    }
}
