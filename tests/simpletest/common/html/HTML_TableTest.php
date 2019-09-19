<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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

class HTML_TableTest extends TuleapTestCase
{

    /**
     * @var HTML_Table
     */
    protected $html_table;

    public function setUp()
    {
        parent::setUp();
        $this->html_table = new HTML_Table();
    }

    public function itBuildsATable()
    {
        $this->assertPattern('%<table>.*</table>%s', $this->html_table->render());
    }

    public function itBuildsTableWithTitles()
    {
        $this->assertPattern('%<th>Bla</th>%', $this->html_table->setColumnsTitle(array('Bla'))->render());
    }

    public function itBuildsTableWithBody()
    {
        $this->assertPattern('%<tbody>Bla</tbody>%', $this->html_table->setBody('Bla')->render());
    }

    public function itHasNoTableHeadIfNoTitles()
    {
        $this->assertNoPattern('%<thead>.*</thead>%', $this->html_table->render());
    }

    public function itHasColumnTitleWhenWeAddTitles()
    {
        $this->assertPattern('%<th>foo</th><th>bar</th>%', $this->html_table->addColumnTitle('foo')->addColumnTitle('bar')->render());
    }

    public function itHasNoTableBodyIfNoBoby()
    {
        $this->assertNoPattern('%<tbody>.*</tbody>%', $this->html_table->render());
    }

    public function itHasTableClasses()
    {
        $this->assertPattern('%<table class="bla">%', $this->html_table->addTableClass('bla')->render());
    }

    public function itHasAnId()
    {
        $this->assertPattern('%<table.*id="bla".*>%', $this->html_table->setId('bla')->render());
    }
}
