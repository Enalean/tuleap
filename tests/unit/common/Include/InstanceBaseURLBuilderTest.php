<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap;

use PHPUnit\Framework\TestCase;

final class InstanceBaseURLBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testInstanceBaseURLIsHTTPSByDefault(): void
    {
        \ForgeConfig::set('sys_https_host', 'example.com');

        $instance_base_url_builder = new InstanceBaseURLBuilder();

        $this->assertEquals('https://example.com', $instance_base_url_builder->build());
    }

    public function testInstanceBaseURLFallbackToHTTPWhenHTTPSIsNotAvailable(): void
    {
        \ForgeConfig::set('sys_default_domain', 'cleartext.example.com');

        $instance_base_url_builder = new InstanceBaseURLBuilder();

        $this->assertEquals('http://cleartext.example.com', $instance_base_url_builder->build());
    }
}
