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
use Tuleap\ForgeConfigSandbox;
use Tuleap\MediawikiStandalone\Configuration\MustachePHPString\NullTemplateCache;
use Tuleap\MediawikiStandalone\Configuration\MustachePHPString\PHPStringMustacheRenderer;
use Tuleap\Test\PHPUnit\TestCase;

final class LocalSettingsPersistToPHPFileTest extends TestCase
{
    use ForgeConfigSandbox;

    private PHPStringMustacheRenderer $renderer;
    private LocalSettingsRepresentation $representation;

    protected function setUp(): void
    {
        $this->renderer       = new PHPStringMustacheRenderer(new NullTemplateCache(), __DIR__ . '/../../../templates');
        $this->representation = (new LocalSettingsRepresentationForTestBuilder())->generateTuleapLocalSettingsRepresentation();
    }

    public function testWritesLocalSettingsFile(): void
    {
        \ForgeConfig::set('sys_http_user', posix_getuid());
        $settings_path = vfsStream::setup()->url();
        $writer        = new LocalSettingsPersistToPHPFile(
            $settings_path,
            $this->renderer
        );

        $writer->persist($this->representation);

        self::assertFileExists($settings_path . '/LocalSettings.local.php');
        self::assertEquals('0600', substr(sprintf('%o', fileperms($settings_path . '/LocalSettings.local.php')), -4));
    }

    public function testThrowsAnErrorWhenTheFileCannotBePersisted(): void
    {
        $settings_path = vfsStream::setup('root', 0000)->url();
        $writer        = new LocalSettingsPersistToPHPFile(
            $settings_path,
            $this->renderer
        );

        $this->expectException(CannotPersistLocalSettings::class);
        $writer->persist($this->representation);
    }

    public function testThrowsAnErrorWhenTheOwnerOfThePersistingFileCannotBeSet(): void
    {
        $impossible_user_name = 'random_' . bin2hex(random_bytes(32));
        \ForgeConfig::set('sys_http_user', $impossible_user_name);
        $settings_path = vfsStream::setup()->url();
        $writer        = new LocalSettingsPersistToPHPFile(
            $settings_path,
            $this->renderer
        );

        $this->expectException(CannotPersistLocalSettings::class);
        $writer->persist($this->representation);
    }
}
