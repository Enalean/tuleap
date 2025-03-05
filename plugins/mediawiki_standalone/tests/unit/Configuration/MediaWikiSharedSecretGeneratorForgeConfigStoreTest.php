<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Configuration;

use org\bovigo\vfs\vfsStream;
use Tuleap\Config\ConfigDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediaWikiSharedSecretGeneratorForgeConfigStoreTest extends TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&ConfigDao
     */
    private $dao;
    private MediaWikiSharedSecretGeneratorForgeConfigStore $secret_generator;

    protected function setUp(): void
    {
        $this->dao              = $this->createStub(ConfigDao::class);
        $this->secret_generator = new MediaWikiSharedSecretGeneratorForgeConfigStore($this->dao);

        $sys_custom_dir_path = vfsStream::setup()->url();
        mkdir($sys_custom_dir_path . '/conf');
        \ForgeConfig::set('sys_custom_dir', $sys_custom_dir_path);
    }

    public function testGeneratesSharedSecret(): void
    {
        $this->dao->method('save');

        $secret = $this->secret_generator->generateSharedSecret();

        self::assertGreaterThan(0, strlen($secret->getString()));
    }
}
