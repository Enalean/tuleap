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
 *
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Configuration;

use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediaWikiManagementCommandProcessFactoryTest extends TestCase
{
    public function testDoesNotTryToInstallIfLocalSettingsFileAlreadyExists(): void
    {
        $settings_directory = vfsStream::setup()->url();
        touch($settings_directory . '/LocalSettings.php');
        $factory = new MediaWikiManagementCommandProcessFactory(new NullLogger(), $settings_directory);

        $install_command = $factory->buildInstallCommand();

        self::assertTrue(Result::isOk($install_command->wait()));
    }

    public function testAdjustFarmInstanceConfigurationFile(): void
    {
        $settings_directory = vfsStream::setup()->url();
        \Psl\File\write(
            $settings_directory . '/LocalSettings.php',
            <<<EOS
            # Enabled skins.
            # The following skins were automatically enabled:
            wfLoadSkin( 'MinervaNeue' );
            wfLoadSkin( 'MonoBook' );
            wfLoadSkin( 'Timeless' );
            wfLoadSkin( 'TuleapSkin' );
            wfLoadSkin( 'Vector' );
            EOS
        );

        $factory = new MediaWikiManagementCommandProcessFactory(new NullLogger(), $settings_directory);

        $update_config_command = $factory->buildFarmInstanceConfigurationUpdate();

        self::assertTrue(Result::isOk($update_config_command->wait()));
        self::assertSame(
            <<<EOS
            # Enabled skins.
            # The following skins were automatically enabled:
            //wfLoadSkin( 'MinervaNeue' );
            //wfLoadSkin( 'MonoBook' );
            //wfLoadSkin( 'Timeless' );
            //wfLoadSkin( 'TuleapSkin' );
            wfLoadSkin( 'Vector' );
            EOS,
            \Psl\File\read($settings_directory . '/LocalSettings.php')
        );
    }
}
