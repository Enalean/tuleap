<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

Mock::generate('SOAP_RequestLimitatorDao');

/**
 * Ensure the given value is ~1h ago (from execution time).
 */
class AboutOneHourAgoExpectation extends SimpleExpectation
{

    public function test($input)
    {
        $oneHourAgo = $_SERVER['REQUEST_TIME'] - 3600;
        $delta = abs($input - $oneHourAgo);
        if ($delta <= 10) {
            return true;
        }
        return false;
    }

    public function testMessage($input)
    {
        $now = $_SERVER['REQUEST_TIME'];
        return 'The given value is not ~1 hour ago (Input: '.$input.' => '.date('c', $input).' <=> Now: '.$now.' => '.date('c', $now).')';
    }
}

class SOAP_RequestLimitatorTest extends TuleapTestCase
{

    private function GivenThereWasAlreadyOneCallTheLastHour()
    {
        $dao = new MockSOAP_RequestLimitatorDao();
        $requestTime = $_SERVER['REQUEST_TIME'];
        $time30minutesAgo = $requestTime - 30 * 60;
        $dar = TestHelper::arrayToDar(array('method_name' => 'addProject', 'date' => $time30minutesAgo));
        // Ensure we search into the db stuff ~1 hour agos
        $dao->setReturnValue('searchFirstCallToMethod', $dar, array('addProject', new AboutOneHourAgoExpectation()));
        $dao->expectOnce('foundRows');
        $dao->setReturnValue('foundRows', 1);

        // Ensure the saved value is ~ the current time (more or less 10 sec)
        $dao->expectOnce('saveCallToMethod', array('addProject', new WithinMarginExpectation($requestTime, 10)));

        return $dao;
    }

    public function testTwoRequestsShouldBeAllowedByConfiguration()
    {
        $dao = $this->GivenThereWasAlreadyOneCallTheLastHour();
        $limitator = new SOAP_RequestLimitator($nb_call = 10, $timeframe = 3600, $dao);
        $limitator->logCallTo('addProject');
    }

    private function GivenThereIsNoPreviousCallStoredInDB()
    {
        $dao = new MockSOAP_RequestLimitatorDao();

        $dar = new MockDataAccessResult();
        $dar->setReturnValue('rowCount', 0);
        $dar->setReturnValue('getRow', null);
        $dao->setReturnValue('searchFirstCallToMethod', $dar);

        $dao->expectOnce('saveCallToMethod', array('addProject', '*'));

        return $dao;
    }

    public function testOneRequestIsAllowed()
    {
        $dao = $this->GivenThereIsNoPreviousCallStoredInDB();

        $limitator = new SOAP_RequestLimitator($nb_call = 10, $timeframe = 3600, $dao);
        $limitator->logCallTo('addProject');
    }

    private function GivenThereWasAlreadyTenCallToAddProject()
    {
        $dao = new MockSOAP_RequestLimitatorDao();

        $time30minutesAgo = $_SERVER['REQUEST_TIME'] - 30 * 60;
        $dar = TestHelper::arrayToDar(array('method_name' => 'addProject', 'date' => $time30minutesAgo));
        $dao->setReturnValue('searchFirstCallToMethod', $dar);
        $dao->setReturnValue('foundRows', 10);

        $dao->expectOnce('saveCallToMethod', array('addProject', '*'));

        return $dao;
    }

    public function testTwoRequestsShouldThrowAnException()
    {
        $dao = $this->GivenThereWasAlreadyTenCallToAddProject();

        $this->expectException('SOAP_NbRequestsExceedLimit_Exception');
        $limitator = new SOAP_RequestLimitator($nb_call = 10, $timeframe = 3600, $dao);
        $limitator->logCallTo('addProject');
    }
}
