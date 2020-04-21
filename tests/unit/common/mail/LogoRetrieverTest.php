<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class LogoRetrieverTest extends \PHPUnit\Framework\TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    private $path;

    protected function setUp(): void
    {
        $this->path = \org\bovigo\vfs\vfsStream::setup('/')->url();
        mkdir($this->path . '/images', 0750, true);
        ForgeConfig::set('sys_data_dir', $this->path);
    }

    public function testItFindsExistingLogo(): void
    {
        touch($this->path . '/images/organization_logo.png');

        $logo_retriever = new LogoRetriever();
        $this->assertNotNull($logo_retriever->getPath());
    }

    public function testItDoesNotFoundUnavailableLogo(): void
    {
        $logo_retriever = new LogoRetriever();
        $this->assertNull($logo_retriever->getPath());
    }
}
