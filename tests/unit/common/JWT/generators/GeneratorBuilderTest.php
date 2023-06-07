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
namespace Tuleap\JWT\generators;

use Tuleap\ForgeConfigSandbox;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Test\PHPUnit\TestCase;
use org\bovigo\vfs\vfsStream;

final class GeneratorBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testnoError(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, true);

        $structure = [
            'env' => [
                'mercure.env' => 'MERCURE_KEY=' . str_repeat('a', 160),
            ],
        ];
        $root      = vfsStream::setup('root1', null, $structure);
        $this->assertTrue($root->hasChild('env/mercure.env'));
        $mercure_client = MercureJWTGeneratorBuilder::build($root->url() . '/env/mercure.env');
        $this->assertnotNull($mercure_client->getTokenBackend());
    }

    public function testNoFileError(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, true);
        $root           = vfsStream::setup('root2');
        $mercure_client = MercureJWTGeneratorBuilder::build($root->url() . '/mercure.env');
        $this->assertNull($mercure_client->getTokenBackend());
    }

    public function testMalformedFileError(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, true);
        $structure      = [
            'env' => [
                'mercure.env' => 'testtes' . str_repeat('a', 3),
            ],
        ];
        $root           = vfsStream::setup('root3', null, $structure);
        $mercure_client = MercureJWTGeneratorBuilder::build($root->url() . '/env/mercure.env');
        $this->assertnull($mercure_client->getTokenBackend());
    }

    public function testInvalidKeyError(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, true);
        $structure      = [
            'env' => [
                'mercure.env' => 'MERCURE_KEY=' . str_repeat('a', 3),
            ],
        ];
        $root           = vfsStream::setup('root4', null, $structure);
        $mercure_client = MercureJWTGeneratorBuilder::build($root->url() . '/env/mercure.env');
        $this->assertNull($mercure_client->getTokenBackend());
    }

    public function testNoFeatureFlag(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, false);
        $structure = [
            'env' => [
                'mercure.env' => 'MERCURE_KEY=' . str_repeat('a', 160),
            ],
        ];
        $root      = vfsStream::setup('root5', null, $structure);
        $this->assertTrue($root->hasChild('env/mercure.env'));
        $mercure_client = MercureJWTGeneratorBuilder::build($root->url() . '/env/mercure.env');
        $this->assertNull($mercure_client->getTokenBackend());
    }
}
