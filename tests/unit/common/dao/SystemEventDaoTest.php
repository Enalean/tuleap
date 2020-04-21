<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

declare(strict_types=1);

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class SystemEventDaoTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $da;

    private $search_term = 'abc';
    private $event_type  = array('MY_IMAGINARY_EVENT');
    private $status      = array('ONGOING');

    protected function setUp(): void
    {
        $this->da = \Mockery::mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);

        $this->da->shouldReceive('quoteSmartImplode')->with(', ', $this->event_type)->andReturns('MY_IMAGINARY_EVENT');
        $this->da->shouldReceive('quoteSmartImplode')->with(', ', $this->status)->andReturns('ONGOING');
    }

    public function testItCreatesCorrectQueryWithSearchTermInFirstPosition(): void
    {
        $dao = new SystemEventDao($this->da);

        $this->da->shouldReceive('quoteLikeValueSuffix')->with($this->search_term . SystemEvent::PARAMETER_SEPARATOR)->andReturns("'" . $this->search_term . "%'");
        $expected_sql = "SELECT  * FROM system_event
                WHERE type   IN (MY_IMAGINARY_EVENT)
                AND status IN (ONGOING)
                AND parameters LIKE 'abc%'";
        $this->da->shouldReceive('query')->with($expected_sql, [])->once()->andReturns(
            Mockery::spy(LegacyDataAccessResultInterface::class)
        );

        $dao->searchWithParam('head', $this->search_term, $this->event_type, $this->status);
    }

    public function testItCreatesCorrectQueryWithSearchTermInLastPosition(): void
    {
        $dao = new SystemEventDao($this->da);

        $this->da->shouldReceive('quoteLikeValuePrefix')->with(SystemEvent::PARAMETER_SEPARATOR . $this->search_term)->andReturns("'%" . $this->search_term . "'");
        $expected_sql = "SELECT  * FROM system_event
                WHERE type   IN (MY_IMAGINARY_EVENT)
                AND status IN (ONGOING)
                AND parameters LIKE '%abc'";
        $this->da->shouldReceive('query')->with($expected_sql, [])->once()->andReturns(
            Mockery::spy(LegacyDataAccessResultInterface::class)
        );

        $dao->searchWithParam('tail', $this->search_term, $this->event_type, $this->status);
    }

    public function testItCreatesCorrectQueryWithExactSearchTerm(): void
    {
        $dao = new SystemEventDao($this->da);

        $this->da->shouldReceive('quoteSmart')->with($this->search_term)->andReturns($this->search_term);
        $this->da->shouldReceive('escapeLikeValue')->with($this->search_term)->andReturns($this->search_term);
        $expected_sql = 'SELECT  * FROM system_event
                WHERE type   IN (MY_IMAGINARY_EVENT)
                AND status IN (ONGOING)
                AND parameters LIKE abc';
        $this->da->shouldReceive('query')->with($expected_sql, [])->once()->andReturns(
            Mockery::spy(LegacyDataAccessResultInterface::class)
        );

        $dao->searchWithParam('all', $this->search_term, $this->event_type, $this->status);
    }
}
