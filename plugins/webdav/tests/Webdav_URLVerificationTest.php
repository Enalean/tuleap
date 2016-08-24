<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'bootstrap.php';

class Webdav_URLVerificationTest extends TuleapTestCase {

    private $request;
    private $webdavURLVerification;

    public function setUp() {
        parent::setUp();

        $this->request = mock('HTTPRequest');
        $this->webdavURLVerification = partial_mock('Webdav_URLVerification', array('getWebDAVHost', 'forbiddenError', 'isException'));
    }

    public function tearDown() {
        unset($GLOBALS['sys_force_ssl']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['sys_https_host']);
        parent::tearDown();
    }

    function testAssertValidUrlHTTPAndForceSslEquals0() {
        $server = array('HTTP_HOST' => 'webdav.codendi.org');

        $GLOBALS['sys_default_domain'] = 'example.com';
        $GLOBALS['sys_https_host']     = 'example.com';
        $GLOBALS['sys_force_ssl'] = 0;

        $this->webdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $this->webdavURLVerification->expectNever('forbiddenError');
        $this->webdavURLVerification->expectNever('isException'); // no parent call

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    function testAssertValidUrlHTTPSAndForceSslEquals0() {
        $server = array('HTTP_HOST' => 'webdav.codendi.org');
        stub($this->request)->isSecure()->returns(true);

        $GLOBALS['sys_default_domain'] = 'example.com';
        $GLOBALS['sys_https_host']     = 'example.com';
        $GLOBALS['sys_force_ssl']      = 0;

        $this->webdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $this->webdavURLVerification->expectNever('forbiddenError');
        $this->webdavURLVerification->expectNever('isException'); // no parent call

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    function testAssertValidUrlHTTPAndForceSslEquals1() {
        $server = array('HTTP_HOST' => 'webdav.codendi.org');

        $GLOBALS['sys_default_domain'] = 'example.com';
        $GLOBALS['sys_https_host']     = 'example.com';
        $GLOBALS['sys_force_ssl']      = 1;

        $this->webdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $this->webdavURLVerification->expectOnce('forbiddenError');
        $this->webdavURLVerification->expectNever('isException'); // no parent call

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    function _testAssertValidUrlHTTPSAndForceSslEquals1() {
        $server = array('HTTP_HOST' => 'webdav.codendi.org');
        stub($this->request)->isSecure()->returns(true);

        $GLOBALS['sys_default_domain'] = 'example.com';
        $GLOBALS['sys_https_host']     = 'example.com';
        $GLOBALS['sys_force_ssl'] = 1;

        $this->webdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $this->webdavURLVerification->expectNever('forbiddenError');
        $this->webdavURLVerification->expectNever('isException'); // no parent call

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    function testAssertValidUrlNotPluginHost() {
        $server = array('HTTP_HOST' => 'codendi.org');

        $this->webdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $this->webdavURLVerification->expectNever('forbiddenError');
        $this->webdavURLVerification->expectOnce('isException'); // parent call
        $this->webdavURLVerification->setReturnValue('isException', true);

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    function testAssertValidUrlButWebdavHostIsDefaultDomain() {
        $server = array('HTTP_HOST' => 'a.codendi.org');

        $GLOBALS['sys_default_domain'] = 'a.codendi.org';

        $this->webdavURLVerification->setReturnValue('getWebDAVHost', 'a.codendi.org');

        $this->webdavURLVerification->expectNever('forbiddenError');
        $this->webdavURLVerification->expectOnce('isException'); // parent call
        $this->webdavURLVerification->setReturnValue('isException', true);

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    function testAssertValidUrlButWebdavHostIsHttpsHost() {
        $server = array('HTTP_HOST' => 'b.codendi.org');

        $GLOBALS['sys_default_domain'] = 'example.com';
        $GLOBALS['sys_https_host']     = 'b.codendi.org';

        $this->webdavURLVerification->setReturnValue('getWebDAVHost', 'b.codendi.org');

        $this->webdavURLVerification->expectNever('forbiddenError');
        $this->webdavURLVerification->expectOnce('isException'); // parent call
        $this->webdavURLVerification->setReturnValue('isException', true);

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }
}
