<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Test\PHPUnit\TestCase;

final class MercureJWTGeneratorBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    private string $mercure_key_path;
    private string $base_path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->base_path        = vfsStream::setup()->url();
        $this->mercure_key_path = $this->base_path . '/mercure_key.txt';

        touch($this->mercure_key_path);
        file_put_contents(
            $this->mercure_key_path,
            "MERCURE_KEY=" . str_repeat('aA', 100),
        );
    }

    public function testItGeneratesMercureJWTWhenKanbanFeatureFlagIsSet(): void
    {
        \ForgeConfig::set(MercureClient::FEATURE_FLAG_KANBAN_KEY, 1);

        $generator = MercureJWTGeneratorBuilder::build($this->mercure_key_path);

        self::assertInstanceOf(
            MercureJWTGenerator::class,
            $generator,
        );
    }

    public function testItGeneratesMercureJWTWhenTestManagementFeatureFlagIsSet(): void
    {
        \ForgeConfig::set(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY, 1);

        $generator = MercureJWTGeneratorBuilder::build($this->mercure_key_path);

        self::assertInstanceOf(
            MercureJWTGenerator::class,
            $generator,
        );
    }

    public function testItGeneratesNullMercureGeneratorWhenNoFeatureFlagIsSet(): void
    {
        $generator = MercureJWTGeneratorBuilder::build($this->mercure_key_path);

        self::assertInstanceOf(
            NullMercureJWTGenerator::class,
            $generator,
        );
    }

    public function testItGeneratesNullMercureGeneratorWhenNoMercureKeyFile(): void
    {
        \ForgeConfig::set(MercureClient::FEATURE_FLAG_KANBAN_KEY, 1);
        \ForgeConfig::set(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY, 1);

        $generator = MercureJWTGeneratorBuilder::build($this->base_path . '/non_existing');

        self::assertInstanceOf(
            NullMercureJWTGenerator::class,
            $generator,
        );
    }

    public function testItGeneratesNullMercureGeneratorWhenMercureKeyDoesNotStartWithMercureKeyPrefix(): void
    {
        \ForgeConfig::set(MercureClient::FEATURE_FLAG_KANBAN_KEY, 1);
        \ForgeConfig::set(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY, 1);

        touch($this->base_path . '/wrong_content_start');
        file_put_contents(
            $this->base_path . '/wrong_content_start',
            str_repeat('aA', 100),
        );

        $generator = MercureJWTGeneratorBuilder::build($this->base_path . '/wrong_content_start');

        self::assertInstanceOf(
            NullMercureJWTGenerator::class,
            $generator,
        );
    }

    public function testItGeneratesNullMercureGeneratorWhenMercureKeyDoesNotHaveTheExpectedLength(): void
    {
        \ForgeConfig::set(MercureClient::FEATURE_FLAG_KANBAN_KEY, 1);
        \ForgeConfig::set(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY, 1);

        touch($this->base_path . '/wrong_content_length');
        file_put_contents(
            $this->base_path . '/wrong_content_length',
            "MERCURE_KEY=" . str_repeat('aA', 1),
        );

        $generator = MercureJWTGeneratorBuilder::build($this->base_path . '/wrong_content_length');

        self::assertInstanceOf(
            NullMercureJWTGenerator::class,
            $generator,
        );
    }
}
