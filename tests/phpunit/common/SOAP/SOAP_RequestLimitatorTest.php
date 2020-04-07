<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class SOAP_RequestLimitatorTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private function givenThereWasAlreadyOneCallTheLastHour()
    {
        $dao                 = \Mockery::spy(\SOAP_RequestLimitatorDao::class);
        $request_time        = $_SERVER['REQUEST_TIME'];
        $time_30_minutes_ago = $request_time - 30 * 60;
        $dar                 = TestHelper::arrayToDar(array('method_name' => 'addProject', 'date' => $time_30_minutes_ago));
        // Ensure we search into the db stuff ~1 hour agos
        $dao->shouldReceive('searchFirstCallToMethod')->with(
            'addProject',
            Mockery::on(
                static function (int $time) use ($request_time): bool {
                    $one_hour_ago = $request_time - 3600;
                    $delta        = abs($time - $one_hour_ago);

                    return $delta <= 10;
                }
            )
        )->andReturns($dar);
        $dao->shouldReceive('foundRows')->once()->andReturns(1);

        // Ensure the saved value is ~ the current time (more or less 10 sec)
        $dao->shouldReceive('saveCallToMethod')->with(
            'addProject',
            Mockery::on(
                static function (int $time) use ($request_time): bool {
                    return ($time >= $request_time - 10) && ($time <= $request_time + 10);
                }
            )
        )->once();

        return $dao;
    }

    public function testTwoRequestsShouldBeAllowedByConfiguration(): void
    {
        $dao = $this->givenThereWasAlreadyOneCallTheLastHour();
        $limitator = new SOAP_RequestLimitator($nb_call = 10, $timeframe = 3600, $dao);
        $limitator->logCallTo('addProject');
    }

    private function givenThereIsNoPreviousCallStoredInDB()
    {
        $dao = \Mockery::spy(\SOAP_RequestLimitatorDao::class);

        $dar = TestHelper::emptyDar();
        $dao->shouldReceive('searchFirstCallToMethod')->andReturns($dar);

        $dao->shouldReceive('saveCallToMethod')->with('addProject', Mockery::any())->once();

        return $dao;
    }

    public function testOneRequestIsAllowed(): void
    {
        $dao = $this->givenThereIsNoPreviousCallStoredInDB();

        $limitator = new SOAP_RequestLimitator($nb_call = 10, $timeframe = 3600, $dao);
        $limitator->logCallTo('addProject');
    }

    private function givenThereWasAlreadyTenCallToAddProject()
    {
        $dao = \Mockery::spy(\SOAP_RequestLimitatorDao::class);

        $time30minutesAgo = $_SERVER['REQUEST_TIME'] - 30 * 60;
        $dar = TestHelper::arrayToDar(array('method_name' => 'addProject', 'date' => $time30minutesAgo));
        $dao->shouldReceive('searchFirstCallToMethod')->andReturns($dar);
        $dao->shouldReceive('foundRows')->andReturns(10);

        $dao->shouldReceive('saveCallToMethod')->with('addProject', Mockery::any())->once();

        return $dao;
    }

    public function testTwoRequestsShouldThrowAnException(): void
    {
        $dao = $this->givenThereWasAlreadyTenCallToAddProject();

        $this->expectException(\SOAP_NbRequestsExceedLimit_Exception::class);
        $limitator = new SOAP_RequestLimitator($nb_call = 10, $timeframe = 3600, $dao);
        $limitator->logCallTo('addProject');
    }
}
