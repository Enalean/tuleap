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

require_once(dirname(__FILE__).'/../include/Webdav_URLVerification.class.php');
Mock::generatePartial(
    'Webdav_URLVerification',
    'Webdav_URLVerificationTestVersion',
    array('getWebDAVHost', /*'parentAssertValidURL', */'forbiddenError')
);

class Webdav_URLVerificationTest extends UnitTestCase {

    function testAssertValidUrlHTTPAndForceSslEquals0() {
        $server = array('HTTP_HOST' => 'webdav.codendi.org');

        $GLOBALS['sys_force_ssl'] = 0;

        $WebdavURLVerification = new Webdav_URLVerificationTestVersion($this);
        $WebdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $WebdavURLVerification->expectNever('forbiddenError');
        //$WebdavURLVerification->expectNever('parentAssertValidURL');

        $WebdavURLVerification->assertValidUrl($server);
    }

    function testAssertValidUrlHTTPSAndForceSslEquals0() {
        $server = array('HTTP_HOST' => 'webdav.codendi.org',
                        'HTTPS'     => 'on');

        $GLOBALS['sys_force_ssl'] = 0;

        $WebdavURLVerification = new Webdav_URLVerificationTestVersion($this);
        $WebdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $WebdavURLVerification->expectNever('forbiddenError');
        //$WebdavURLVerification->expectNever('parentAssertValidURL');

        $WebdavURLVerification->assertValidUrl($server);
    }

    function testAssertValidUrlHTTPAndForceSslEquals1() {
        $server = array('HTTP_HOST' => 'webdav.codendi.org');

        $GLOBALS['sys_force_ssl'] = 1;

        $WebdavURLVerification = new Webdav_URLVerificationTestVersion($this);
        $WebdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $WebdavURLVerification->expectOnce('forbiddenError');
        //$WebdavURLVerification->expectNever('parentAssertValidURL');

        $WebdavURLVerification->assertValidUrl($server);
    }

    function testAssertValidUrlHTTPSAndForceSslEquals1() {
        $server = array('HTTP_HOST' => 'webdav.codendi.org',
                        'HTTPS'     => 'on');

        $GLOBALS['sys_force_ssl'] = 1;

        $WebdavURLVerification = new Webdav_URLVerificationTestVersion($this);
        $WebdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $WebdavURLVerification->expectNever('forbiddenError');
        //$WebdavURLVerification->expectNever('parentAssertValidURL');

        $WebdavURLVerification->assertValidUrl($server);
    }

    /*function testAssertValidUrlNotPluginHost() {
        $server = array('HTTP_HOST' => 'codendi.org');

        $WebdavURLVerification = new Webdav_URLVerificationTestVersion($this);
        $WebdavURLVerification->setReturnValue('getWebDAVHost', 'webdav.codendi.org');

        $WebdavURLVerification->expectNever('forbiddenError');
        $WebdavURLVerification->expectOnce('parentAssertValidURL');

        $WebdavURLVerification->assertValidUrl($server);
    }*/
}
?>