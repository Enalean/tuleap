<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class HudsonTestResultTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var XML_Security */
    private $xml_security;
    /**
     * @var Http_Client
     */
    private $http_client;

    public function setUp()
    {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
        $this->http_client = \Mockery::spy(\Http_Client::class);

        $GLOBALS['Language'] = Mockery::spy(BaseLanguage::class);
    }

    public function tearDown()
    {
        unset($GLOBALS['Language']);
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function testMalformedURL()
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonTestResult("toto", $this->http_client);
    }

    public function testMissingSchemeURL()
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonTestResult("code4:8080/hudson/jobs/tuleap", $this->http_client);
    }

    public function testMissingHostURL()
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonTestResult("http://", $this->http_client);
    }

    public function testSimpleJobTestResult()
    {
        $test_result_file = __DIR__ . '/resources/testReport.xml';
        $xmldom           = simplexml_load_file($test_result_file);

        $test_result = Mockery::spy(HudsonTestResult::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $test_result->shouldReceive('_getXMLObject')->andReturn($xmldom);
        $test_result->__construct("http://myCIserver/jobs/myCIjob/lastBuild/testReport/", $this->http_client);

        $this->assertEquals($test_result->getFailCount(), 5);
        $this->assertEquals($test_result->getPassCount(), 416);
        $this->assertEquals($test_result->getSkipCount(), 3);
        $this->assertEquals($test_result->getTotalCount(), 424);
    }
}
