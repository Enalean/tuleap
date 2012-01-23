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

require_once 'common/soap/SOAP_RequestLimitator.class.php';

class SOAP_RequestLimitatorTest extends UnitTestCase {

    public function testTwoRequestsShouldBeAllowedByConfiguration() {
        $limitator = new SOAP_RequestLimitator($nb_call = 10, $timeframe = 3600);
        $limitator->logCallTo('addProject');
        $limitator->logCallTo('addProject');
    }
    
    public function testOneRequestIsAllowed() {
        $limitator = new SOAP_RequestLimitator($nb_call = 1, $timeframe = 3600);
        $limitator->logCallTo('addProject');
    }
    
    public function testTwoRequestsShouldThrowAnException() {
        $this->expectException('SOAP_NbRequestsExceedLimit_Exception');
        $limitator = new SOAP_RequestLimitator($nb_call = 1, $timeframe = 2);
        $limitator->logCallTo('addProject');
        $limitator->logCallTo('addProject');
    }
    
    public function testTwoRequestsAtAllowedRateShouldBeAllowed() {
        $limitator = new SOAP_RequestLimitator($nb_call = 1, $timeframe = 2);
        $limitator->logCallTo('addProject');
        sleep(2);
        $limitator->logCallTo('addProject');
    }
}

?>
