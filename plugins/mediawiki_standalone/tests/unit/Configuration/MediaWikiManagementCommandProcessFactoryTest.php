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
}
