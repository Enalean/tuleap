<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

class LogoRetrieverTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
    }

    public function tearDown()
    {
        parent::tearDown();
        ForgeConfig::restore();
    }

    public function itFindsExistingLogo()
    {
        ForgeConfig::set('sys_urlroot', '/tuleap/src/www/');
        $logo_retriever = new LogoRetriever();
        $this->assertTrue($logo_retriever->getPath());
    }

    public function itDoesNotFoundUnavailableLogo()
    {
        ForgeConfig::set('sys_urlroot', '/wrongpath/');
        $logo_retriever = new LogoRetriever();
        $this->assertFalse($logo_retriever->getPath());
    }
}
