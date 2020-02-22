<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap;

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use URLVerification;

class URLVerificationPrivateRestrictedTest extends TestCase
{

    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    private $url_verification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->url_verification = new URLVerification();

        ForgeConfig::set('sys_default_domain', 'default.example.test');
        ForgeConfig::set('sys_https_host', 'default');
    }

    public function testItChecksUriInternal(): void
    {
        $this->assertFalse($this->url_verification->isInternal('http://evil.example.com/'));
        $this->assertFalse($this->url_verification->isInternal('https://evil.example.com/'));
        $this->assertFalse($this->url_verification->isInternal('javascript:alert(1)'));
        $this->assertTrue($this->url_verification->isInternal('/path/to/feature'));
        $this->assertFalse(
            $this->url_verification->isInternal('http://' . ForgeConfig::get('sys_default_domain') . '/smthing')
        );
        $this->assertFalse(
            $this->url_verification->isInternal('https://' . ForgeConfig::get('sys_https_host') . '/smthing')
        );

        $this->assertFalse($this->url_verification->isInternal('//example.com'));
        $this->assertFalse($this->url_verification->isInternal('/\example.com'));
        $this->assertFalse($this->url_verification->isInternal(
            'https://' . ForgeConfig::get('sys_https_host') . '@evil.example.com'
        ));
    }
}
