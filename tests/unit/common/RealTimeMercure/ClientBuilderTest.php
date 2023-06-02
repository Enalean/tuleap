<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
namespace Tuleap\RealTimeMercure;

use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

class ClientBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testnoError()
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, true);
        $structure = [
            'env' => [
                'mercure.env' => 'MERCURE_KEY=' . str_repeat('a', 160),
            ],
        ];
        $root      = vfsStream::setup('root1', null, $structure);
        $this->assertTrue($root->hasChild('env/mercure.env'));
        $mercure_client = ClientBuilder::build($root->url() . '/env/mercure.env');
        $this->assertInstanceOf(MercureClient::class, $mercure_client);
    }

    public function testNoFeatureFlag()
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, false);
        $root           = vfsStream::setup('root2');
        $mercure_client = ClientBuilder::build($root->url() . '/mercure.env');
        $this->assertInstanceOf(NullClient::class, $mercure_client);
    }
}
